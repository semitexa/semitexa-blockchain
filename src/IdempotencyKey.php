<?php

declare(strict_types=1);

namespace Semitexa\Blockchain;

final class IdempotencyKey
{
    public static function generate(string $nodeId, string $resourceClass, mixed $pkValue, string $operation, array $broadcastData): string
    {
        $canonical = json_encode([
            'nodeId' => $nodeId,
            'resourceClass' => $resourceClass,
            'pkValue' => $pkValue,
            'operation' => $operation,
            'broadcastData' => $broadcastData,
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        return hash('sha256', $canonical);
    }
}
