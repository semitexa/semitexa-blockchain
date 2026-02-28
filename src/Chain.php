<?php

declare(strict_types=1);

namespace Semitexa\Blockchain;

use Semitexa\Blockchain\Crypto\SignerInterface;
use Semitexa\Blockchain\Storage\StorageInterface;

final class Chain
{
    private ?\Swoole\Coroutine\Channel $mutex = null;

    public function __construct(
        private readonly StorageInterface $storage,
        private readonly SignerInterface $signer,
    ) {
        if (class_exists(\Swoole\Coroutine::class) && \Swoole\Coroutine::getCid() > 0) {
            $this->mutex = new \Swoole\Coroutine\Channel(1);
            $this->mutex->push(true);
        }
    }

    public function append(Block $block): void
    {
        $locked = $this->lock();

        try {
            $lastHash = $this->storage->getLastHash() ?? str_repeat('0', 64);

            if ($block->previousHash !== $lastHash) {
                throw new \RuntimeException(
                    "Chain linkage error: expected previousHash '{$lastHash}', got '{$block->previousHash}'."
                );
            }

            $expectedHash = Block::computeHash($block->version, $block->previousHash, $block->payload, $block->timestamp);
            if ($block->hash !== $expectedHash) {
                throw new \RuntimeException('Block hash mismatch.');
            }

            $this->storage->append($block);
        } finally {
            if ($locked) {
                $this->unlock();
            }
        }
    }

    public function validate(): bool
    {
        $length = $this->storage->getChainLength();
        $batchSize = 100;
        $previousHash = str_repeat('0', 64);

        for ($offset = 0; $offset < $length; $offset += $batchSize) {
            $blocks = $this->storage->getBlocks($offset, $batchSize);

            foreach ($blocks as $block) {
                if ($block->previousHash !== $previousHash) {
                    return false;
                }

                $expectedHash = Block::computeHash($block->version, $block->previousHash, $block->payload, $block->timestamp);
                if ($block->hash !== $expectedHash) {
                    return false;
                }

                $previousHash = $block->hash;
            }
        }

        return true;
    }

    public function getLastHash(): ?string
    {
        return $this->storage->getLastHash();
    }

    private function lock(): bool
    {
        if ($this->mutex !== null) {
            $this->mutex->pop();
            return true;
        }
        return false;
    }

    private function unlock(): void
    {
        $this->mutex?->push(true);
    }
}
