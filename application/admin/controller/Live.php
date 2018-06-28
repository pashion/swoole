<?php
namespace app\admin\controller;
use app\common\lib\ali\Sms;
use app\common\lib\task\Task;
use app\common\lib\Util;
use app\common\lib\Redis\Predis;
use app\common\lib\Reids;

class Live
{
	/** 
     * 后台录入直播赛况推送的客户端
     */
    public function push()
    {
        // 1,入库  2,推送
    	$teamArr = [
    		'0' => [
    			'name' => '解说员',
    			'logo' => '/imgs/team22.png',
    			],
    		'1' => [
    			'name' => '马刺',
    			'logo' => '/imgs/team1.png',
    			],
    		'4' => [
    			'name' => '火箭',
    			'logo' => '/imgs/team2.png',
    			]
    	];

    	$jdata = [
    		'type' 	  => isset($_GET['type']) ? $_GET['type'] : '',
    		'name' 	  => isset($_GET['team_id']) ? $teamArr[$_GET['team_id']]['name'] : '解说员',
    		'logo' 	  => isset($_GET['team_id']) ? $teamArr[$_GET['team_id']]['logo'] : '',
    		'content' => isset($_GET['content']) ? $_GET['content'] : '',
    		'image'   => isset($_GET['image']) ? $_GET['image'] : ''
    	];

    	$data = [
    		'method' => 'pushLive',
    		'data'  => $jdata
    	];

    	// 使用task异步推送
    	$_POST['httpServer']->task($data);

        return Util::show(config('code.success'), 'ok');
    }
}
