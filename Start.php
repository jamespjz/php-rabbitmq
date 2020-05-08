<?php
/**+----------------------------------------------------------------------
 * JamesPi Redis [php-redis]
 * +----------------------------------------------------------------------
 * RabbitMQ Basic Service Configuration file
 * +----------------------------------------------------------------------
 * Copyright (c) 2020-2030 http://www.pijianzhong.com All rights reserved.
 * +----------------------------------------------------------------------
 * Author：PiJianZhong <jianzhongpi@163.com>
 * +----------------------------------------------------------------------
 */

namespace Jamespi\RabbitMQ;

use ReflectionClass;
use Jamespi\RabbitMQ\Producer\ProducerServerApi;
use Jamespi\RabbitMQ\Consumer\ConsumerServerApi;
use Jamespi\RabbitMQ\Producer\Server\ProducerServer;
class Start
{
    /**
     * 服务配置项
     * @var mixed
     */
    protected $config = [];
    /**
     * 业务场景类别
     * @var mixed
     */
    protected $type = 1;
    /**
     * 服务实例化对象
     * @var object
     */
    protected $model;

    public function __construct()
    {
        $this->config = require_once __DIR__.'/Config/Config.php';
    }

    /**
     * 启动服务
     * @param int $type 服务类型
     * @param array $config 服务配置
     * @return $this
     */
    public function run(int $type, array $config)
    {
        $this->type = $type;
        if (!empty($config)) $this->config = array_merge($this->config, $config);
        switch ($type){
            case 1:
                $this->model = (new ProducerServerApi($this->config));
                break;
            case 2:
                $this->model = (new ConsumerServerApi($this->config));
                break;
        }

        return $this;
    }

    public function __call(string $name, array $arguments)
    {
        // TODO: Implement __call() method.
        $producerServer = new ProducerServer();
        try{
            $class = new ReflectionClass($this->model);
            $class->getMethod($name);
            $data = call_user_func_array([$this->model, $name], [$producerServer, $arguments[0]]);
            $data = json_decode($data, true);
            if ($data['status'] == 'success')
                return json_encode(['status'=>'success', 'msg'=>'调用成功！', 'data'=>$data['data']]);
            else
                return json_encode(['status'=> 'failed', 'msg'=>'Error：'.$data['msg'], 'data'=>$data['data']]);
        }catch (\Exception $e){
            return json_encode(['status'=> 'failed', 'msg'=>'Error：'.$e->getMessage()]);
        }
    }

}