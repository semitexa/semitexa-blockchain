<?php

declare(strict_types=1);

namespace Semitexa\Blockchain\Transport;

interface TransportInterface
{
    public function publish(string $exchange, string $payload): void;

    public function subscribe(string $queue, callable $handler): void;

    public function requestChainSince(string $fromHash): void;
}
