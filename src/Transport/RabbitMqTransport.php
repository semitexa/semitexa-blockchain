<?php

declare(strict_types=1);

namespace Semitexa\Blockchain\Transport;

final class RabbitMqTransport implements TransportInterface
{
    private const MIGRATION_MESSAGE = 'Semitexa\\Blockchain\\Transport\\RabbitMqTransport was removed during the NATS migration. Redeploy semitexa/blockchain with a fresh Composer autoload dump and use NatsTransport instead.';

    public function __construct(
        private readonly string $dsn,
        private readonly string $nodeId,
    ) {}

    public function publish(string $exchange, string $payload): void
    {
        throw new \RuntimeException(self::MIGRATION_MESSAGE);
    }

    public function subscribe(string $queue, callable $handler): void
    {
        throw new \RuntimeException(self::MIGRATION_MESSAGE);
    }

    public function requestChainSince(string $fromHash): void
    {
        throw new \RuntimeException(self::MIGRATION_MESSAGE);
    }
}
