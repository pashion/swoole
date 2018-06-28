<?php 
namespace app\common\lib\Redis;

// 封装单例redis,作用实例化一个类的时候，不管调用多少次，都永远只有一个实例, 不会有多个，这样就节省了内存分配开支。
// see https://www.zybuluo.com/phper/note/81802
class Predis
{
	public $redis = null; // 普通成员使用$this->获取, 可以被外部调用
	public static $instance = null; // 静态成员使用self::获取,且不能被外部调用

	public static function getInstance() {
		if (empty(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	private function __construct() {
		$this->redis = new \Redis();
		$connect = $this->redis->connect(config('redis.host'), config('redis.port'));
		if ($connect === false) {
			echo "redis connect error".PHP_EOL;
		}
	}

	/**
	 * @param $key string
	 * @param $value string
	 * @param $time int
	 * @return boolean
	 */
	public function set($key, $value, $time = 0) {
		if (empty($key)) {
			return '';
		}

		if (is_array($value)) {
			$value = json_encode($value);
		}

		if (intval($time) > 0) {
			return $this->redis->setex($key, $time, $value);
		}

		return $this->redis->set($key, $value);
	} 

	/**
	 * @param $key string
	 * @return string
	 */
	public function get($key) {
		if (empty($key)) {
			return '';
		}

		return $this->redis->get($key);
	}

	/**
	 * 删除键值
	 */
	public function del($key) {
		return $this->redis->del($key);
	}

	/**
	 * 添加有序集合
	 * @param $key
	 * @param $value
	 */
	public function sadd($key, $value) {
		return $this->redis->sadd($key, $value);
	}

	/**
	 * 删除有序集合
	 * @param $key
	 * @param $value
	 */
	public function srem($key, $value) {
		return $this->redis->srem($key, $value);
	}

	/**
	 * 获取集合的所有值
	 */
	public function smembers($key) {
		return $this->redis->smembers($key);
	}
}