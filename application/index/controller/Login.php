<?php 
namespace app\index\controller;
use app\common\lib\Util;
use app\common\lib\Redis;
use app\common\lib\Redis\Predis;

class Login
{

	public function index() {
		$phoneNum = intval($_GET['phone_num']);
		$code = intval($_GET['code']);

		$redis = Predis::getInstance();
		$redisCode = $redis->get(Redis::smsKey($phoneNum));
		
		if ($redisCode == $code) {
			$data = [
				'user'    => $phoneNum,
				'srcKey'  => md5(Redis::userKey($phoneNum)),
				'time'    => time(),
				'isLogin' => true,
			];

			$redis->set(Redis::userKey($phoneNum), $data);

        	return Util::show(config('code.success'), 'ok', $data);
		} else {
        	return Util::show(config('code.error'), '登陆失败');
		}
	}
}