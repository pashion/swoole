<?php
namespace app\index\controller;
use app\common\lib\ali\Sms;
use app\common\lib\Util;
use app\common\lib\Redis;
use app\common\lib\task\Task;

class Send
{
    /**
     * 发送验证码
     */
    public function index()
    {
        // $phoneNum = request()->get('phone_num', 0 , 'intval');
        $phoneNum = intval($_GET['phone_num']);

        if (!$this->isMobile($phoneNum)) {
        	return Util::show(config('code.error'), 'error');
        }

        try {
        	$code = rand(10000, 99999);

            $data = [
                'method' => 'sendSms',
                'phone'  => $phoneNum,
                'code'   => $code,
            ];

            $_POST['httpServer']->task($data);

        	//$response = Sms::sendSms($phoneNum, $code);

        } catch (Exception $e) {
        	return Util::show(config('code.error'), '发送短信失败');
        }

		return Util::show(config('code.success'), 'success');
    }

	/** 
	 * 验证手机号是否正确
	 * @author pashioner
	 * @param $mobile int
     * @return boolean
	 */  
	function isMobile($mobile) {  
	    if (!is_numeric($mobile)) {  
	        return false;  
	    }  
	    return preg_match('#^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$#', $mobile) ? true : false;  
	}  

}
