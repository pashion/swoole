<?php 
namespace app\common\lib;

class Util
{

	public static function show($status = 0, $message = '', $data = []) {
		$result = [
			'status'    => $status,
			'message'   => $message,
			'data'		=> $data
		];

		echo json_encode($result);
	}
}