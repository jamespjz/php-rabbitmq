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

namespace Jamespi\RabbitMQ\Producer;

use Jamespi\RabbitMQ\Common\Common;
use Jamespi\RabbitMQ\Common\Basic;
use Jamespi\RabbitMQ\Api\ProducerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
class ProducerServerApi extends Basic
{
    /**
     * 生产消息入队列
     * @param ProducerInterface $producerInterface
     * @param array $body
     * @return object|string
     */
    public function addMessage(ProducerInterface $producerInterface ,array $body)
    {
        $isAutoDelete = false;
        $isExclusive = false;
        $exchangeArguments = [];
        $isMandatory = ($this->config['is_ae']) ? false : $this->config['is_mandatory'];
        $exchangeName = (isset($body['exchange_name']) && !empty($body['exchange_name']))? $body['exchange_name'] : '';
        $queueName = (isset($body['queue_name']) && !empty($body['queue_name']))? $body['queue_name'] : '';
        //绑定key
        $routingKey = (isset($body['routing_key']) && !empty($body['routing_key']))? $body['routing_key'] : '';
        //交换器类型
        switch ($this->config['scene_mode']){
            case 1:
                $typeName = 'fanout';
                break;
            case 2:
                $typeName = 'direct';
                break;
            case 3:
                $typeName = 'topic';
                break;
            default:
                $typeName = 'fanout';
                break;
        }
        //交换器是否持久化
        $isExDurable = $this->config['is_exchange_persistence'];
        //队列是否持久化
        $isQueDurable = $this->config['is_queue_persistence'];
        //消息是否持久化
        $isMessageDurable = ($this->config['is_message_persistence']) ? (array('delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT)) : (array('delivery_mode' => AMQPMessage::DELIVERY_MODE_NON_PERSISTENT));
        //消息体
        $msg = new AMQPMessage($body['msg'], $isMessageDurable);
        //开启严格模式
        if($this->config['is_strict_mode']){
            $this->confirmCallback();
        }

        try{
            $model = $producerInterface->connection($this->connection)->channel($this->channel);
            //设置备份交换器
            if ($this->config['is_ae']){
                $exchangeArguments = new AMQPTable();
                $exchangeArguments->set('alternate-exchange', 'myAe');
                $model->exchangeDeclare(
                    'myAe',
                    'fanout',
                    $isExDurable,
                    $isAutoDelete,
                    []
                );
                //声明队列
                $model->queueDeclare('unroutedQueue', $isQueDurable, $isExclusive, $isAutoDelete);
                //队列绑定
                $model->queueBind('unroutedQueue', 'myAe', '');
            }
            //声明交换器
            if ($exchangeName){
                $model->exchangeDeclare(
                    $exchangeName,
                    $typeName,
                    $isExDurable,
                    $isAutoDelete,
                    $exchangeArguments
                );
            }
            //声明队列
            $model->queueDeclare($queueName, $isQueDurable, $isExclusive, $isAutoDelete);
            //队列绑定
            $model->queueBind($queueName, $exchangeName, $routingKey);
            //发送消息
            $model->basicPublish($msg, $exchangeName, $routingKey, $isMandatory, false);
            $this->close($model);
            $data = [
                'exchange_name' => $exchangeName,
                'queue_name' => $queueName,
                'routing_key' => $routingKey,
                'message' => $msg->body
            ];
            return Common::resultMsg('success', '生产内容成功', $data);
        }catch (\Exception $e){
            return Common::resultMsg('failed', '生产内容失败：'.$e->getMessage());
        }
    }

    /**
     * 断开连接
     * @param $model
     */
    protected function close($model)
    {
        $model->close();
    }

    protected function deadLetterExchange()
    {

    }
}