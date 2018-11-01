<?php
/**
 * rabbitmq-php
 *
 * @copyright daozhu <597180041@qq.com>
 */

namespace Daozhu\Demo\RabbitMQ\Console;

use Daozhu\RabbitMQ\Message;
use Daozhu\RabbitMQ\PubSub\Publisher;
use Daozhu\RabbitMQ\RabbitMQ;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Publish extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = [
            'host' => "127.0.0.1",
            'port' => 5672,
            'user' => "guest",
            'password' => "guest",
        ];
        $publisher = new Publisher(new RabbitMQ($config), 'teachingService');

        $publisher->publish(new Message(['id' => uniqid(), 'name' => 'xinTest']), 'class.create');

        $output->writeln('message send over');
    }

    protected function configure()
    {
        $this->setName('demo:publisher')
            ->setDescription('pub demo');
    }

}