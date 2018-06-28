<?php
namespace app\index\controller;
use app\common\lib\ali\Sms;
use app\common\lib\task\Task;

class Index
{
    public function index()
    {
        return '';
    }

    public function hello($name = 'ThinkPHP5')
    {
        return 'hello ge diao,' . $name;
    }

    public function singwa()
    {
    	return 'singwa';
    }

    public function sms()
    {
    	$result = Sms::sendSms('18814188421', '88888888');
    }
}
