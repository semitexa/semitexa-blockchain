<?php

declare(strict_types=1);

namespace Semitexa\Blockchain;

final readonly class Node
{
    public function __construct(
        public string $nodeId,
        public string $signingKeyPath,
    ) {}
}
