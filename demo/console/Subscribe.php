<?php
/**
 * rabbitmq-php
 *
 * @copyright daozhu <597180041@qq.com>
 */

namespace Daozhu\Demo\RabbitMQ\Console;

use Daozhu\RabbitMQ\PubSub\DealSub;
use Daozhu\RabbitMQ\RabbitMQ;
use Daozhu\RabbitMQ\PubSub\SubMessage;

/**
 *  继承DealSub 并实现其抽象方法，其中的subscribe方法为业务处理
 *
 *  最后调用 $obj->deal() 消费数据
 * */
class Subscribe extends DealSub
{
    /**
     * deal your data here
     * @param SubMessage $msg
     * @return bool if success then return true  else throw sth
     * @throws \Exception  if failed then throw sth
     */
    public function subscribe(SubMessage $msg): bool
    {
        //业务
        echo sprintf(
            "subscriber:<%s> %s\n",
            $msg->getRoutingKey(),
            $msg->getMessage()->serialize()
        );
        echo "----------------------------------------\n";

        throw new \Exception('failed and retry 3 times');

        return true;
    }

    /**
     * get route key
     * @return string
     */
    public function getRoutingKey(): string
    {
        return 'class.*';
    }

    /**
     * get queue name
     * @return string
     */
    public function getQueueName(): string
    {
        return "class";
    }

    /**
     * get rabbit mq
     * @return RabbitMQ
     */
    public function getRabbitMQ(): RabbitMQ
    {
        $config = [
            'host' => "127.0.0.1",
            'port' => 5672,
            'user' => "guest",
            'password' => "guest",
        ];
        return new RabbitMQ($config);
    }

    /**
     * get exchange name
     * @return string
     */
    public function getExchangeName(): string
    {
        return 'teachingService';
    }
}