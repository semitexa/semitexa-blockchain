<?php

declare(strict_types=1);

namespace Semitexa\Blockchain\Bridge;

use Semitexa\Blockchain\Block;
use Semitexa\Blockchain\Chain;
use Semitexa\Blockchain\Config\BlockchainConfig;
use Semitexa\Blockchain\Crypto\SignerInterface;
use Semitexa\Blockchain\Storage\StorageInterface;
use Semitexa\Blockchain\Transport\MessageFormat;
use Semitexa\Blockchain\Transport\TransportInterface;
use Semitexa\Core\Attributes\AsEventListener;
use Semitexa\Core\Event\EventExecution;
use Semitexa\Orm\Event\ResourceBroadcastEvent;

#[AsEventListener(event: ResourceBroadcastEvent::class, execution: EventExecution::Sync)]
final class ResourceBroadcastListener
{
    public function __construct(
        private readonly BlockchainConfig $config,
        private readonly Chain $chain,
        private readonly StorageInterface $storage,
        private readonly SignerInterface $signer,
        private readonly TransportInterface $transport,
    ) {}

    public function handle(ResourceBroadcastEvent $event): void
    {
        if (!$this->config->enabled) {
            return;
        }

        $payloadData = [
            'resourceClass' => $event->getResourceClass(),
            'tableName' => $event->getTableName(),
            'pkColumn' => $event->getPkColumn(),
            'pkValue' => $event->getPkValue(),
            'operation' => $event->getOperation(),
            'broadcastData' => $event->getBroadcastData(),
        ];

        $block = Block::create(
            previousHash: $this->chain->getLastHash() ?? str_repeat('0', 64),
            payloadData: $payloadData,
            signer: $this->signer,
            nodeId: $this->config->nodeId,
            resourceClass: $event->getResourceClass(),
            pkValue: $event->getPkValue(),
            operation: $event->getOperation(),
        );

        if ($this->storage->hasIdempotencyKey($block->idempotencyKey)) {
            return;
        }

        $this->chain->append($block);

        $message = new MessageFormat(
            messageType: MessageFormat::TYPE_BLOCK,
            nodeId: $this->config->nodeId,
            data: $block->toArray(),
        );

        $this->transport->publish('semitexa.blockchain.blocks', $message->toJson());
    }
}
