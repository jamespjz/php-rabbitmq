<?php
/**+----------------------------------------------------------------------
 * JamesPi Redis [php-redis]
 * +----------------------------------------------------------------------
 * RabbitMQ Producer Service Business logic file
 * +----------------------------------------------------------------------
 * Copyright (c) 2020-2030 http://www.pijianzhong.com All rights reserved.
 * +----------------------------------------------------------------------
 * Author：PiJianZhong <jianzhongpi@163.com>
 * +----------------------------------------------------------------------
 */

namespace Jamespi\RabbitMQ\Producer\Server;

use Jamespi\RabbitMQ\Api\ProducerInterface;
class ProducerServer implements ProducerInterface
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
     * 声明交换器
     * @param string $exchangeName 交换器名称
     * @param string $typeName 交换器类型
     * @param bool $isDurable 是否持久化
     * @param bool $isAutoDelete 是否自动删除
     * @return mixed|null
     */
    public function exchangeDeclare(
        string $exchangeName = '',
        string $typeName = 'direct',
        bool $isDurable = true,
        bool $isAutoDelete = false
    )
    {

        return $this->channel->exchange_declare($exchangeName, $typeName, false, $isDurable, $isAutoDelete);
    }

    /**
     * 声明队列
     * @param string $queueName 队列名称
     * @param bool $isDurable 是否持久化
     * @param bool $isExclusive 是否排它队列
     * @param bool $isAutoDelete 是否自动删除
     * @return array|null
     */
    public function queueDeclare(
        string $queueName,
        bool $isDurable = true,
        bool $isExclusive = false,
        bool $isAutoDelete = false
    )
    {
        return $this->channel->queue_declare($queueName, false, $isDurable, $isExclusive, $isAutoDelete);
    }

    /**
     *  队列与交换器绑定
     * @param string $queueName 队列名称
     * @param string $exchangeName 交换器名称
     * @param string $routing_key 绑定键
     * @return mixed|null
     */
    public function queueBind(string $queueName, string $exchangeName, string $routing_key)
    {
        return $this->channel->queue_bind($queueName, $exchangeName, $routing_key);
    }

    /**
     * destination交换器绑定到source交换器，消息从source到destination
     * @param string $destination 交换器名称
     * @param string $source 交换器名称
     * @param string $routing_key 绑定key
     * @return mixed|null
     */
    public function exchangeBind(string $destination, string $source, string $routing_key)
    {
        return $this->channel->exchange_bind($destination, $source, $routing_key);
    }

    /**
     * 发送消息
     * @param string $msg 消息体信息
     * @param string $exchangeName 交换器名称
     * @param string $routing_key 绑定key
     * @param bool $mandatory
     * @param bool $immediate
     * @return mixed
     */
    public function basicPublish(
        string $msg,
        string $exchangeName,
        string $routing_key,
        bool $mandatory,
        bool $immediate
    )
    {
        return $this->channel->basic_publish($msg, $exchangeName, $routing_key, $mandatory, $immediate);
    }

    /**
     * 关闭信道/连接
     */
    public function close()
    {
        $this->channel->close();
        $this->connection->close();
    }
}