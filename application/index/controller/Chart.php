<?php 
namespace app\index\controller;
use app\common\lib\Util;
use app\common\lib\Redis;
use app\common\lib\Redis\Predis;

class Chart
{
	public function index() {
		foreach($_POST['httpServer']->connections as $fd) {
			// $_POST['httpServer']->push($fd, $fd);
			print_r($fd);
		}
	}
}

