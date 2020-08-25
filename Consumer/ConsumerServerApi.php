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

namespace Jamespi\RabbitMQ\Consumer;

use ReflectionClass;
use Jamespi\RabbitMQ\Common\Common;
use Jamespi\RabbitMQ\Common\Basic;
use Jamespi\RabbitMQ\Api\ConsumerInterface;
use PhpAmqpLib\Wire\AMQPTable;
class ConsumerServerApi extends Basic
{
    /**
     * 消费消息
     * @param ConsumerInterface $consumerInterface
     * @param array $body
     */
    public function consumerMessage(ConsumerInterface $consumerInterface, array $body)
    {
        $autoAck = ($this->config['is_autoAck']) ? $this->config['is_autoAck'] : false;
        $consumerMode = ($this->config['consumer_mode']);
        $qosNumber = $body['qos_number']??1;
        if (!(isset($body['queque_name']) && is_string($body['queque_name']) && !empty($body['queque_name'])))
            return Common::resultMsg('failed', '队列名称参数非法');
        if (isset($body['callback']) && is_array($body['callback']) && !empty($body['callback'])){
            if (
                !(isset($body['callback']['namespace']) && !empty($body['callback']['namespace'])) ||
                !(isset($body['callback']['action']) && !empty($body['callback']['action']))
            )
                return Common::resultMsg('failed', '回调类或方法参数为空');
            try{
                $class = new ReflectionClass($body['callback']['namespace']);
                $class->getMethod($body['callback']['action']);
                $callback = [
                    'class' => $body['callback']['namespace'],
                    'method' => $body['callback']['action']
                ];
            }catch (\Exception $e){
                return Common::resultMsg('failed', '回调类或方法不存在');
            }

        }else{
            return Common::resultMsg('failed', '回调函数参数为空');
        }
        $model = $consumerInterface->connection($this->connection)->channel($this->channel);
        switch ($consumerMode){
            case 1:
                $this->pushMessage($model, $autoAck, $qosNumber, $body, $callback);
                break;
            case 2:
                $this->pullMessage($model, $autoAck, $body);
                break;
            default:
                $this->pushMessage($model, $autoAck, $qosNumber, $body, $callback);
                break;
        }

        $this->close($model);
    }

    /**
     * 消费消息-推模式
     * @param $model
     * @param bool $autoAck
     * @param int $qosNumber
     * @param array $body
     * @param array $callback
     * @return object|string
     */
    protected function pushMessage($model, bool $autoAck, int $qosNumber, array $body, array $callback)
    {
        $consumerTag = (isset($body['consumer_tag'])&&!empty($body['consumer_tag']))?$body['consumer_tag']:'';
        $noLocal = (isset($body['no_local'])&&!empty($body['no_local']))?$body['no_local']:false;
        $exclusive = (isset($body['exclusive'])&&!empty($body['exclusive']))?$body['exclusive']:false;
        $argument = (isset($body['argument'])&&!empty($body['argument']))?$body['argument']:[];

        try {
            //回调函数
            $callback = function ($message) use ($callback, $autoAck, $model) {
                try {
                    call_user_func_array([new $callback['class'], $callback['method']], [$message->body]);
                    if (!$autoAck)
                        $model->basicAck($message->delivery_info);
//                    return Common::resultMsg('success', 'message:'.$message.'consumption is successful' );
                } catch (\Exception $e) {
                    return Common::resultMsg('failed', 'error：' . $e->getMessage());
                }
            };
            //信道上消费者所能保证最大未确认消息的数量
            $model->basicQos(null, $qosNumber, null);
            //消费消息
            $model->basicConsume(
                $body['queque_name'],
                $consumerTag,
                $noLocal,
                $autoAck,
                $exclusive,
                false,
                $callback,
                null,
                $argument
            );
            while (count($this->channel->callbacks)) {
                $this->channel->wait();
            }
        }catch (\Exception $e){
            return Common::resultMsg('failed', 'Error：'.$e->getMessage());
        }
    }

    /**
     * 消费消息-拉模式
     * @param $model
     * @param bool $autoAck
     * @param array $body
     * @return object|string
     */
    protected function pullMessage($model, bool $autoAck, array $body)
    {
        try{
            //声明队列
            $model->queueDeclare(
                $body['queque_name'],
                true,
                false,
                false,
                new AMQPTable([])
            );
            //消费消息
            $result = $model->basicGet($body['queque_name'], $autoAck);
            if (!$autoAck)  $model->basicAck($result->delivery_info);
            return Common::resultMsg('success', "message：".$result->delivery_info['delivery_tag']." consumption is successful");
        }catch (\Exception $e){
            return Common::resultMsg('failed', 'Error：'.$e->getMessage());
        }
    }

    /**
     * 消息拒绝 - 单条
     * @param $model
     * @param string $deliveryTag
     * @param bool $requeue
     */
    protected function refuseMessage($model, string $deliveryTag, bool $requeue)
    {
        $model->basicReject($deliveryTag, $requeue);
    }

    /**
     * 消息拒绝 - 批量
     * @param $model
     * @param string $deliveryTag
     * @param bool $multiple
     * @param bool $requeue
     */
    protected function batchRefuseMessage($model, string $deliveryTag, bool $multiple, bool $requeue)
    {
        $model->basicNack($deliveryTag, $multiple, $requeue);
    }

    /**
     * 断开连接
     * @param $model
     */
    protected function close($model)
    {
        $model->close();
    }
}