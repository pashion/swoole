<?php 

namespace app\common\lib;

class Redis {

	public static $smsPre = 'sms_';
	public static $userPre = 'user_';

	public static function smsKey($phone) {
		return self::$smsPre.$phone;
	}

	public static function userKey($phone) {
		return self::$userPre.$phone;
	}
}