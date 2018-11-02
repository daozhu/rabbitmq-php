<?php
/**
 * rabbitmq-php
 *
 * @copyright daozhu <597180041@qq.com>
 */

namespace Daozhu\RabbitMQ\PubSub;


use Daozhu\RabbitMQ\RabbitMQ;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * Class PubSub
 *
 * @package RabbitMQ
 */
abstract class PubSub
{

    /**
     * @var RabbitMQ
     * */
    private $rabbitMQ;

    /**
     * @var AMQPChannel
     * */
    protected $channel;

    /**
     * @var  string
     * */
    private $exchangeName;

    /**
     * @var $topicExchangeTypeName
     * */
    private $topicExchangeTypeName = 'topic';

    /**
     * need enable the rabbitmq-delayed-message-exchange plugin
     * @var $retryExchangeName
     */
    private $retryExchangeTypeName = 'x-delayed-message';

    /**
     * Create a publisher
     * @param RabbitMQ|null $rabbitMQ
     * @param string|null $exchangeName
     */
    public function __construct(RabbitMQ $rabbitMQ = null, string $exchangeName = null)
    {
        $this->rabbitMQ     = $rabbitMQ;
        $this->exchangeName = $exchangeName;
        $this->initialize();
    }

    /**
     *  init
     */
    public function initialize(): void
    {
        $this->channel = $this->rabbitMQ->channel();

        // normal
        $this->channel->exchange_declare($this->exchangeTopic(), $this->topicExchangeTypeName, false, true, false);
        // retry
        $this->channel->exchange_declare($this->exchangeRetryTopic(), $this->topicExchangeTypeName, false, true, false);
        // failed
        $this->channel->exchange_declare($this->exchangeFailedTopic(), $this->topicExchangeTypeName, false, true,false);
    }

    /**
     * add a delayed exchange
     */
    public function addDelayedExchange(): void
    {
        $this->channel->exchange_declare($this->exchangeDelayTopic(), $this->topicExchangeTypeName, false, true,false);
    }

    /**
     * get exchange | topic
     * @return string
     */
    public function exchangeTopic(): string
    {
        return $this->exchangeName;
    }

    /**
     * get retry exchange | topic
     * @return string
     */
    public function exchangeRetryTopic() : string
    {
        return $this->exchangeName . "." . "retry";
    }

    /**
     * get failed exchange | topic
     * @return string
     */
    public function exchangeFailedTopic() : string
    {
        return $this->exchangeName . "." . "failed";
    }


    public function exchangeDelayTopic() : string
    {
        return $this->exchangeName . "." . "delayed";
    }

    /**
     * declare a retry queue
     *
     * @param string $queue
     * @param string $routingKey
     * @return string
     */
    public function declareDelayQueue(string $queue, string $routingKey) : string
    {
        $delayQueueName = $queue . "@delay";
        $this->channel->queue_declare($delayQueueName, false, true, false, false, false);
        $this->channel->queue_bind($delayQueueName, $this->exchangeDelayTopic(), $routingKey);
        $this->channel->basic_qos(0, 1, false);

        return $delayQueueName;
    }


}