<?php
namespace app\admin\controller;
use app\common\lib\ali\Sms;
use app\common\lib\task\Task;
use app\common\lib\Util;

class Image
{
    public function index()
    {
        $file = request()->file('file');
        $result = $file->move('../public/static/upload');
        if ($result) {
        	$data = [
        		'image' => config('live.host').$result->getSaveName(),
        	];

        	return Util::show(config('code.success'), 'ok', $data);

        } else {

        	return Util::show(config('code.error'), 'error');
        }
    }
}
