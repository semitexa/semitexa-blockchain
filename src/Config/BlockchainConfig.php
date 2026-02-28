<?php

declare(strict_types=1);

namespace Semitexa\Blockchain\Config;

final class BlockchainConfig
{
    public readonly bool $enabled;
    public readonly string $storage;
    public readonly string $dbPath;
    public readonly string $transport;
    public readonly string $rabbitmqDsn;
    public readonly string $signingKeyPath;
    public readonly string $nodeId;

    public function __construct()
    {
        $this->enabled = ($_ENV['BLOCKCHAIN_ENABLED'] ?? '0') === '1';
        $this->storage = $_ENV['BLOCKCHAIN_STORAGE'] ?? 'sqlite';
        $this->dbPath = $_ENV['BLOCKCHAIN_DB_PATH'] ?? '';
        $this->transport = $_ENV['BLOCKCHAIN_TRANSPORT'] ?? 'rabbitmq';
        $this->rabbitmqDsn = $_ENV['BLOCKCHAIN_RABBITMQ_DSN'] ?? '';
        $this->signingKeyPath = $_ENV['BLOCKCHAIN_SIGNING_KEY'] ?? '';
        $this->nodeId = $_ENV['BLOCKCHAIN_NODE_ID'] ?? '';
    }

    public function validate(): void
    {
        if (!$this->enabled) {
            return;
        }

        if ($this->dbPath === '') {
            throw new BlockchainConfigException('BLOCKCHAIN_DB_PATH is required when blockchain is enabled.');
        }

        if ($this->signingKeyPath === '') {
            throw new BlockchainConfigException('BLOCKCHAIN_SIGNING_KEY is required when blockchain is enabled.');
        }

        if (!file_exists($this->signingKeyPath)) {
            throw new BlockchainConfigException("Signing key not found at: {$this->signingKeyPath}");
        }

        if ($this->nodeId === '') {
            throw new BlockchainConfigException('BLOCKCHAIN_NODE_ID is required when blockchain is enabled.');
        }

        if ($this->transport === 'rabbitmq' && $this->rabbitmqDsn === '') {
            throw new BlockchainConfigException('BLOCKCHAIN_RABBITMQ_DSN is required when transport is rabbitmq.');
        }
    }
}
