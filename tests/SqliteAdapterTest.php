<?php

declare(strict_types=1);

namespace Semitexa\Blockchain\Tests;

use PHPUnit\Framework\TestCase;
use Semitexa\Blockchain\Block;
use Semitexa\Blockchain\Storage\SqliteAdapter;

final class SqliteAdapterTest extends TestCase
{
    private string $dbPath;

    protected function setUp(): void
    {
        $this->dbPath = sys_get_temp_dir() . '/blockchain_test_' . uniqid() . '.db';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->dbPath)) {
            unlink($this->dbPath);
        }
    }

    private function createBlock(string $previousHash = '', string $idempotencyKey = ''): Block
    {
        $previousHash = $previousHash ?: str_repeat('0', 64);
        $payload = '{"test":true}';
        $timestamp = '2026-01-01T00:00:00+00:00';
        $hash = Block::computeHash(1, $previousHash, $payload, $timestamp);

        return new Block(
            version: 1,
            previousHash: $previousHash,
            payload: $payload,
            timestamp: $timestamp,
            hash: $hash,
            signature: base64_encode('sig'),
            idempotencyKey: $idempotencyKey ?: bin2hex(random_bytes(16)),
        );
    }

    public function testAppendAndRetrieve(): void
    {
        $adapter = new SqliteAdapter($this->dbPath);
        $block = $this->createBlock();

        $adapter->append($block);

        self::assertSame(1, $adapter->getChainLength());
        self::assertSame($block->hash, $adapter->getLastHash());

        $blocks = $adapter->getBlocks(0, 10);
        self::assertCount(1, $blocks);
        self::assertSame($block->hash, $blocks[0]->hash);
    }

    public function testIdempotencyKeyUniqueness(): void
    {
        $adapter = new SqliteAdapter($this->dbPath);
        $block = $this->createBlock(idempotencyKey: 'unique-key-1');

        $adapter->append($block);
        self::assertTrue($adapter->hasIdempotencyKey('unique-key-1'));
        self::assertFalse($adapter->hasIdempotencyKey('nonexistent'));

        // Duplicate should throw
        $this->expectException(\Exception::class);
        $adapter->append($block);
    }

    public function testEmptyChain(): void
    {
        $adapter = new SqliteAdapter($this->dbPath);

        self::assertNull($adapter->getLastHash());
        self::assertSame(0, $adapter->getChainLength());
        self::assertSame([], $adapter->getBlocks(0, 10));
    }
}
