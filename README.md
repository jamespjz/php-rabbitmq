# php-rabbitmq
php基于rabbitmq实现消息队列的操作封装，支持单节点、集群模式
>使用版本说明：RabbitMQ v3.6.10 - PHP v7.4.1 - Composer v1.10

# 简要说明：
公司目前正在全面转微服务架构，为了让PHPER在分布式高并发场景下保证数据的准确性，特封装了基于RabbitMQ消息队列功能的插件，目前为version 0.1-dev。

# 消息队列支持的特性：
* mandatory
* 备份交换器
* 事务性
* 生产端消息确认【同步模式、同步批量模式、异步模式、异步批量模式】
* 死信队列
* 延迟队列
* 消息消费端确认
* 持久化
* 消息消费模式【推模式、拉模式】

# 部署安装
* github下载
```
git clone https://github.com/jamespjz/php-redis.git
```
已经加入对composer支持，根目录下有个composer.json，请不要随意修改其中内容如果你不明白你在做什么操作。
* composer下载
```
composer require jamespi/php-rabbitmq dev-master
```

# 使用方式
> 通过rabbitmq生产消息
```
require_once 'vendor/autoload.php';
use Jamespi\RabbitMQ\Start;

$type = 2; // 服务类型【1：消息生产端 2：消息消费端】
$config = [
    'Basic' => [
        'host' => '192.168.109.53',
        'port' => '5672',
        'username' => 'admin',
        'password' => 'admin'
    ],
    
    'is_mandatory' => false, //系统默认关闭【若需要特殊需求需要true开启,若无特殊需求可不必写入】
    'is_tx' => false, //是否开启事务【系统默认关闭，开启事务会保持高可靠性但会降低性能】
    'is_producer_confirm' => false, //是否开启生产端确认机制【系统默认关闭，开启会保持高可靠性，与事务不同是如果采用异步模式，不会让发送端阻塞】
    'is_producer_confirm_type' => 2, //confirm消息确认模式【0：普通模式(同步串行) 1：批量模式(同步串行) 2：异步模式 3：批量异步模式】
    //若第一次没有开启AE而声明了交换器后再开启AE需要先将同名交换器删除再声明开启否则会报错
    'is_ae' => true, //是否开启备份交换器【默认开启,若开启AE则系统自动关闭mandatory】
    'is_dead_exchange' => true, //是否开启死信队列【系统默认关闭】
    'operating_mode' => 1, // 服务模式【1：单一节点模式  2：普通(集群)模式  3：镜像节点(集群)模式】
];

$body = [
    //发送端confirm确认
    'producer_confirm' => [
        'timeout' => 10, //发送端confirm模式同步超时时间（秒）
    ],
    //死信队列(dlx)
    'dead_exchange' => [
        'dlx_exchange' => 'dlx_ex',
        'dlx_type' => 'direct',
        'dlx_durable' => true,
        'dlx_queue' => 'dlx_queue',
        'dlx_queue_durable' => true,
        'dlx_routekey' => 'dlx_routekey'
    ],
    //备份交换器(Ae)
    'back_up_exchange' => [
        'ae_exchange' => 'myAe',
        'ae_exchange_type' => 'fanout',
        'ae_queue' => 'unroutedQueue',
        'ae_routingkey' => 'ae_routingkey'
    ],
    //若set_message_ttl_type选择1则队列中消息都相同过期时间，若选择2则每个消息过期时间不一样，若2种有有则按最小时间过期
    'message_ttl' => 4500000, //单条消息过期时间（毫秒）【不需要设置时间可以不加】
    'set_message_ttl_type' => 1, //设置消息过期时间方式【1：设置队列时间属性 2：设置单个消息过期时间】【不需要设置时间可以不加】
    'exchange_name' => 'test_exchange', //交换器名称
    'scene_mode' => 2, // 交换器模式【1：fanout，适用于广播 2：direct，应用精准匹配 3：topic，应用于模糊范围匹配】
    'is_exchange_persistence' => true, //是否开启交换器持久化
//    'queue_name' => 'test_queue', //队列名称
    'is_queue_persistence' => true, //是否开启队列持久化
    'routing_key' => 'test_key', //绑定key
    'is_message_persistence' => true, //是否开启消息持久化
    'msg' => '纵腾测试rabbitmq', //消息体
];
//生产
echo (new Start())->run($type, $config)->addMessage($body);
```
> 通过rabbitmq消费消息
```
require_once 'vendor/autoload.php';
use Jamespi\RabbitMQ\Start;

$type = 2; // 服务类型【1：消息生产端 2：消息消费端】
$config = [
    'Basic' => [
        'host' => '192.168.109.53',
        'port' => '5672',
        'username' => 'admin',
        'password' => 'admin'
    ],

    'is_autoAck' => false, //系统默认false【若需要特殊需求需要true开启,若无特殊需求可不必写入】
    'consumer_mode' => 1, //消费模式【系统默认是推模式，1：推模式 2：拉模式】
    'operating_mode' => 1, // 服务模式【1：单一节点模式  2：普通(集群)模式  3：镜像节点(集群)模式】
];

$body = [
    'qos_number' => 1, //信道上消费者所能保证最大未确认消息的数量
    'queque_name' => 'test_queue', //队列名称
    'consumer_tag' => '', //消费者标签（可不加）
    'no_local' => false, //设置为true表示不能同一个connection中生产者发送的消息传给这个connection的消费者（可不加）
    'exclusive' => '', //设置是否排它（可不加）
    'argument' => [], //其他参数（可不加）
    'callback' => ['namespace' => 'App\IndexController', 'action'=> 'rabbitmqTest']
];
//消费
echo (new Start())->run($type, $config)->consumerMessage($body);
```

***注意：配置数组的下标键名是约定好的，请不要定制个性化名称，如果不想系统报错或系统使用默认配置参数而达不到您想要的结果的话***

# 联系方式
* wechat：james-pi
* email：jianzhongpi@163.com