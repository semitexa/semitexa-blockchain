<?php

declare(strict_types=1);

namespace Semitexa\Blockchain\Config;

use Semitexa\Core\Environment;

final class BlockchainConfig
{
    public readonly bool $enabled;
    public readonly string $storage;
    public readonly string $dbPath;
    public readonly string $transport;
    public readonly string $natsUrl;
    public readonly string $signingKeyPath;
    public readonly string $nodeId;

    public function __construct()
    {
        $this->enabled = Environment::getEnvValue('BLOCKCHAIN_ENABLED', '0') === '1';
        $this->storage = Environment::getEnvValue('BLOCKCHAIN_STORAGE', 'sqlite');
        $this->dbPath = Environment::getEnvValue('BLOCKCHAIN_DB_PATH', '');
        $this->transport = Environment::getEnvValue('BLOCKCHAIN_TRANSPORT', 'nats');
        $this->natsUrl = Environment::getEnvValue('BLOCKCHAIN_NATS_URL', '')
            ?: Environment::getEnvValue('NATS_PRIMARY_URL', '');
        $this->signingKeyPath = Environment::getEnvValue('BLOCKCHAIN_SIGNING_KEY', '');
        $this->nodeId = Environment::getEnvValue('BLOCKCHAIN_NODE_ID', '');
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

        if ($this->transport === 'nats' && $this->natsUrl === '') {
            throw new BlockchainConfigException('BLOCKCHAIN_NATS_URL or NATS_PRIMARY_URL is required when transport is nats.');
        }
    }
}
