<?php

/**
 * Description of AdminControllor
 *
 * @author dell
 */
class BaseClass {

	/**
     * Swoole Socket 连接处理
     */
	public function onOpen($fd,$admin_id,$channel){
		$redis = Predis::getInstance();

		$redis->redis->hset('websocket_connections',$fd,$channel);
		$redis->redis->sadd($channel,$fd);
	}

	/**
     * Swoole Socket 断开连接处理
     */
	public function onClose($fd){
		$redis = Predis::getInstance();

		$channel = $redis->redis->hget('websocket_connections',$fd);
		if($channel){
			$redis->redis->srem($channel,$fd);
			$redis->redis->hdel('websocket_connections',$fd);
		}
	}

	/**
     * 清除redis 缓存
     */
	public static function clearFd(){
		$redis = Predis::getInstance();
		$redis->redis->del('websocket_connections');
		$redis->redis->del('adminnotice');
	}

}