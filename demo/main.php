#!/usr/bin/env php
<?php
/**
 * rabbitmq-php
 *
 * @copyright daozhu <597180041@qq.com>
 */

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Console\Application;

$application = new Application();

$application->add(new \Daozhu\Demo\RabbitMQ\Console\Publish());
$application->add(new \Daozhu\Demo\RabbitMQ\Console\Subscribe());

try {
    $application->run();
} catch (Exception $e) {
    echo sprintf('命令执行失败: %s', $e->getMessage());
}


