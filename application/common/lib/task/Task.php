<?php 

namespace app\common\lib\task;
use app\common\lib\ali\Sms;
use app\common\lib\Util;
use app\common\lib\Redis;
use app\common\lib\Redis\Predis;

// can't use async-io in task process
class Task
{
	/**
	 * task任务发送邮件
	 * @param $data
	 * @param $serv ws服务器
	 */
	public static function sendSms($data, $serv) {
		try {
			$response = Sms::sendSms($data['phone'], $data['code']);

	        if ($response->Code === "OK") {

	        	// $redis = new \swoole_redis();
	        	// $redis->connect(config('redis.host'), config('redis.port'), function($redis, $result) use($data) {
	        	// 	if ($result === false) {
	        	// 		return Util::show(config('code.error'), 'connect redis error');
	        	// 	}

	        	// 	$redis->setex(Redis::smsKey($data['phone']), config('redis.time_out'), $data['code'], function($redis, $result) {
	        	// 		if ($result === 'OK') {
	        	// 			return json_encode(Util::show(config('code.success'), 'success'));
	        	// 		} else {
	        	// 			return json_encode(Util::show(config('code.error'), 'set redis error'));
	        	// 		}
	        	// 	});

	        	// 	$redis->close();
	        	// });

	        	$redis = Predis::getInstance();
	        	return $redis->set(Redis::smsKey($data['phone']), $data['code'], config('redis.time_out'));

	        } else {
	        	return false;
	        }

		} catch (Exception $e) {
			return false;
		}
	}

	/**
	 * 推送直播赛况
	 */
	public static function pushLive($data, $serv) {
		$clients = Predis::getInstance()->smembers(config('redis.live_game_key'));
		var_dump($data['data']);
        foreach ($clients as $client) {
        	$serv->push($client, json_encode($data['data']));
        }
	}
}