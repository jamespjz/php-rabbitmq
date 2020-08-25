<?php
/**+----------------------------------------------------------------------
 * JamesPi Redis [php-redis]
 * +----------------------------------------------------------------------
 * RabbitMQ Basic Service Business logic file
 * +----------------------------------------------------------------------
 * Copyright (c) 2020-2030 http://www.pijianzhong.com All rights reserved.
 * +----------------------------------------------------------------------
 * Author：PiJianZhong <jianzhongpi@163.com>
 * +----------------------------------------------------------------------
 */

namespace Jamespi\RabbitMQ\Common;

use PhpAmqpLib\Connection\AMQPStreamConnection;
abstract class Basic
{
    /**
     * 服务实例化连接
     * @var AMQPStreamConnection
     */
    protected $connection;
    /**
     * 服务实例化信道
     * @var \PhpAmqpLib\Channel\AMQPChannel
     */
    protected $channel;
    /**
     * 配置参数
     * @var
     */
    protected $config;

    public function __construct($config)
    {
        $this->config = $config;
        $basic = $config['Basic'];
        try{
            $this->connection = new AMQPStreamConnection($basic['host'], $basic['port'], $basic['username'], $basic['password'], $basic['vhost']);
        }catch (\Exception $e){
            return Common::resultMsg('failed', '连接失败：'.$e->getMessage());
        }
        $this->channel = $this->connection->channel();
    }

}