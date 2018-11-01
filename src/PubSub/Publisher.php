<?php
/**
 * rabbitmq-php
 *
 * @copyright daozhu <597180041@qq.com>
 */

namespace Daozhu\RabbitMQ\PubSub;


use PhpAmqpLib\Message\AMQPMessage;
use Daozhu\RabbitMQ\Message;

class Publisher extends PubSub implements Publish
{

    /**
     * send a msg to mq
     * @param $message
     * @param string $routingKey
     * @return mixed|AMQPMessage
     */
    public function publish($message, string $routingKey)
    {
        if (is_array($message)) {
            $message = new Message($message);
        }

        if (! $message instanceof \Serializable) {
            throw new \InvalidArgumentException('message 必须实现 Serializable 接口');
        }

        $msg = new AMQPMessage($message->serialize(),[
           'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
        ]);

        $this->channel->basic_publish($msg, $this->exchangeTopic(), $routingKey);
        return $msg;
    }
}