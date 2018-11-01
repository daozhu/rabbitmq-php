<?php
/**
 * rabbitmq-php
 *
 * @copyright daozhu <597180041@qq.com>
 */

namespace Daozhu\RabbitMQ\PubSub;


interface Subscribe
{

    /**
     *  sub msg
     * @param string $queueName
     * @param string $routingKey
     * @param \Closure $callback
     * @param \Closure|null $exitCallback
     * @return mixed
     */
    public function consume(string $queueName, string $routingKey, \Closure $callback, \Closure $exitCallback = null);

    /**
     * retry a failed msg
     * @param string $queue
     * @param string $routingKey
     * @param \Closure|null $callback
     * @return mixed
     */
    public function retryFailed(string $queue, string $routingKey, $callback = null);
}