<?php

declare(strict_types=1);

namespace Semitexa\Blockchain;

final class PendingBlockQueue
{
    /** @var Block[] */
    private array $blocks = [];

    public function __construct(
        private readonly int $maxSize = 100,
    ) {}

    public function add(Block $block): void
    {
        if (count($this->blocks) >= $this->maxSize) {
            array_shift($this->blocks);
        }

        $this->blocks[] = $block;
    }

    /**
     * @return Block[] Blocks that can now be appended in order
     */
    public function drain(string $currentLastHash): array
    {
        $result = [];
        $changed = true;

        while ($changed) {
            $changed = false;
            foreach ($this->blocks as $index => $block) {
                if ($block->previousHash === $currentLastHash) {
                    $result[] = $block;
                    $currentLastHash = $block->hash;
                    unset($this->blocks[$index]);
                    $changed = true;
                }
            }
        }

        $this->blocks = array_values($this->blocks);

        return $result;
    }

    public function count(): int
    {
        return count($this->blocks);
    }
}
