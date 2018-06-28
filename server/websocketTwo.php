<?php 
/** 
 * 使用Nginx在代理分发到websocket服务器端口时,如果使用长连接,则nginx的配置参数proxy_read_timeout会生效
 * 当连接超过proxy_read_timeout设置的时间,会主动断开客户端与服务器的链接
 * 解决参考
 * https://blog.csdn.net/Jack______/article/details/76588998
 * https://blog.csdn.net/jkxqj/article/details/77848466
 */

use app\common\lib\task\Task;
use app\common\lib\Redis\Predis;

// 适配th5.1框架的wobsocket_server class
class WebSocket
{
	const HOST = '127.0.0.1';
	const PORT = 9504;
	const CHART_PORT = 9504;

	public $websocket;

	public function __construct() {

		$this->websocket = new swoole_websocket_server(self::HOST, self::PORT);

		// 监听第二个端口,不设置set方法则继承第一个的设置属性
		// $this->websocket->listen(self::HOST, self::CHART_PORT, SWOOLE_SOCK_TCP);

		// on方法为swoole websocket的回调方法,跟本类无关系
		$this->websocket->on('start', [$this, 'onStart']);
		$this->websocket->on('open', [$this, 'onOpen']);
		$this->websocket->on('message', [$this, 'onMessage']);
		$this->websocket->on('request', [$this, 'onRequest']);
		$this->websocket->on('WorkerStart', [$this, 'onWorkerStart']);
		$this->websocket->on('task', [$this, 'onTask']);
		$this->websocket->on('finish', [$this, 'onFinish']);
		$this->websocket->on('close', [$this, 'onClose']);

		$this->websocket->set([
			'enable_static_handler' => true,
			//'document_root'	=> '/mnt/hgfs/vm_www/swoole_imooc/public/static',
			'worker_num' => 8, // woker进程数 cpu核数的1-4倍
			'task_worker_num' => 5,
			'reactor_num' => 2, // reactor线程数 多核优势的体现
			'heartbeat_idle_time' => 6000,
    		'heartbeat_check_interval' => 600,
		]);

		$this->websocket->start();

	}

	/**
	 * 监听start
	 */
	public function onStart() {
		// 设置进程的名称等同于php的cli_set_process_title
		swoole_set_process_name('master_live');
	}

	/**
	 * 客户端成功连接websocket监听
	 */
	public function onOpen($server, $request) {
		$result = Predis::getInstance()->sadd(config('redis.live_game_key'), $request->fd);
		echo "client-fd-success : {$request->fd}\n";
	}

	/**
	 * 监听客户端发送事件
	 */
	public function onMessage($server, $frame) {
	    echo "receive from {$frame->fd}:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}\n";
    	$server->push($frame->fd, "this is server");
	}


	public function onRequest($request, $response) {
		echo 'come from WebSocketTwo'.PHP_EOL;

		// 自定义http_server服务器访问日志
		$accessStr  = '';
		$accessStr .= @$request->header['referer'].' - ';
		$accessStr .= $request->header['x-real-ip'].' - - ';
		$accessStr .= '['.date('d/m/Y:H:i:s',time()).' +0800] ';
		$accessStr .= 'HEAD /'.$request->server['server_protocol'].' - ';
		$accessStr .= $request->header['user-agent'];

		$content = $accessStr.PHP_EOL;

		$result = swoole_async_write(__DIR__.'/access.log', $content, -1);
		
		if (!$result) {
			echo 'http_server write access.log fail'.PHP_EOL;
		}

		// 封装get,post,server内容,使php函数能获取
		// 当get,post,server在使用过程中没有销毁则会一直存在与内存中,可使用unset方法销毁或http的close方法
		$_GET = $_POST = $_SERVER = $_FILES = [];
		// print_r($request->server);
		if (isset($request->get)) {
			// unset($_GET);
			foreach ($request->get as $key => $value) {
				$_GET[$key] = $value;
			}
		}

		if (isset($request->files)) {
			foreach ($request->files as $key => $value) {
				$_FILES[$key] = $value;
			}
		}

		if (isset($request->post)) {
			foreach ($request->post as $key => $value) {
				$_POST[$key] = $value;
			}
		}
		// 保存websocket到Post让逻辑层可以使用
		$_POST['httpServer'] = $this->websocket;

		if (isset($request->server)) {
			foreach ($request->server as $key => $value) {
				$_SERVER[strtoupper($key)] = $value;
			}
		}

		if (isset($request->header)) {
			foreach ($request->header as $key => $value) {
				$_SERVER[strtoupper($key)] = $value;
			}
		}

		ob_start();
		
		// try {
			// 执行应用并响应	
			think\Container::get('app', [defined('APP_PATH') ? APP_PATH : ''])
			    ->run()
			    ->send();
		// } catch (Exception $e) {
		// 	// todo
		// 	echo 'get thinkphp run fail'.PHP_EOL;
		// }

		// 获取thinkphp控制器方法存在缓存,修改框架获取控制器方法
		// echo "-swoole-action-".request()->action();
		// http://sw.server/?s=Index/Index/singwa注意框架使用方法才能获取控制器方法,不能使用http://sw.server/Index/Index/singwa

		$content = ob_get_contents();
		ob_end_flush();

		// 内容响应到页面
		$end = $response->end($content);

		// 暴力关闭服务器,下次进来会重新开启,造成较大的资源损耗
		// $http_server->close();
	}

	/**
	 * http服务器热加载启动,实际等于启动worker*reactor_num个进程服务器
	 * @param $server
	 * @param $worker_id
	 */
	public function onWorkerStart($server, $worker_id) {

		// 定义应用目录
		define('APP_PATH', __DIR__ . '/../application/');

		// 加载基础文件
		// require __DIR__ . '/../thinkphp/base.php';
		require __DIR__ . '/../thinkphp/start.php';

		$this->checkSmember();		
	}

	/**
	 * 监听task事件
	 * @param $serv
	 * @param $taskId
	 * @param $workerId
	 * @param $data
	 */
	public function onTask($serv, $taskId, $workerId, $data) {
		if (!isset($data['method'])) {
			return 'task param error';
		}

		if(method_exists('Task' ,$data['method'])) {
			return 'task method error';
		}

		return Task::$data['method']($data, $serv);
	}

	/**
	 * 监听task的结果事件
	 * @param $serv
	 * @param $taskId
	 * @param $data
	 */
	public function onFinish($serv, $taskId, $data) {
		echo $data.PHP_EOL;
	}

	/**
	 * 客户端关闭连接
	 */
	public function onClose($serv, $fd) {
		$result = Predis::getInstance()->srem(config('redis.live_game_key'), $fd);
		echo "close-fd-success : {$fd}\n";
	}

	/**
	 * 服务器启动时,检查并清空有序集合
	 */
	public function checkSmember() {
		$result = Predis::getInstance()->smembers(config('redis.live_game_key'));
		if ($result) {
			Predis::getInstance()->del(config('redis.live_game_key'));
		}
	}
}

new websocket();

// 20台机器 agent -> spark(计算) -> 数据库   elasticsearch