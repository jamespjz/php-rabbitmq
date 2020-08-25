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
    public function addMessage(ProducerInterface $producerInterface, array $body)
    {
        $queueArguments = [];
        $isAutoDelete = false;
        $isExclusive = false;
        $exchangeArguments = [];
        $isMandatory = ($this->config['is_ae']) ? false : $this->config['is_mandatory'];
        $exchangeName = (isset($body['exchange_name']) && !empty($body['exchange_name']))? $body['exchange_name'] : '';
        $queueName = (isset($body['queue_name']) && !empty($body['queue_name']))? $body['queue_name'] : '';
        //绑定key
        $routingKey = (isset($body['routing_key']) && !empty($body['routing_key']))? $body['routing_key'] : '';
        //交换器类型
        switch ($body['scene_mode']){
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
        $isExDurable = $body['is_exchange_persistence']??true;
        //队列是否持久化
        $isQueDurable = $body['is_queue_persistence']??true;
        //消息是否持久化
        $isMessageDurable = ($body['is_message_persistence']) ? (array('delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT)) : (array('delivery_mode' => AMQPMessage::DELIVERY_MODE_NON_PERSISTENT));
        //设置消息TTL
        if($body['set_message_ttl_type']==2){
            $isMessageDurable = array_merge($isMessageDurable, ['expiration' => $body['message_ttl']]);
        }
        //消息体
        $msg = new AMQPMessage($body['msg'], $isMessageDurable);

        try{
            $model = $producerInterface->connection($this->connection)->channel($this->channel);
        }catch (\Exception $error){
            return Common::resultMsg('failed', '服务器连接失败：'.$error->getMessage());
        }

        try{
            //开启死信队列
            if ($this->config['is_dead_exchange']){
                $queueArguments['x-dead-letter-exchange'] = $body['dead_exchange']['dlx_exchange'];
                $queueArguments['x-dead-letter-routing-key'] = $body['dead_exchange']['dlx_routekey'];
                $dlxParams = [
                    'dlx_exchange' => $body['dead_exchange']['dlx_exchange'],
                    'dlx_type' => $body['dead_exchange']['dlx_type'],
                    'dlx_durable' => $body['dead_exchange']['dlx_durable']??true,
                    'dlx_autoDelete' => $body['dead_exchange']['dlx_autoDelete']??false,
                    'dlx_internal' => $body['dead_exchange']['dlx_internal']??false,
                    'dlx_argument' => $body['dead_exchange']['dlx_argument']??[],
                    'dlx_queue' => $body['dead_exchange']['dlx_queue'],
                    'dlx_queue_durable' => $body['dead_exchange']['dlx_queue_durable']??true,
                    'dlx_queue_exclusive' => $body['dead_exchange']['dlx_queue_exclusive']??false,
                    'dlx_queue_autoDelete' => $body['dead_exchange']['dlx_queue_autoDelete']??false,
                    'dlx_queue_argument' => $body['dead_exchange']['dlx_queue_argument']??[],
                    'dlx_routekey' => $body['dead_exchange']['dlx_routekey']
                ];
                $this->deadLetterExchange($model, $dlxParams);
			}
            //设置备份交换器
            if ($this->config['is_ae']){
                $aeParams = [
                    'ae_exchange' => $body['back_up_exchange']['ae_exchange']??'myAe',
                    'ae_type' => $body['back_up_exchange']['ae_exchange_type']??'fanout',
                    'ae_durable' => $body['back_up_exchange']['ae_durable']??true,
                    'ae_autoDelete' => $body['back_up_exchange']['ae_autoDelete']??false,
                    'ae_internal' => true,
                    'ae_argument' => $body['back_up_exchange']['ae_argument']??[],
                    'ae_queue' => $body['back_up_exchange']['ae_queue']??'unroutedQueue',
                    'ae_queue_durable' => $body['back_up_exchange']['ae_queue_durable']??true,
                    'ae_queue_exclusive' => $body['back_up_exchange']['ae_queue_exclusive']??false,
                    'ae_queue_autoDelete' => $body['back_up_exchange']['ae_queue_autoDelete']??false,
                    'ae_queue_argument' => $body['back_up_exchange']['ae_queue_argument']??[],
                    'ae_routingkey' => $body['back_up_exchange']['ae_routingkey']??'',
                ];
                $exchangeArguments = $this->backUpExchange($model, $aeParams);
            }
            //声明交换器
            if ($exchangeName){
                $model->exchangeDeclare(
                    $exchangeName,
                    $typeName,
                    $isExDurable,
                    $isAutoDelete,
                    false,
                    new AMQPTable($exchangeArguments)
                );
            }
            //声明队列
            if($body['set_message_ttl_type']==1){
                $queueArguments['x-message-ttl'] = $body['message_ttl'];
            }
            $model->queueDeclare(
                $queueName,
                $isQueDurable,
                $isExclusive,
                $isAutoDelete,
                new AMQPTable($queueArguments)
            );
            //队列绑定
            $model->queueBind($queueName, $exchangeName, $routingKey);
            //发送消息
            try{
                if ($this->config['is_tx']){
                    $model->txSelect();
                }elseif ($this->config['is_producer_confirm']){
                    switch ($this->config['is_producer_confirm_type']){
                        case 0:
                            $model->actionProducerConfirm($body['producer_confirm']['timeout']);
                            break;
                        case 1:
                            $model->actionProducerConfirms($body['producer_confirm']['timeout']);
                            break;
                        case 2:
                            $callback = function (AMQPMessage $message){
                                echo "Message acked with content " . $message->body . PHP_EOL;
                            };
                            $model->asynProducerConfirm($callback);
                            break;
                        case 3:
                            $callback = function (AMQPMessage $message){
                                echo "Message acked with content " . $message->body . PHP_EOL;
                            };
                            $model->asynProducerConfirms($callback);
                            break;
                        default:
                            $model->actionProducerConfirm($body['producer_confirm']['timeout']);
                            break;
                    }
                }
                $model->basicPublish($msg, $exchangeName, $routingKey, $isMandatory, false);
                if ($this->config['is_tx'])    $model->txCommit();
            }catch (\Exception $e){
                if ($this->config['is_tx'])    $model->txRollback();
                return Common::resultMsg('failed', '生产内容失败：'.$e->getMessage());
            }

            //开启mandatory失败消息回调返回给生产者
            $model->setReturnListener(
                function ($replyCode, $replyText, $exchangeName, $routingKey, AMQPMessage $message) {
//                    echo $replyCode . PHP_EOL . $replyText . PHP_EOL . $exchangeName . PHP_EOL . $routingKey;
//                    echo "Message returned with content " . $message->body . PHP_EOL;
                    return Common::resultMsg('failed', '发送失败', ['message'=>"Message returned with content " . $message->body . PHP_EOL]);
                });
            $data = [
                'exchange_name' => $exchangeName,
                'queue_name' => $queueName,
                'routing_key' => $routingKey,
                'message' => $msg->body
            ];
            return Common::resultMsg('success', '生产内容成功', $data);
        }catch (\Exception $e){
            return Common::resultMsg('failed', '生产内容失败：'.$e->getMessage());
        }finally{
            $this->close($model);
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

    /**
     * 备份交换器
     * @param $model
     * @param array $params
     * @return array
     */
    protected function backUpExchange($model, array $params)
    {
        $exchangeArguments = [
            'alternate-exchange' => $params['ae_exchange']
        ];
        $model->exchangeDeclare(
            $params['ae_exchange'],
            $params['ae_type'],
            $params['ae_durable'],
            $params['ae_autoDelete'],
            $params['ae_internal'],
            $params['ae_argument']
        );
        //声明队列
        $model->queueDeclare(
            $params['ae_queue'],
            $params['ae_queue_durable'],
            $params['ae_queue_exclusive'],
            $params['ae_queue_autoDelete'],
            $params['ae_queue_argument']
        );
        //队列绑定
        $model->queueBind(
            $params['ae_queue'],
            $params['ae_exchange'],
            $params['ae_routingkey']
        );

        return $exchangeArguments;
    }

    /**
     * 死信队列
     * @param $model
     * @param array $params
     * @return bool|string
     */
    protected function deadLetterExchange($model, array $params)
    {
        try {
            //声明交换器
            $model->exchangeDeclare(
                $params['dlx_exchange'],
                $params['dlx_type'],
                $params['dlx_durable'],
                $params['dlx_autoDelete'],
                $params['dlx_internal'],
                new AMQPTable($params['dlx_argument'])
            );
            //声明队列
            $model->queueDeclare(
                $params['dlx_queue'],
                $params['dlx_queue_durable'],
                $params['dlx_queue_exclusive'],
                $params['dlx_queue_autoDelete'],
                new AMQPTable($params['dlx_queue_argument'])
            );
            //队列绑定
            $model->queueBind(
                $params['dlx_queue'],
                $params['dlx_exchange'],
                $params['dlx_routekey']
            );
            return true;
        }catch (\Exception $e){
            return $e->getMessage();
        }
    }

}