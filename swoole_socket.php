<?php
require './lib/Predis.php';
require './lib/BaseClass.php';
require './lib/PushClass.php';


class WsServer{

	CONST HOST 			= "0.0.0.0";
    CONST PORT 			= 9501;


    /**
     * swoole websocket server 实例对象
     * [$ws description]
     * @var null
     */
	public $ws  	=	 null;
	public $redis 	=	 null;

	/**
	 * 初始化方法
	 * [__construct description]
	 */
	public function __construct(){
		$this->ws = new \swoole_websocket_server(self::HOST, self::PORT);
		$this->ws->set([
            'worker_num' => 4,
        ]);

        $this->ws->on("start", [$this, 'onStart']);
        $this->ws->on("open", [$this, 'onOpen']);
        $this->ws->on("message", [$this, 'onMessage']);
        $this->ws->on("workerstart", [$this, 'onWorkerStart']);
        $this->ws->on("request", [$this, 'onRequest']);
        $this->ws->on("close", [$this, 'onClose']);

        BaseClass::clearFd();

        $this->ws->start();
	}

	/**
     * 服务启动时定义进程别名
     * @param $server
     */
    public function onStart($server) {
        swoole_set_process_name("swoole ws");
    }

    /**
     * 连接池
     * @param $server
     * @param $worker_id
     */
    public function onWorkerStart($server,  $worker_id) {

    }

    /**
     * swoole http 入口
     * [onRequest description]
     * @param  [type] $request  [description]
     * @param  [type] $response [description]
     * @return [type]           [description]
     */
    public function onRequest($request, $response){

    	if($request->server['request_uri'] == '/favicon.ico') {
            $response->status(200);
            $response->end();
            return ;
        }

        $get = $request->get;

        $channel    = isset($get['channel']) && !empty($get['channel']) ? $get['channel'] : '';
        $noticetype = isset($get['noticetype']) && !empty($get['noticetype']) ? $get['noticetype'] : '';
        $msgtype    = isset($get['msgtype']) && !empty($get['msgtype']) ? $get['msgtype'] : '';
        $message    = isset($get['message']) && !empty($get['message']) ? $get['message'] : '';
        $params     = isset($get['params'])  && !empty($get['params'])  ? $get['params']  : '';


        //获取推送信息
        if(!empty($channel) && !empty($noticetype) && !empty($msgtype) && !empty($message) && !empty($params)){
            $pushClass = new PushClass($this->ws);
            $pushClass->pushMessage($channel,$noticetype,$msgtype,$message,$params);
        }

        $data = ['code'=>200,'message'=>'','data'=>[]];

        $response->end(json_encode($data));
    }

    /**
     * 监听ws连接事件
     * @param $ws
     * @param $request
     */
    public function onOpen($serv, $request) {
        $get        = $request->get;

        $fd         = $request->fd;
        $admin_id   = $get['admin_id'];
        $channel    = $get['channel'];

        $obj = new BaseClass();
        $obj ->onOpen($fd,$admin_id,$channel);


        //调试模块
        $content = [
            'noticetype'    =>'connect',
            'msgtype'       =>1,
            'message'       =>"[{$request->fd}] 已连接消息提示服务",
            'params'        =>[]
        ];

    	$serv->push($request->fd,json_encode($content));
    }

    /**
     * 监听ws消息事件
     * @param $ws
     * @param $frame
     */
    public function onMessage($ws, $frame) {
        
        foreach ($this->ws->connections as $fd) {
            $s = $this->ws->connection_info($fd);
            if($s['websocket_status'] == 3){
                $this->ws->push($fd, $frame->data);
            }
        }

        // echo "receive from {$frame->fd}:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}\n";
        $ws->push($frame->fd, "server-push:".date("Y-m-d H:i:s"));
    }

    /**
     * close
     * @param $ws
     * @param $fd
     */
    public function onClose($ws, $fd) {
        $obj = new BaseClass();
        $obj ->onClose($fd);
    }

}


new WsServer();