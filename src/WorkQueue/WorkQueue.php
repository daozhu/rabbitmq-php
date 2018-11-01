<?php
/**
 * rabbitmq-php
 *
 * @copyright daozhu <597180041@qq.com>
 */

namespace Daozhu\RabbitMQ\WorkQueue;


interface WorkQueue
{
    /**
     * workQueue
     *
     * @return mixed
     */
    public function workQueue();
}