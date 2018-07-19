<?php

/**
 * Description of AdminControllor
 *
 * @author dell
 */
class PushClass {

	public $redis    = null;
	public $wsServer = null;

	public function __construct($wsServer){
		$this->wsServer = $wsServer;
	}


	/**
	* 获取推送的渠道对应的的客户端fd
	*/
	public function getChannelFd($channel){
		$fdArr = [];
		echo '获取推送fd列表:'.$channel;

		$redis = Predis::getInstance();


		if($arr = $redis->redis->smembers($channel)){
			$fdArr = $arr;
		}

	 	return $fdArr;
	}

	/**
	* 推送消息
	*/
	public function pushMessage($channel,$noticetype,$msgtype,$message,$params){

		$content = [
			'noticetype'  =>$noticetype,   #渠道下的消息别名
			'msgtype'     =>$msgtype,	   #渠道下的消息类型
			'message'     =>$message,	   #渠道下的消息内容
			'params'      =>$params	       #渠道下的消息附加参数
		];

		$content = json_encode($content);
		if($fdArray = $this->getChannelFd($channel)){
			foreach ($fdArray as $fd) {
				#判断WebSocket客户端是否存在，并且状态为Active状态。
				if($this->wsServer->exist($fd)){
					$this->wsServer->push($fd, $content);
				}
	        }


			/**$wsServer = $this->wsServer;
	        $process = new swoole_process(function(swoole_process $worker) use($wsServer,$content,$fdArray) {

		        foreach ($fdArray as $fd) {
					#判断WebSocket客户端是否存在，并且状态为Active状态。
					if($wsServer->exist($fd)){
						$wsServer->push($fd, $content);
					}
		        }
		    }, false);
		    // $process->start();*/

		    return true;
		}
	}
}