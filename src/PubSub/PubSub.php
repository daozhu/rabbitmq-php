<?php
/**
 * rabbitmq-php
 *
 * @copyright daozhu <597180041@qq.com>
 */

namespace Daozhu\RabbitMQ\PubSub;


use Daozhu\RabbitMQ\RabbitMQ;
use PhpAmqpLib\Channel\AMQPChannel;

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
     * @var $topicExchangeName
     * */
    private $topicExchangeName = 'topic';

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
        $this->channel->exchange_declare($this->exchangeTopic(), $this->topicExchangeName, false, true, false);
        // retry
        $this->channel->exchange_declare($this->exchangeRetryTopic(), $this->topicExchangeName, false, true, false);
        // failed
        $this->channel->exchange_declare($this->exchangeFailedTopic(), $this->topicExchangeName, false, true,false);
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


}