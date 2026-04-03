# Semitexa Blockchain

Immutable verification log for ORM operations with cryptographic integrity and event broadcasting.

## Purpose

Maintains an append-only chain of signed blocks for auditable ORM operations. Each block contains a SHA-256 hash linking to the previous block, with RSA-signed payloads for tamper detection.

## Role in Semitexa

Depends on `semitexa/core` and `semitexa/orm`. Optionally integrates with RabbitMQ (php-amqplib) for cross-node event broadcasting. Hooks into ORM operations automatically via `ResourceBroadcastListener`.

## Key Features

- Append-only block chain with SHA-256 linking
- RSA cryptographic signing via `SignerInterface`
- `ResourceBroadcastListener` hooking into ORM operations automatically
- RabbitMQ transport for broadcasting chain events
- Swoole-aware mutex locking for concurrent appends
- SQLite-backed local chain storage

## Notes

The chain is local per node. RabbitMQ transport enables cross-node verification but is not required for single-instance deployments.
