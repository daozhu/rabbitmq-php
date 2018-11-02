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

    /**
     * publish a delay message to mq
     *
     * @param $message
     * @param string $queue
     * @param string $routingKey
     * @param int $delayTime  ms
     * @return mixed
     */
    public function publishDelayed($message, string $queue, string $routingKey, int $delayTime);
}