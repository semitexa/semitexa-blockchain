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
        private readonly ?string $credentialsPath = null,
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

        // Support both full URLs (nats://host:port) and bare host:port strings
        $host = $parsed['host'] ?? null;
        $port = $parsed['port'] ?? 4222;
        if ($host === null && isset($parsed['path']) && $parsed['path'] !== '') {
            $bareParts = explode(':', $parsed['path'], 2);
            $host = $bareParts[0];
            if (isset($bareParts[1]) && is_numeric($bareParts[1])) {
                $port = (int) $bareParts[1];
            }
        }

        if ($host === null || $host === '') {
            throw new \InvalidArgumentException("Cannot determine NATS host from: {$this->natsUrl}");
        }

        $options = [
            'host' => $host,
            'port' => $port,
        ];

        // Security: support credentials file authentication for blockchain NATS transport (VULN-010)
        if ($this->credentialsPath !== null) {
            $credentialsPath = trim($this->credentialsPath);

            if ($credentialsPath === '') {
                throw new \InvalidArgumentException('NATS credentials path cannot be empty.');
            }

            if (!is_file($credentialsPath) || !is_readable($credentialsPath)) {
                throw new \InvalidArgumentException("NATS credentials file is not readable: {$credentialsPath}");
            }

            // basis-company/nats uses 'creds' for .credentials file paths
            $options['creds'] = $credentialsPath;
        }

        $this->client = new Client(new Configuration($options));

        return $this->client;
    }
}
