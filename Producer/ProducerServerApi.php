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
        $exchangeName = (isset($body['exchange_name']) && !empty($body['exchange_name']))? $body['exchange_name'] : '';
        $queueName = (isset($body['queue_name']) && !empty($body['queue_name']))? $body['queue_name'] : '';
        //绑定key
        $routingKey = (isset($body['routing_key']) && !empty($body['routing_key']))? $body['routing_key'] : '';
        //消息体
        $msg = $body['msg'];
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
        //是否持久化
        $isDurable = $this->config['is_persistence'];

        try{
            $model = $producerInterface->connection($this->connection)->channel($this->channel);
            //声明交换器
            if ($exchangeName){
                $model->exchangeDeclare($exchangeName, $typeName, $isDurable, $isAutoDelete);
            }
            //声明队列
            $model->queueDeclare($queueName, $isDurable, $isExclusive, $isAutoDelete);
            //发送消息
            $model->basicPublish($msg, $exchangeName, $routingKey);
            return Common::resultMsg('success', '生产内容成功');
        }catch (\Exception $e){
            return Common::resultMsg('failed', '生产内容失败：'.$e->getMessage());
        }
    }

}