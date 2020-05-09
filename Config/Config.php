<?php
/**+----------------------------------------------------------------------
 * JamesPi Redis [php-redis]
 * +----------------------------------------------------------------------
 * RabbitMQ Service Configuration file
 * +----------------------------------------------------------------------
 * Copyright (c) 2020-2030 http://www.pijianzhong.com All rights reserved.
 * +----------------------------------------------------------------------
 * Author：PiJianZhong <jianzhongpi@163.com>
 * +----------------------------------------------------------------------
 */

return [
    'Basic' =>[
        'host' => '192.168.109.54',
        'port' => '5672',
        'username' => 'guest',
        'password' => 'guest'
    ],
    'is_autoAck' => false, //系统默认false【若需要特殊需求需要true开启】
    'is_mandatory' => true, //系统默认开启【若需要特殊需求需要false关闭】
    'is_strict_mode' => false, //是否开启严格模式【开启事务+生产端确认机制，高可靠性但会降低性能】
    //若第一次没有开启AE而声明了交换器后再开启AE需要先将同名交换器删除再声明开启否则会报错
    'is_ae' => true, //是否开启备份交换器【开启严格模式默认开启mandatory,若开启AE则系统自动关闭mandatory】
    'is_exchange_persistence' => true, //是否开启交换器持久化
    'is_queue_persistence' => true, //是否开启队列持久化
    'is_message_persistence' => true, //是否开启消息持久化
    'operating_mode' => 1, // 服务模式【1：单一节点模式  2：普通(集群)模式  3：镜像节点(集群)模式】
    'scene_mode' => 1, // 交换器模式【1：fanout，适用于广播 2：direct，应用精准匹配 3：topic，应用于模糊范围匹配】

];