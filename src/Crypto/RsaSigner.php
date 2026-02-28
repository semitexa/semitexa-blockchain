<?php

declare(strict_types=1);

namespace Semitexa\Blockchain\Crypto;

use Semitexa\Blockchain\Config\BlockchainConfigException;

final class RsaSigner implements SignerInterface
{
    private \OpenSSLAsymmetricKey $privateKey;

    public function __construct(string $privateKeyPath)
    {
        $keyContents = file_get_contents($privateKeyPath);
        if ($keyContents === false) {
            throw new BlockchainConfigException("Cannot read signing key at: {$privateKeyPath}");
        }

        $key = openssl_pkey_get_private($keyContents);
        if ($key === false) {
            throw new BlockchainConfigException("Invalid private key at: {$privateKeyPath}");
        }

        $this->privateKey = $key;
    }

    public function sign(string $data): string
    {
        $signature = '';
        if (!openssl_sign($data, $signature, $this->privateKey, OPENSSL_ALGO_SHA256)) {
            throw new \RuntimeException('Failed to sign data: ' . openssl_error_string());
        }

        return base64_encode($signature);
    }

    public function verify(string $data, string $signature, string $publicKey): bool
    {
        $key = openssl_pkey_get_public($publicKey);
        if ($key === false) {
            return false;
        }

        $rawSignature = base64_decode($signature, true);
        if ($rawSignature === false) {
            return false;
        }

        return openssl_verify($data, $rawSignature, $key, OPENSSL_ALGO_SHA256) === 1;
    }
}
