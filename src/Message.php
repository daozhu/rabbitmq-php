<?php
/**
 * rabbitmq-php
 *
 * @copyright daozhu <597180041@qq.com>
 */

namespace Daozhu\RabbitMQ;


class Message implements \Serializable
{
    private $message = [];

    public function __construct(array $message = [])
    {
        $this->message = [
            'body' => $message,
            '_id' => uniqid(date('YmdHis-')),
        ];
    }

    /**
     * get message
     * @return mixed|null
     */
    public function getMessage()
    {
        return $this->message['body'] ?? null;
    }

    /**
     * get Id
     * @return mixed|null
     */
    public function getId()
    {
        return $this->message['_id'] ?? null;
    }

    /**
     * serialize
     * @return false|string
     */
    public function serialize()
    {
        return json_encode($this->message, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * unserialize
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $this->message = json_decode($serialized, true);
    }
}