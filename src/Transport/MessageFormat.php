<?php

declare(strict_types=1);

namespace Semitexa\Blockchain\Transport;

final readonly class MessageFormat
{
    public const TYPE_BLOCK = 'block';
    public const TYPE_CHAIN_REQUEST = 'chain_request';
    public const TYPE_CHAIN_RESPONSE = 'chain_response';

    public function __construct(
        public string $messageType,
        public string $nodeId,
        public array $data,
    ) {}

    public function toJson(): string
    {
        return json_encode([
            'messageType' => $this->messageType,
            'nodeId' => $this->nodeId,
            'data' => $this->data,
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    public static function fromJson(string $json): self
    {
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return new self(
            messageType: $decoded['messageType'],
            nodeId: $decoded['nodeId'],
            data: $decoded['data'],
        );
    }
}
