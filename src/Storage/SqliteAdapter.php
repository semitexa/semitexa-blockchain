<?php

declare(strict_types=1);

namespace Semitexa\Blockchain\Storage;

use Semitexa\Blockchain\Block;

final class SqliteAdapter implements StorageInterface
{
    private \SQLite3 $db;

    public function __construct(string $dbPath)
    {
        $this->db = new \SQLite3($dbPath);
        $this->db->enableExceptions(true);
        $this->db->exec('PRAGMA journal_mode = WAL');
        $this->createTable();
    }

    private function createTable(): void
    {
        $this->db->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS blocks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                version INT NOT NULL,
                previous_hash TEXT NOT NULL,
                payload TEXT NOT NULL,
                hash TEXT NOT NULL,
                signature TEXT NOT NULL,
                idempotency_key TEXT NOT NULL UNIQUE,
                created_at TEXT NOT NULL
            )
        SQL);
    }

    public function append(Block $block): void
    {
        $stmt = $this->db->prepare(<<<'SQL'
            INSERT INTO blocks (version, previous_hash, payload, hash, signature, idempotency_key, created_at)
            VALUES (:version, :previous_hash, :payload, :hash, :signature, :idempotency_key, :created_at)
        SQL);

        $stmt->bindValue(':version', $block->version, SQLITE3_INTEGER);
        $stmt->bindValue(':previous_hash', $block->previousHash, SQLITE3_TEXT);
        $stmt->bindValue(':payload', $block->payload, SQLITE3_TEXT);
        $stmt->bindValue(':hash', $block->hash, SQLITE3_TEXT);
        $stmt->bindValue(':signature', $block->signature, SQLITE3_TEXT);
        $stmt->bindValue(':idempotency_key', $block->idempotencyKey, SQLITE3_TEXT);
        $stmt->bindValue(':created_at', $block->timestamp, SQLITE3_TEXT);
        $stmt->execute();
    }

    public function getLastHash(): ?string
    {
        $result = $this->db->querySingle('SELECT hash FROM blocks ORDER BY id DESC LIMIT 1');
        return $result !== false && $result !== null ? (string) $result : null;
    }

    public function getBlocks(int $offset, int $limit): array
    {
        $stmt = $this->db->prepare('SELECT * FROM blocks ORDER BY id ASC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':limit', $limit, SQLITE3_INTEGER);
        $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
        $result = $stmt->execute();

        $blocks = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $blocks[] = new Block(
                version: (int) $row['version'],
                previousHash: $row['previous_hash'],
                payload: $row['payload'],
                timestamp: $row['created_at'],
                hash: $row['hash'],
                signature: $row['signature'],
                idempotencyKey: $row['idempotency_key'],
            );
        }

        return $blocks;
    }

    public function getChainLength(): int
    {
        return (int) $this->db->querySingle('SELECT COUNT(*) FROM blocks');
    }

    public function hasIdempotencyKey(string $key): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM blocks WHERE idempotency_key = :key LIMIT 1');
        $stmt->bindValue(':key', $key, SQLITE3_TEXT);
        $result = $stmt->execute();

        return $result->fetchArray() !== false;
    }
}
