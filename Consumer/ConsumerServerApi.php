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

use function foo\func;
use ReflectionClass;
use Jamespi\RabbitMQ\Common\Common;
use Jamespi\RabbitMQ\Common\Basic;
use Jamespi\RabbitMQ\Api\ConsumerInterface;
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
                $this->pullMessage($model, $autoAck, $qosNumber, $body, $callback);
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
     */
    protected function pushMessage($model, bool $autoAck, int $qosNumber, array $body, array $callback)
    {
        //声明队列
        $model->queueDeclare(
            $body['queque_name'],
            true,
            false,
            false,
            new AMQPTable([])
        );
        //回调函数
        $callback = function ($message) use($callback){
            try{
                call_user_func_array([$callback['class'], $callback['method']], [$message->body]);
                $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
            }catch (\Exception $e){
                return Common::resultMsg('failed', 'error：'.$e->getMessage());
            }
        };
        //信道上消费者所能保证最大未确认消息的数量
        $model->basic_qos(null, $qosNumber, null);
        //消费消息
        $model->basic_consume(
            $body['queque_name'],
            $body['consumer_tag'],
            $body['no_local'],
            $autoAck,
            $body['exclusive'],
            false,
            $callback,
            null,
            $body['argument']
        );
        while(count($model->callbacks)) {
            $model->wait();
        }
    }

    protected function pullMessage()
    {

    }

    public function refuseMessage()
    {

    }

    public function batchRefuseMessage()
    {

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