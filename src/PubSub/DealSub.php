<?php
/**
 * rabbitmq-php
 *
 * @copyright daozhu <597180041@qq.com>
 */

namespace Daozhu\RabbitMQ\PubSub;


use Daozhu\RabbitMQ\RabbitMQ;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class DealSub
{
    /**
     *  start to deal msg
     */
    public function deal()
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
            $this->getQueueName(),
            $this->getRoutingKey(),
            $callback
        );
    }

    /**
     * get routing key
     * @return string
     */
    abstract public function getRoutingKey(): string;

    /**
     * get queue name
     * @return string
     */
    abstract public function getQueueName(): string;

    /**
     * get rabbit mq
     * @return RabbitMQ
     */
    abstract public function getRabbitMQ(): RabbitMQ;

    /**
     * deal the msg
     * @param SubMessage $msg
     * @return bool
     */
    abstract public function subscribe(SubMessage $msg): bool;

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
     * get a exchange name
     * @return string
     */
    abstract public function getExchangeName(): string;


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
            $this->getQueueName(),
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