<?php

declare(strict_types=1);

namespace Semitexa\Blockchain\Tests;

use PHPUnit\Framework\TestCase;
use Semitexa\Blockchain\Block;
use Semitexa\Blockchain\PendingBlockQueue;

final class PendingBlockQueueTest extends TestCase
{
    private function makeBlock(string $previousHash, string $hash): Block
    {
        return new Block(
            version: 1,
            previousHash: $previousHash,
            payload: '{}',
            timestamp: '2026-01-01T00:00:00+00:00',
            hash: $hash,
            signature: base64_encode('sig'),
            idempotencyKey: bin2hex(random_bytes(16)),
        );
    }

    public function testDrainInOrder(): void
    {
        $queue = new PendingBlockQueue();

        $b1 = $this->makeBlock('aaa', 'bbb');
        $b2 = $this->makeBlock('bbb', 'ccc');
        $b3 = $this->makeBlock('zzz', 'yyy'); // unrelated

        $queue->add($b2); // out of order
        $queue->add($b1);
        $queue->add($b3);

        $drained = $queue->drain('aaa');

        self::assertCount(2, $drained);
        self::assertSame('bbb', $drained[0]->hash);
        self::assertSame('ccc', $drained[1]->hash);
        self::assertSame(1, $queue->count()); // b3 remains
    }

    public function testMaxSizeEviction(): void
    {
        $queue = new PendingBlockQueue(maxSize: 2);

        $queue->add($this->makeBlock('a', 'b'));
        $queue->add($this->makeBlock('c', 'd'));
        $queue->add($this->makeBlock('e', 'f')); // evicts first

        self::assertSame(2, $queue->count());
    }
}
