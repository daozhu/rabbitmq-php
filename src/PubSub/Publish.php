<?php
/**
 * rabbitmq-php
 *
 * @copyright daozhu <597180041@qq.com>
 */

namespace Daozhu\RabbitMQ\PubSub;


interface Publish
{
    /**
     * publish message to mq
     *
     * @param $message
     * @param string $routingKey
     * @return mixed
     */
    public function publish($message, string $routingKey);
}