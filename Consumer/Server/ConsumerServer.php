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

    /**
     * 消费确认
     * @param $deliveryTag
     * @return mixed
     */
    public function basicAck($delivery)
    {
        return $delivery['channel']->basic_ack($delivery['delivery_tag']);
    }

    /**
     * 消息未确认上限
     * @param $prefetch_size 未确认消息总体大小（B）0表示没有上限
     * @param $prefetch_count 信道未确认消息上限
     * @param $a_global 全局配置（信道上全部消费者都得遵从/信道上新消费者）
     * @return mixed
     */
    public function basicQos($prefetch_size, $prefetch_count, $a_global)
    {
        return $this->channel->basic_qos($prefetch_size, $prefetch_count, $a_global);
    }

    /**
     * 消费消息（推模式）
     * @param string $queue 队列名称
     * @param string $consumer_tag 消费者标识
     * @param bool $no_local 设置为true表示不能同一个connection中生产者发送的消息传给这个connection的消费者（可不加）
     * @param bool $no_ack 是否自动确认消费
     * @param bool $exclusive 排它性
     * @param bool $nowait 是否不用等待回复
     * @param $callback 回调函数
     * @param $ticket
     * @param array $arguments 其他参数
     * @return mixed
     */
    public function basicConsume(
        string $queue,
        string $consumer_tag,
        bool $no_local,
        bool $no_ack,
        bool $exclusive,
        bool $nowait,
        $callback,
        $ticket = null,
        array $arguments
    )
    {
        return $this->channel->basic_consume(
            $queue,
            $consumer_tag,
            $no_local,
            $no_ack,
            $exclusive,
            $nowait,
            $callback,
            $ticket,
            $arguments
        );
    }

    /**
     * 消费消息（拉模式）
     * @param string $queue
     * @param bool $no_ack
     * @param null $ticket
     * @return mixed
     */
    public function basicGet(string $queue = '', bool $no_ack = false, $ticket = null)
    {
        return $this->channel->basic_get($queue, $no_ack, $ticket);
    }

    /**
     * 消息拒绝 - 单条
     * @param string $deliveryTag 消息标识
     * @param bool $requeue true重新入队，false进入死信队列
     * @return mixed
     */
    public function basicReject(string $deliveryTag, bool $requeue)
    {
        return $this->channel->basic_reject($deliveryTag, $requeue);
    }

    /**
     * 消息拒绝 - 批量
     * @param string $deliveryTag 消息标识
     * @param bool $requeue true重新入队，false进入死信队列
     * @param bool $multiple false单条拒绝$deliveryTag，true拒绝$deliveryTag之前所有未确认消息
     * @return mixed
     */
    public function basicNack(string $deliveryTag, bool $multiple = false, bool $requeue = false)
    {
        return $this->channel->basic_nack($deliveryTag, $multiple, $requeue);
    }

    public function wait(){
        return $this->channel->wait();
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