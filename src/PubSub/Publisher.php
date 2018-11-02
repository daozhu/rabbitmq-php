<?php
/**
 * rabbitmq-php
 *
 * @copyright daozhu <597180041@qq.com>
 */

namespace Daozhu\RabbitMQ\PubSub;


use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
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

    /**
     * publish a delay message to mq
     *
     * @param $message
     * @param string $queue
     * @param string $routingKey
     * @param int $delayTime  ms
     * @return mixed
     */
    public function publishDelayed($message, string $queue, string $routingKey, int $delayTime = 60*1000)
    {
        if (is_array($message)) {
            $message = new Message($message);
        }

        if (! $message instanceof \Serializable) {
            throw new \InvalidArgumentException('message 必须实现 Serializable 接口');
        }

        $this->addDelayedExchange();
        $this->declareDelayQueue($queue, $routingKey);

        $msg = new AMQPMessage($message->serialize(),[
            'delivery_mode'             => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            'application_headers'       => new AMQPTable([
                'x-dead-letter-exchange'    => $this->exchangeTopic(),
                'x-dead-letter-routing-key' => $routingKey,
                'x-message-ttl'             => $delayTime,
            ]),
        ]);

        $this->channel->basic_publish($msg, $this->exchangeDelayTopic(), $routingKey);
        return $msg;
    }
}