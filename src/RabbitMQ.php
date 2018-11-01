<?php
/**
 * rabbitmq-php
 *
 * @copyright daozhu <597180041@qq.com>
 */

namespace Daozhu\RabbitMQ;


use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class RabbitMQ
{
    /**
     * @var string
     */
    private $host = "127.0.0.1";

    /**
     * @var int
     */
    private $port = 5672;

    /**
     * @var string
     */
    private $user = "guest";

    /**
     * @var string
     */
    private $password  = "guest";

    /**
     * @var string
     */
    private $vhost = "/";

    /**
     * @var AMQPStreamConnection
     */
    private $connection;

    /**
     * @var AMQPChannel[]
     */
    private $channels = [];

    /**
     * host/port/user/password/vhost
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        foreach ($config as $key => $value) {
            if (isset($this->$key)) $this->$key = $value;
        }

        $this->connection = new AMQPStreamConnection(
            $this->host,
            $this->port,
            $this->user,
            $this->password,
            $this->vhost
        );
    }

    /**
     *  get | create a channel
     *
     * @param string|null $connectId
     * @return AMQPChannel
     */
    public function channel(string $connectId = null)
    {
        if (isset($this->channels[$connectId])) return $this->channels[$connectId];

        return $this->channels[$connectId] = $this->connection->channel($connectId);
    }

    /**
     * get the RabbitMQ connection
     *
     * @return AMQPStreamConnection
     */
    public function connection()
    {
        return $this->connection;
    }

    /**
     * destructor
     */
    public function __destruct()
    {
        foreach ($this->channels as $channel) {
            if (!empty($channel)) $channel->close();
        }

        if (!empty($this->connection)) $this->connection->close();
    }

}