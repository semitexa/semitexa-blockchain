<?php

declare(strict_types=1);

namespace Semitexa\Blockchain\Tests;

use PHPUnit\Framework\TestCase;
use Semitexa\Blockchain\Block;
use Semitexa\Blockchain\Crypto\SignerInterface;

final class BlockTest extends TestCase
{
    public function testHashComputation(): void
    {
        $hash = Block::computeHash(1, str_repeat('0', 64), '{"foo":"bar"}', '2026-01-01T00:00:00+00:00');
        self::assertSame(64, strlen($hash));
        // Deterministic
        self::assertSame($hash, Block::computeHash(1, str_repeat('0', 64), '{"foo":"bar"}', '2026-01-01T00:00:00+00:00'));
    }

    public function testCreateAndRoundTrip(): void
    {
        $signer = $this->createMock(SignerInterface::class);
        $signer->method('sign')->willReturn(base64_encode('test-signature'));

        $block = Block::create(
            previousHash: str_repeat('0', 64),
            payloadData: ['key' => 'value'],
            signer: $signer,
            nodeId: 'node1',
            resourceClass: 'App\\Resource\\User',
            pkValue: 42,
            operation: 'insert',
        );

        self::assertSame(1, $block->version);
        self::assertSame(str_repeat('0', 64), $block->previousHash);
        self::assertSame('{"key":"value"}', $block->payload);
        self::assertSame(64, strlen($block->hash));
        self::assertSame(64, strlen($block->idempotencyKey));

        // Round-trip via array
        $arr = $block->toArray();
        $restored = Block::fromArray($arr);
        self::assertSame($block->hash, $restored->hash);
        self::assertSame($block->idempotencyKey, $restored->idempotencyKey);
        self::assertSame($block->payload, $restored->payload);
    }

    public function testIdempotencyKeyDeterministic(): void
    {
        $signer = $this->createMock(SignerInterface::class);
        $signer->method('sign')->willReturn(base64_encode('sig'));

        $b1 = Block::create(str_repeat('0', 64), ['a' => 1], $signer, 'n1', 'Cls', 1, 'insert');
        $b2 = Block::create(str_repeat('0', 64), ['a' => 1], $signer, 'n1', 'Cls', 1, 'insert');

        self::assertSame($b1->idempotencyKey, $b2->idempotencyKey);
    }
}
