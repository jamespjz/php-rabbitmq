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
     * @param bool $isInternal 是否内置
     * @param $arguments 其他配置参数
     * @return mixed|null
     */
    public function exchangeDeclare(
        string $exchangeName = '',
        string $typeName = 'direct',
        bool $isDurable = true,
        bool $isAutoDelete = false,
        bool $isInternal = false,
        $arguments = []
    )
    {

        return $this->channel->exchange_declare(
            $exchangeName,
            $typeName,
            false,
            $isDurable,
            $isAutoDelete,
            $isInternal,
            false,
            $arguments
        );
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
     *  队列与交换器绑定
     * @param string $queueName 队列名称
     * @param string $exchangeName 交换器名称
     * @param string $routing_key 绑定键
     * @param array $arguments 其他配置参数
     * @return mixed|null
     */
    public function queueBind(
        string $queueName,
        string $exchangeName,
        string $routing_key,
        array $arguments = []
    )
    {
        return $this->channel->queue_bind(
            $queueName,
            $exchangeName,
            $routing_key,
            false,
            $arguments
        );
    }

    /**
     * destination交换器绑定到source交换器，消息从source到destination
     * @param string $destination 交换器名称
     * @param string $source 交换器名称
     * @param string $routing_key 绑定key
     * @param array $arguments 其他配置参数
     * @return mixed|null
     */
    public function exchangeBind(
        string $destination,
        string $source,
        string $routing_key,
        array $arguments = []
    )
    {
        return $this->channel->exchange_bind(
            $destination,
            $source,
            $routing_key,
            false,
            $arguments
        );
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
        $msg,
        string $exchangeName,
        string $routing_key,
        bool $mandatory,
        bool $immediate
    )
    {
        return $this->channel->basic_publish(
            $msg,
            $exchangeName,
            $routing_key,
            $mandatory,
            $immediate
        );
    }

    /**
     * Sets callback for basic_return
     *
     * @param  callable $callback
     * @throws \InvalidArgumentException if $callback is not callable
     */
    public function setReturnListener($callBack)
    {
        return $this->channel->set_return_listener($callBack);
    }

    /**
     * 开启事务
     * @return mixed
     */
    public function txSelect()
    {
        return $this->channel->tx_select();
    }

    /**
     * 事务提交
     * @return mixed
     */
    public function txCommit()
    {
        return $this->channel->tx_commit();
    }

    /**
     * 事务回滚
     * @return mixed
     */
    public function txRollback()
    {
        return $this->channel->tx_rollback();
    }

    /**
     * 开启信道确认模式
     * @return mixed
     */
    public function confirmSelect()
    {
        return $this->channel->confirm_select();
    }

    /**
     * 普通同步confirm消息确认模式
     * @return mixed
     */
    public function actionProducerConfirm()
    {
        return $this->channel->wait_for_pending_acks();
    }

    /**
     * 批量同步confirm消息确认模式
     * @return mixed
     */
    public function actionProducerConfirms()
    {
        return $this->channel->wait_for_pending_acks_returns();
    }

    /**
     * 异步confirm消息确认模式
     * @return mixed
     */
    public function asynProducerConfirm()
    {
        return $this->channel->set_ack_handler();
    }

    /**
     * 批量异步confirm消息确认模式
     * @return mixed
     */
    public function asynProducerConfirms()
    {
        return $this->channel->set_nack_handler();
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