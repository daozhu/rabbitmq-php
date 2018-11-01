<?php
/**
 * rabbitmq-php
 *
 * @copyright daozhu <597180041@qq.com>
 */

namespace Daozhu\RabbitMQ\PubSub;


use Daozhu\RabbitMQ\RabbitMQ;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DealSubCommand extends Command
{

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $retryFailed = $input->hasOption('retry-failed') && $input->getOption('retry-failed');
        if ($retryFailed) {
            $this->retryFailedMessage($input, $output);

            return;
        }

        $this->deal($input, $output);
    }

    /**
     *  start to deal msg
     */
    public function deal(InputInterface $input, OutputInterface $output)
    {
        $callback = function (AMQPMessage $msg, $publishRetry, $publishFailed) {
            $retry = $this->getRetryCount($msg);

            try {
                $subMessage = new SubMessage($msg, $this->getOrigRoutingKey($msg), [
                    'retry_count' => $retry,
                ]);

                $this->subscribe($subMessage);

                $msg->delivery_info['channel']->basic_ack(
                    $msg->delivery_info['delivery_tag']
                );

            } catch (\Exception $ex) {

                if ($retry > 3) {
                    $publishFailed($msg);
                    return;
                }
                $publishRetry($msg);
            }
        };

        $subscriber = new Subscriber($this->getRabbitMQ(), $this->getExchangeName());

        $subscriber->consume(
            $this->getQueueName($input),
            $this->getRoutingKey(),
            $callback
        );
    }

    /**
     * deal the msg
     * @param SubMessage $msg
     * @return bool
     */
    public function subscribe(SubMessage $msg): bool
    {
        //业务
        echo sprintf(
            "subscriber:<%s> %s\n",
            $msg->getRoutingKey(),
            $msg->getMessage()->serialize()
        );
        echo "----------------------------------------\n";

        return true;
    }

    /**
     * deal the failed msg
     * if true , then retry，if false , then deal your self
     * @param SubMessage      $msg
     * @param OutputInterface $output
     * @return bool
     */
    public function subscribeFailed(SubMessage $msg, OutputInterface $output): bool
    {
        $output->writeln(sprintf(
            'retry msg %s, routing_key: %s, body: %s',
            $msg->getMessage()->getID(),
            $msg->getRoutingKey(),
            $msg->getAMQPMessage()->body
        ));
        $output->writeln('------------------------------------');

        return true;
    }

    /**
     * get the queue name
     * @param InputInterface $input
     * @return string
     */
    protected function getQueueName(InputInterface $input): string
    {
        return $input->getArgument('queue_name');
    }

    /**
     * get the routing key
     * @return string
     */
    public function getRoutingKey(): string
    {
        return "class.*";
    }

    /**
     * get a rabbit mq
     * @return RabbitMQ
     */
    public function getRabbitMQ(): RabbitMQ
    {
        return new RabbitMQ([]);
    }

    /**
     * get a exchange name
     * @return string
     */
    public function getExchangeName(): string
    {
        return 'teachingService';
    }

    public function retryFailedMessage(InputInterface $input, OutputInterface $output)
    {
        $callback = function (AMQPMessage $msg) use ($output) {
            $retry = $this->getRetryCount($msg);

            $subMessage = new SubMessage($msg, $this->getOrigRoutingKey($msg), [
                'retry_count' => $retry, // 重试次数
            ]);

            return $this->subscribeFailed($subMessage, $output);
        };

        $subscriber = new Subscriber($this->getRabbitMQ(), $this->getExchangeName());
        $subscriber->retryFailed(
            $this->getQueueName($input),
            $this->getRoutingKey(),
            $callback
        );

        $output->writeln('没有更多待处理的消息了');
    }

    /**
     * get the origin routing key
     * @param AMQPMessage $msg
     * @return mixed|null|\PhpAmqpLib\Channel\AMQPChannel
     */
    public function getOrigRoutingKey(AMQPMessage $msg)
    {
        $retry = null;
        if ($msg->has('application_headers')) {
            $headers = $msg->get('application_headers')->getNativeData();
            if (isset($headers['x-orig-routing-key'])) {
                $retry = $headers['x-orig-routing-key'];
            }
        }

        return !is_null($retry) ? $retry : $msg->get('routing_key');
    }

    /**
     *  get retry times
     * @param AMQPMessage $msg
     * @return int
     */
    public function getRetryCount(AMQPMessage $msg) : int
    {
        $retry = 0;
        if ($msg->has('application_headers')) {
            $headers = $msg->get('application_headers')->getNativeData();
            if (isset($headers['x-death'][0]['count'])) {
                $retry = $headers['x-death'][0]['count'];
            }
        }

        return (int)$retry;
    }
}