<?php 
// 适配th5.1框架的http_server class
use app\common\lib\task\Task;

class HttpServer
{
	const HOST = '127.0.0.1';
	const PORT = 9503;

	public $httpServer;

	public function __construct() {
		$this->httpServer = new swoole_http_server(self::HOST, self::PORT);

		// on方法为httpServer的回调方法,跟本类无关系
		$this->httpServer->on('request', [$this, 'onRequest']);
		$this->httpServer->on('WorkerStart', [$this, 'onWorkerStart']);
		$this->httpServer->on('task', [$this, 'onTask']);
		$this->httpServer->on('finish', [$this, 'onFinish']);

		$this->httpServer->set([
			'enable_static_handler' => true,
			'document_root'	=> '/mnt/hgfs/vm_www/swoole_imooc/public/static',
			'worker_num' => 8, // woker进程数 cpu核数的1-4倍
			'task_worker_num' => 5,
			'reactor_num' => 2, // reactor线程数 多核优势的体现
		]);

		$this->httpServer->start();
	}

	public function onRequest($request, $response) {
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
		$_GET = $_POST = $_SERVER = [];
		// print_r($request->server);
		if (isset($request->get)) {
			// unset($_GET);
			foreach ($request->get as $key => $value) {
				$_GET[$key] = $value;
			}
		}

		if (isset($request->post)) {
			foreach ($request->post as $key => $value) {
				$_POST[$key] = $value;
			}
		}
		// 保存httpServer到Post让逻辑层可以使用
		$_POST['httpServer'] = $this->httpServer;

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
	 * http服务器热加载启动
	 * @param $server
	 * @param $worker_id
	 */
	public function onWorkerStart($server, $worker_id) {
		// 定义应用目录
		define('APP_PATH', __DIR__ . '/../application/');

		// 加载基础文件
		// require __DIR__ . '/../thinkphp/base.php';
		require __DIR__ . '/../thinkphp/start.php';
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

		return Task::$data['method']($data);
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
}

new HttpServer();



/* -----------------------------   面向过程   ----------------------------- */     
// $http_server = new swoole_http_server('127.0.0.1', 9503);

// $http_server->on('request', function($request, $response) use($http_server){
	
// 	// 自定义http_server服务器访问日志
// 	$accessStr  = '';
// 	$accessStr .= @$request->header['referer'].' - ';
// 	$accessStr .= $request->header['x-real-ip'].' - - ';
// 	$accessStr .= '['.date('d/m/Y:H:i:s',time()).' +0800] ';
// 	$accessStr .= 'HEAD /'.$request->server['server_protocol'].' - ';
// 	$accessStr .= $request->header['user-agent'];

// 	$content = $accessStr.PHP_EOL;

// 	$result = swoole_async_write(__DIR__.'/access.log', $content, -1);
	
// 	if (!$result) {
// 		echo 'http_server write access.log fail'.PHP_EOL;
// 	}

// 	// 封装get,post,server内容,使php函数能获取
// 	// 当get,post,server在使用过程中没有销毁则会一直存在与内存中,可使用unset方法销毁或http的close方法
// 	$_GET = $_POST = $_SERVER = [];
// 	// print_r($request->server);
// 	if (isset($request->get)) {
// 		// unset($_GET);
// 		foreach ($request->get as $key => $value) {
// 			$_GET[$key] = $value;
// 		}
// 	}

// 	if (isset($request->post)) {
// 		foreach ($request->post as $key => $value) {
// 			$_POST[$key] = $value;
// 		}
// 	}

// 	if (isset($request->server)) {
// 		foreach ($request->server as $key => $value) {
// 			$_SERVER[strtoupper($key)] = $value;
// 		}
// 	}

// 	if (isset($request->header)) {
// 		foreach ($request->header as $key => $value) {
// 			$_SERVER[strtoupper($key)] = $value;
// 		}
// 	}

// 	ob_start();
	
// 	// try {
// 		// 执行应用并响应	
// 		think\Container::get('app', [defined('APP_PATH') ? APP_PATH : ''])
// 		    ->run()
// 		    ->send();
// 	// } catch (Exception $e) {
// 	// 	// todo
// 	// 	echo 'get thinkphp run fail'.PHP_EOL;
// 	// }

// 	// 获取thinkphp控制器方法存在缓存,修改框架获取控制器方法
// 	// echo "-swoole-action-".request()->action();
// 	// http://sw.server/?s=Index/Index/singwa注意框架使用方法才能获取控制器方法,不能使用http://sw.server/Index/Index/singwa

// 	$content = ob_get_contents();
// 	ob_end_flush();

// 	// 内容响应到页面
// 	$end = $response->end($content);

// 	// 暴力关闭服务器,下次进来会重新开启,造成较大的资源损耗
// 	// $http_server->close();
// });

// $http_server->set(
// 	[
// 		'enable_static_handler' => true,
// 		'document_root'	=> '/mnt/hgfs/vm_www/swoole_imooc/public/static',
// 		'worker_num' => 8, // woker进程数 cpu核数的1-4倍
// 		'reactor_num' => 2, // reactor线程数 多核优势的体现
// 	]
// );

// // onWorkerStart在Worker进程/Task进程启动时发生。这里创建的对象可以在进程生命周期内使用。既开启服务器就热加载
// // php-fpm是每执行一个文件就启用加载一次框架内容，对比起来性能优势相对较差
// $http_server->on('WorkerStart', function(swoole_server $server, $worker_id) {

// 	// 定义应用目录
// 	define('APP_PATH', __DIR__ . '/../application/');

// 	// 加载基础文件
// 	require __DIR__ . '/../thinkphp/base.php';

// });

// $http_server->start();



