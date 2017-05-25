<?php
// +----------------------------------------------------------------------
// | PHP [ just do it ]
// +----------------------------------------------------------------------
// | Copyright (c) 2017~2017 http://www.jyphp.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Albert <albert_p@foxmail.com>
// +----------------------------------------------------------------------
namespace JYPHP\Core\Server;

use JYPHP\Core\Http\Request;
use JYPHP\Core\Interfaces\Application\IApplication;
use JYPHP\Core\Interfaces\Server\IHttpServer;
use JYPHP\Core\Interfaces\Server\IServer;

class HttpServer implements IServer,IHttpServer
{
    protected $version = "JYPHP_SERVER 1.0 BETA";
    protected $default_host = "0.0.0.0";
    protected $default_port = "9999";

    /**
     * 指定启动的worker进程数。
     * swoole是master-> n * worker的模式，开启的worker进程数越多，server负载能力越大，
     * 但是相应的server占有的内存也会更多。
     * 同时，当worker进程数过多时，进程间切换带来的系统开销也会更大。
     * 因此建议开启的worker进程数为cpu核数的1-4倍。
     * @var int
     */
    protected $work_num = 3;

    /**
     * 每个worker进程允许处理的最大任务数。
     * 设置该值后，每个worker进程在处理完max_request个请求后就会自动重启。
     * 设置该值的主要目的是为了防止worker进程处理大量请求后可能引起的内存溢出。
     * @var int
     */
    protected $max_request = 1000;

    /**
     * 服务器允许维持的最大TCP连接数
     * 设置此参数后，当服务器已有的连接数达到该值时，新的连接会被拒绝。
     * 另外，该参数的值不能超过操作系统ulimit -n的值，同时此值也不宜设置过大，
     * 因为swoole_server会一次性申请一大块内存用于存放每一个connection的信息。
     * @var int
     */
    protected $max_conn = 1000;

    /**
     * 指定数据包分发策略。
     * 1 => 轮循模式，收到会轮循分配给每一个worker进程
     * 2 => 固定模式，根据连接的文件描述符分配worker。这样可以保证同一个连接发来的数据只会被同一个worker处理
     * 3 => 抢占模式，主进程会根据Worker的忙闲状态选择投递，只会投递给处于闲置状态的Worker
     * @var int
     */
    protected $dispatch_mode = 2;

    /**
     * 设置程序进入后台作为守护进程运行。
     * @var bool
     */
    protected $daemon = false;

    /**
     * 上传文件时的临时目录
     * @var string
     */
    protected $upload_tmp_dir;

    /**
     * @var \swoole_http_server
     */
    protected $swoole_server;

    /**
     * 服务器运行的应用程序
     * @var IApplication
     */
    protected $application;

    /**
     * 新建swoole http 服务器
     * @return \swoole_http_server
     */
    private function create_server() : \swoole_http_server
    {
        $swoole_server = new \swoole_http_server($this->default_host,$this->default_port);
        $configure = [
            'worker_num' => $this->work_num,
            'max_request' => $this->max_request,
            'max_conn' => $this->max_conn,
            'dispatch_mode' => $this->dispatch_mode,
            'debug_mode'=> 1,
            'daemonize' => $this->daemon,
            'heartbeat_check_interval' => 60
        ];
        $swoole_server->set($configure);
        return $swoole_server;
    }

    private function setGlobal($req){
        //把$req->server请求信息注入$_SERVER
        if(!empty($req->get))
            $_GET = $req->get;
        if(!empty($req->post))
            $_POST = $req->post;
        if(!empty($req->cookie))
            $_COOKIE = $req->cookie;
        if(!empty($req->files))
            $_FILES = $req->files;
        if(!empty($req->server)){
            foreach($req->server as $key => $value){
                $_SERVER[strtoupper($key)] = $value;
            }
        }
        if(!empty($req->header)){
            foreach($req->header as $key => $value){
                $_SERVER['HTTP_'.strtoupper($key)] = $value;
            }
        }
    }

    /**
     * HttpServer constructor.
     * @param IApplication $application
     */
    public function __construct(IApplication $application)
    {
        $this->application = $application;
    }

    /**
     * 运行服务器
     * @param null $swoole_server
     */
    public function run ($swoole_server = null){
        if(empty($swoole_server) || (!$swoole_server instanceof \swoole_http_server)){
            $swoole_server = $this->create_server();
        }
        $this->swoole_server = $swoole_server;
        $this->swoole_server->on('Request',[$this,'onRequest']);
        $this->swoole_server->on('Close',[$this,'onClose']);
        $this->swoole_server->start();
    }

    public function onRequest($req, $res)
    {
        $this->setGlobal($req);
        $this->application->instance('request',$req);
        $this->application->instance('response',$res);
        $this->application->instance(\Swoole\Http\Response::class,$res);
        $response = $this->application->handle(Request::createFromGlobals());
        $response->header("Server",$this->version());
        $response->send();
    }

    public function setWorkNum(int $num)
    {
        $this->work_num = $num;
        return $this;
    }

    public function setRequestMax(int $max)
    {
        $this->max_request = $max;
        return $this;
    }

    public function onClose()
    {

    }

    public function version()
    {
        return $this->version;
    }

    public function onShutdown()
    {
        echo "服务器成功关闭";
    }
}