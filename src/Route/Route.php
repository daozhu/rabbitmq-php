<?php
/**
 * rabbitmq-php
 *
 * @copyright daozhu <597180041@qq.com>
 */

namespace Daozhu\RabbitMQ\Route;


interface Route
{
    /**
     * create a route
     *
     * @return mixed
     */
    public function route();
}