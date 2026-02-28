<?php

declare(strict_types=1);

namespace Semitexa\Blockchain\Tests;

use Semitexa\Blockchain\Block;
use Semitexa\Blockchain\Storage\StorageInterface;

final class InMemoryStorage implements StorageInterface
{
    /** @var Block[] */
    private array $blocks = [];

    /** @var array<string, true> */
    private array $idempotencyKeys = [];

    public function append(Block $block): void
    {
        $this->blocks[] = $block;
        $this->idempotencyKeys[$block->idempotencyKey] = true;
    }

    public function getLastHash(): ?string
    {
        if ($this->blocks === []) {
            return null;
        }

        return $this->blocks[array_key_last($this->blocks)]->hash;
    }

    public function getBlocks(int $offset, int $limit): array
    {
        return array_slice($this->blocks, $offset, $limit);
    }

    public function getChainLength(): int
    {
        return count($this->blocks);
    }

    public function hasIdempotencyKey(string $key): bool
    {
        return isset($this->idempotencyKeys[$key]);
    }
}
