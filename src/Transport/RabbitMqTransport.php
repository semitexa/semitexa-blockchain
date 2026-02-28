<?php

declare(strict_types=1);

namespace Semitexa\Blockchain\Transport;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

final class RabbitMqTransport implements TransportInterface
{
    private const EXCHANGE = 'semitexa.blockchain.blocks';

    private ?AMQPStreamConnection $connection = null;
    private ?AMQPChannel $channel = null;

    public function __construct(
        private readonly string $dsn,
        private readonly string $nodeId,
    ) {}

    public function publish(string $exchange, string $payload): void
    {
        $channel = $this->getChannel();
        $channel->exchange_declare($exchange, 'fanout', false, true, false);

        $msg = new AMQPMessage($payload, ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);
        $channel->basic_publish($msg, $exchange);
    }

    public function subscribe(string $queue, callable $handler): void
    {
        $channel = $this->getChannel();
        $channel->exchange_declare(self::EXCHANGE, 'fanout', false, true, false);
        $channel->queue_declare($queue, false, true, false, false);
        $channel->queue_bind($queue, self::EXCHANGE);

        $channel->basic_consume($queue, '', false, false, false, false, function (AMQPMessage $msg) use ($handler) {
            $message = MessageFormat::fromJson($msg->getBody());

            // Ignore own messages
            if ($message->nodeId === $this->nodeId) {
                $msg->ack();
                return;
            }

            $handler($message);
            $msg->ack();
        });

        while ($channel->is_consuming()) {
            $channel->wait();
        }
    }

    public function requestChainSince(string $fromHash): void
    {
        $message = new MessageFormat(
            messageType: MessageFormat::TYPE_CHAIN_REQUEST,
            nodeId: $this->nodeId,
            data: ['fromHash' => $fromHash],
        );

        $this->publish(self::EXCHANGE, $message->toJson());
    }

    private function getChannel(): AMQPChannel
    {
        if ($this->channel !== null && $this->channel->is_open()) {
            return $this->channel;
        }

        $parsed = parse_url($this->dsn);
        $this->connection = new AMQPStreamConnection(
            $parsed['host'] ?? 'localhost',
            $parsed['port'] ?? 5672,
            $parsed['user'] ?? 'guest',
            $parsed['pass'] ?? 'guest',
            ltrim($parsed['path'] ?? '/', '/') ?: '/',
        );

        $this->channel = $this->connection->channel();

        return $this->channel;
    }

    public function __destruct()
    {
        try {
            $this->channel?->close();
            $this->connection?->close();
        } catch (\Throwable) {
        }
    }
}
