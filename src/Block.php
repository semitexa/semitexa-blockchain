<?php

declare(strict_types=1);

namespace Semitexa\Blockchain;

use Semitexa\Blockchain\Crypto\SignerInterface;

final readonly class Block
{
    public function __construct(
        public int $version,
        public string $previousHash,
        public string $payload,
        public string $timestamp,
        public string $hash,
        public string $signature,
        public string $idempotencyKey,
    ) {}

    public static function create(
        string $previousHash,
        array $payloadData,
        SignerInterface $signer,
        string $nodeId,
        string $resourceClass = '',
        mixed $pkValue = null,
        string $operation = '',
    ): self {
        $version = 1;
        $payload = json_encode($payloadData, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $timestamp = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('c');

        $hash = self::computeHash($version, $previousHash, $payload, $timestamp);
        $signature = $signer->sign($payload);
        $idempotencyKey = IdempotencyKey::generate(
            $nodeId,
            $resourceClass,
            $pkValue,
            $operation,
            $payloadData,
        );

        return new self($version, $previousHash, $payload, $timestamp, $hash, $signature, $idempotencyKey);
    }

    public static function computeHash(int $version, string $previousHash, string $payload, string $timestamp): string
    {
        $canonical = json_encode([
            'version' => $version,
            'previousHash' => $previousHash,
            'payload' => $payload,
            'timestamp' => $timestamp,
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        return hash('sha256', $canonical);
    }

    public function toArray(): array
    {
        return [
            'version' => $this->version,
            'previousHash' => $this->previousHash,
            'payload' => $this->payload,
            'timestamp' => $this->timestamp,
            'hash' => $this->hash,
            'signature' => $this->signature,
            'idempotencyKey' => $this->idempotencyKey,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            version: (int) $data['version'],
            previousHash: $data['previousHash'],
            payload: $data['payload'],
            timestamp: $data['timestamp'],
            hash: $data['hash'],
            signature: $data['signature'],
            idempotencyKey: $data['idempotencyKey'],
        );
    }
}
