<?php
/**+----------------------------------------------------------------------
 * JamesPi Redis [php-redis]
 * +----------------------------------------------------------------------
 * RabbitMQ Consumer Service Api file
 * +----------------------------------------------------------------------
 * Copyright (c) 2020-2030 http://www.pijianzhong.com All rights reserved.
 * +----------------------------------------------------------------------
 * Author：PiJianZhong <jianzhongpi@163.com>
 * +----------------------------------------------------------------------
 */

namespace Jamespi\RabbitMQ\Api;


interface ConsumerInterface
{
    /**
     * 创建链接
     * @param $connection
     * @return $this
     */
    public function connection($connection);

    /**
     * 创建信道
     * @param $channel
     * @return $this
     */
    public function channel($channel);

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
    );

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
    );

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
    );

    /**
     * 关闭信道/连接
     */
    public function close();
}