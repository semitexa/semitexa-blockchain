<?php

declare(strict_types=1);

namespace Semitexa\Blockchain\Tests;

use PHPUnit\Framework\TestCase;
use Semitexa\Blockchain\Block;
use Semitexa\Blockchain\Chain;
use Semitexa\Blockchain\Crypto\SignerInterface;
use Semitexa\Blockchain\Storage\StorageInterface;

final class ChainTest extends TestCase
{
    private function makeSigner(): SignerInterface
    {
        $signer = $this->createMock(SignerInterface::class);
        $signer->method('sign')->willReturn(base64_encode('sig'));
        return $signer;
    }

    public function testAppendAndValidate(): void
    {
        $storage = new InMemoryStorage();
        $signer = $this->makeSigner();
        $chain = new Chain($storage, $signer);

        $block = Block::create(str_repeat('0', 64), ['x' => 1], $signer, 'n1', 'C', 1, 'insert');
        $chain->append($block);

        self::assertSame($block->hash, $chain->getLastHash());
        self::assertTrue($chain->validate());
    }

    public function testAppendRejectsBrokenLinkage(): void
    {
        $storage = new InMemoryStorage();
        $signer = $this->makeSigner();
        $chain = new Chain($storage, $signer);

        $block = Block::create('wrong_hash', ['x' => 1], $signer, 'n1', 'C', 1, 'insert');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Chain linkage error/');
        $chain->append($block);
    }
}
