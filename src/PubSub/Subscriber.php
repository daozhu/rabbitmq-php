<?php
/**
 * rabbitmq-php
 *
 * @copyright daozhu <597180041@qq.com>
 */

namespace Daozhu\RabbitMQ\PubSub;


use PhpAmqpLib\Exception\AMQPIOWaitException;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class Subscriber extends PubSub implements Subscribe
{
    /**
     *  consume msg
     * @param string $queueName
     * @param string $routingKey
     * @param \Closure $callback
     * @param \Closure|null $exitCallback
     * @return mixed|void
     * @throws \ErrorException
     */
    public function consume(string $queueName, string $routingKey, \Closure $callback, \Closure $exitCallback = null)
    {
        if (null === $exitCallback) {
            $exitCallback = function (){
                return false;
            };
        }

        $this->declareConsumeQueue($queueName, $routingKey);
        $this->declareRetryQueue($queueName, $routingKey);
        $this->declareFailedQueue($queueName, $routingKey);

        $retryCallback = function (AMQPMessage $msg) use ($queueName, $routingKey) {

            if ($msg->has('application_headers')) {
                $headers = $msg->get('application_headers');
            } else {
                $headers = new AMQPTable();
            }

            $headers->set('x-orig-routing-key', $this->getOrigRoutingKey($msg));
            $properties = $msg->get_properties();
            $properties['application_headers'] = $headers;

            $newMsg = new AMQPMessage($msg->body, $properties);

            $this->channel->basic_publish($newMsg, $this->exchangeRetryTopic(), $routingKey);

            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        };

        $failedCallback = function (AMQPMessage $msg) use ($queueName, $routingKey) {

            $this->channel->basic_publish($msg, $this->exchangeFailedTopic(), $routingKey);
            $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
        };

        $this->channel->basic_consume($queueName, '', false, false, false, false, function (AMQPMessage $msg) use ($callback, $retryCallback, $failedCallback) {
            $callback($msg, $retryCallback, $failedCallback);
        });

        while (count($this->channel->callbacks)) {

            if ($exitCallback()) {
                return;
            }

            try{
                $this->channel->wait(null, false, 3);
            } catch (AMQPTimeoutException $e) {

            } catch (AMQPIOWaitException $e) {

            }
        }
    }

    /**
     *  declare a failed queue
     * @param string $queue
     * @param string $routingKey
     * @return string
     */
    public function declareFailedQueue(string $queue, string $routingKey) : string
    {
        $failedQueueName = $queue . "@failed";
        $this->channel->queue_declare($failedQueueName, false, true, false, false, false);
        $this->channel->queue_bind($failedQueueName, $this->exchangeFailedTopic(), $routingKey);

        return $failedQueueName;
    }

    /**
     * declare a retry queue
     *
     * @param string $queue
     * @param string $routingKey
     * @return string
     */
    public function declareRetryQueue(string $queue, string $routingKey) : string
    {
        $retryQueueName = $queue . "@retry";
        $this->channel->queue_declare($retryQueueName, false, true, false, false, false,
            new AMQPTable([
                'x-dead-letter-exchange'    => $this->exchangeTopic(),
                'x-dead-letter-routing-key' => $routingKey,
                'x-message-ttl'             => 60 * 1000,
            ])
        );
        $this->channel->queue_bind($retryQueueName, $this->exchangeRetryTopic(), $routingKey);

        return $retryQueueName;
    }

    /**
     * declare a normal queue
     *
     * @param string $queueName
     * @param string $routingKey
     * @return string
     */
    public function declareConsumeQueue(string $queueName, string $routingKey)
    {
        $this->channel->queue_declare($queueName, false, true, false, false, false);
        $this->channel->queue_bind($queueName, $this->exchangeTopic(), $routingKey);

        return $queueName;
    }

    /**
     * failed retry
     * @param string $queue
     * @param string $routingKey
     * @param \Closure null $callback
     * @return mixed|void
     */
    public function retryFailed(string $queue, string $routingKey, $callback = null)
    {
        /* test
        $this->declareConsumeQueue($queue, $routingKey);
        $failedQueueName = $this->declareFailedQueue($queue, $routingKey);

        $this->channel->basic_consume($failedQueueName, '', false, false, false, false, function (AMQPMessage $msg) use ($queue, $routingKey, $callback) {
            if ( $callback == null || $callback($msg) ) {
                $msg->set('application_headers', new AMQPTable([
                    'x-orig-routing-key' => $this->getOrigRoutingKey($msg),
                ]));

                $this->channel->basic_publish($msg, $this->exchangeTopic(), $routingKey);

                $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
            }
        });

        while (count($this->channel->callbacks)) {
            try {
                $this->channel->wait(null, false, 3);
            } catch (AMQPTimeoutException $e) {
                return;
            } catch (AMQPIOWaitException $e) {
            }
        }
        */
        return true;
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
}