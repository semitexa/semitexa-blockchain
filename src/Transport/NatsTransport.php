<?php

declare(strict_types=1);

namespace Semitexa\Blockchain\Transport;

use Basis\Nats\Client;
use Basis\Nats\Configuration;
use Basis\Nats\Message\Msg;

final class NatsTransport implements TransportInterface
{
    private const SUBJECT = 'semitexa.blockchain.blocks';

    private ?Client $client = null;

    public function __construct(
        private readonly string $natsUrl,
        private readonly string $nodeId,
    ) {}

    public function publish(string $exchange, string $payload): void
    {
        $this->getClient()->publish($exchange, $payload);
    }

    public function subscribe(string $queue, callable $handler): void
    {
        $client = $this->getClient();
        $client->subscribe(self::SUBJECT, function (Msg $payload) use ($handler) {
            $message = MessageFormat::fromJson($payload->payload->body);

            if ($message->nodeId === $this->nodeId) {
                return;
            }

            $handler($message);
        });

        while (true) {
            $client->process(0.1);
        }
    }

    public function requestChainSince(string $fromHash): void
    {
        $message = new MessageFormat(
            messageType: MessageFormat::TYPE_CHAIN_REQUEST,
            nodeId: $this->nodeId,
            data: ['fromHash' => $fromHash],
        );

        $this->getClient()->publish(self::SUBJECT, $message->toJson());
    }

    private function getClient(): Client
    {
        if ($this->client !== null) {
            return $this->client;
        }

        $parsed = parse_url($this->natsUrl);
        if (!is_array($parsed)) {
            throw new \InvalidArgumentException("Invalid NATS URL: {$this->natsUrl}");
        }

        $this->client = new Client(new Configuration([
            'host' => $parsed['host'] ?? 'localhost',
            'port' => $parsed['port'] ?? 4222,
        ]));

        return $this->client;
    }
}
