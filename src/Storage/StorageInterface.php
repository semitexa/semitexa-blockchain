<?php

declare(strict_types=1);

namespace Semitexa\Blockchain\Storage;

use Semitexa\Blockchain\Block;

interface StorageInterface
{
    public function append(Block $block): void;

    public function getLastHash(): ?string;

    /** @return Block[] */
    public function getBlocks(int $offset, int $limit): array;

    public function getChainLength(): int;

    public function hasIdempotencyKey(string $key): bool;
}
