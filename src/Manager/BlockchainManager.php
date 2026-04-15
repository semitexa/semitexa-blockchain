<?php

declare(strict_types=1);

namespace Semitexa\Blockchain\Manager;

use Semitexa\Blockchain\Chain;
use Semitexa\Blockchain\Config\BlockchainConfig;
use Semitexa\Blockchain\Crypto\RsaSigner;
use Semitexa\Blockchain\Crypto\SignerInterface;
use Semitexa\Blockchain\Storage\SqliteAdapter;
use Semitexa\Blockchain\Storage\StorageInterface;
use Semitexa\Blockchain\Transport\NatsTransport;
use Semitexa\Blockchain\Transport\TransportInterface;

final class BlockchainManager
{
    private ?StorageInterface $storage = null;
    private ?SignerInterface $signer = null;
    private ?TransportInterface $transport = null;
    private ?Chain $chain = null;
    private bool $booted = false;

    public function __construct(
        private readonly BlockchainConfig $config,
    ) {}

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->config->validate();
        $this->booted = true;
    }

    public function getConfig(): BlockchainConfig
    {
        return $this->config;
    }

    public function getStorage(): StorageInterface
    {
        if ($this->storage === null) {
            $this->boot();
            $this->storage = new SqliteAdapter($this->config->dbPath);
        }

        return $this->storage;
    }

    public function getSigner(): SignerInterface
    {
        if ($this->signer === null) {
            $this->boot();
            $this->signer = new RsaSigner($this->config->signingKeyPath);
        }

        return $this->signer;
    }

    public function getTransport(): TransportInterface
    {
        if ($this->transport === null) {
            $this->boot();
            $this->transport = new NatsTransport(
                $this->config->natsUrl,
                $this->config->nodeId,
                $this->config->natsCredentialsPath,
            );
        }

        return $this->transport;
    }

    public function getChain(): Chain
    {
        if ($this->chain === null) {
            $this->chain = new Chain($this->getStorage(), $this->getSigner());
        }

        return $this->chain;
    }
}
