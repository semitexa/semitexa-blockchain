<?php

declare(strict_types=1);

namespace Semitexa\Blockchain\Crypto;

interface SignerInterface
{
    public function sign(string $data): string;

    public function verify(string $data, string $signature, string $publicKey): bool;
}
