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

        // Security: validate minimum RSA key size (VULN-019)
        $details = openssl_pkey_get_details($key);
        if ($details === false) {
            throw new BlockchainConfigException("Cannot read key details at: {$privateKeyPath}");
        }

        // Verify the key type is actually RSA before applying bit-length rules
        if (($details['type'] ?? null) !== OPENSSL_KEYTYPE_RSA) {
            throw new BlockchainConfigException(
                "Signing key at {$privateKeyPath} is not an RSA key (type: {$details['type']}). RSA key required."
            );
        }

        $bits = is_int($details['bits'] ?? null) ? $details['bits'] : 0;
        if ($bits < 2048) {
            throw new BlockchainConfigException(
                "RSA key at {$privateKeyPath} is too weak ({$bits} bits). Minimum 2048 bits required."
            );
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
