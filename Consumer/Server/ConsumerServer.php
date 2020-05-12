<?php
/**+----------------------------------------------------------------------
 * JamesPi Redis [php-redis]
 * +----------------------------------------------------------------------
 * RabbitMQ Consumer Service Business logic file
 * +----------------------------------------------------------------------
 * Copyright (c) 2020-2030 http://www.pijianzhong.com All rights reserved.
 * +----------------------------------------------------------------------
 * Author：PiJianZhong <jianzhongpi@163.com>
 * +----------------------------------------------------------------------
 */

namespace Jamespi\RabbitMQ\Consumer\Server;

use Jamespi\RabbitMQ\Api\ConsumerInterface;
class ConsumerServer implements ConsumerInterface
{
    /**
     * 链接信息
     * @var
     */
    protected $connection;
    /**
     * 信道信息
     * @var
     */
    protected $channel;

    /**
     * 创建链接
     * @param $connection
     * @return $this
     */
    public function connection($connection)
    {
        $this->connection = $connection;
        return $this;
    }

    /**
     * 创建信道
     * @param $channel
     * @return $this
     */
    public function channel($channel)
    {
        $this->channel = $channel;
        return $this;
    }

    /**
     * 声明队列
     * @param string $queueName 队列名称
     * @param bool $isDurable 是否持久化
     * @param bool $isExclusive 是否排它队列
     * @param bool $isAutoDelete 是否自动删除
     * @param $arguments 其他配置参数
     * @return array|null
     */
    public function queueDeclare(
        string $queueName,
        bool $isDurable = true,
        bool $isExclusive = false,
        bool $isAutoDelete = false,
        $arguments = []
    )
    {
        return $this->channel->queue_declare(
            $queueName,
            false,
            $isDurable,
            $isExclusive,
            $isAutoDelete,
            false,
            $arguments
        );
    }
}