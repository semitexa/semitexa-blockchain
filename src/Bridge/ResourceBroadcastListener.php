<?php

declare(strict_types=1);

namespace Semitexa\Blockchain\Bridge;

use Semitexa\Blockchain\Config\BlockchainConfig;
use Semitexa\Blockchain\Manager\BlockchainManager;
use Semitexa\Blockchain\Transport\MessageFormat;
use Semitexa\Core\Attributes\AsEventListener;
use Semitexa\Core\Event\EventExecution;
use Semitexa\Orm\Event\ResourceBroadcastEvent;

#[AsEventListener(event: ResourceBroadcastEvent::class, execution: EventExecution::Sync)]
final class ResourceBroadcastListener
{
    public function __construct(
        private readonly ?BlockchainManager $manager = null,
    ) {}

    public function handle(ResourceBroadcastEvent $event): void
    {
        $manager = $this->manager ?? new BlockchainManager(new BlockchainConfig());
        $config = $manager->getConfig();

        if (!$config->enabled) {
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

        $chain = $manager->getChain();
        $storage = $manager->getStorage();
        $signer = $manager->getSigner();
        $transport = $manager->getTransport();

        $block = \Semitexa\Blockchain\Block::create(
            previousHash: $chain->getLastHash() ?? str_repeat('0', 64),
            payloadData: $payloadData,
            signer: $signer,
            nodeId: $config->nodeId,
            resourceClass: $event->getResourceClass(),
            pkValue: $event->getPkValue(),
            operation: $event->getOperation(),
        );

        if ($storage->hasIdempotencyKey($block->idempotencyKey)) {
            return;
        }

        $chain->append($block);

        $message = new MessageFormat(
            messageType: MessageFormat::TYPE_BLOCK,
            nodeId: $config->nodeId,
            data: $block->toArray(),
        );

        $transport->publish('semitexa.blockchain.blocks', $message->toJson());
    }
}
