<?php 

/**
 * 在linux后台执行php脚本命令 nohup
 * nohup /usr/bin/php /mnt/hgfs/vm_www/swoole_imooc/server/monitoring/server.php > /mnt/hgfs/vm_www/swoole_imooc/server/monitoring/monitoring.log &
 */

class Server
{

	const PORT = 9503;

	public function monitoring() {
		/** 
		 * shell_exec php执行shell命令
		 * 2>/dev/null 把提示信息去掉
		 * grep LISTEN 上一步的结果集在进行grep查找
		 * wc -l 获取结果的行数
		 */
		$result = shell_exec('netstat -tlunp 2>/dev/null | grep '.self::PORT.'| grep LISTEN | wc -l');
		if ($result == 1) {
			// todo 执行通知 邮件/短信 
			echo date('Y-m-d H:i:s').'server connect success'.PHP_EOL;
		}else {
			echo date('Y-m-d H:i:s').'server connect error'.PHP_EOL;
		}
	}
}

// swoole定时器
swoole_timer_tick(2000, function($timer_id) {
	$server = new Server();
	$server->monitoring();
});