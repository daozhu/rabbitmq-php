<?php
/**
 * rabbitmq-php
 *
 * @copyright daozhu <597180041@qq.com>
 */

namespace Daozhu\RabbitMQ\PubSub;


use PhpAmqpLib\Message\AMQPMessage;
use Daozhu\RabbitMQ\Message;

class SubMessage
{

    private $message;
    private $routingKey;
    private $params;

    /**
     * SubMessage constructor.
     * @param AMQPMessage $message
     * @param string $routingKey
     * @param array $params
     */
    public function __construct(AMQPMessage $message, string $routingKey, array $params = [])
    {
        $this->message      = $message;
        $this->routingKey   = $routingKey;
        $this->params       = $params;
    }

    /**
     * get msg
     * @return Message
     */
    public function getMessage() : Message
    {
        $msg = new Message();
        $msg->unserialize($this->message->body);

        return $msg;
    }

    /**
     * get AMQP msg
     * @return AMQPMessage
     */
    public function getAMQPMessage() : AMQPMessage
    {
        return $this->message;
    }

    /**
     * get the routing key
     * @return string
     */
    public function getRoutingKey() : string
    {
        return $this->routingKey;
    }

    /**
     * get params
     * @return array
     */
    public function getParams() : array
    {
        return $this->params;
    }

    /**
     * get a param
     * @param string $key
     * @return mixed|null
     */
    public function getParam(string $key)
    {
        return $this->params[$key] ?? null;
    }
}