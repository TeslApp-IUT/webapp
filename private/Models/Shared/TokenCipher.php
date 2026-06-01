<?php

declare(strict_types=1);

namespace Teslapp\Models\Shared;

/**
 * Encrypts the Tesla OAuth tokens with libsodium's authenticated secretbox before they are
 * stored in DB. The 32-byte key comes from the TESLAPP_TOKEN_ENCRYPTION_KEY env var.
 */
final class TokenCipher
{
    public function __construct(private readonly string $key)
    {
        if (strlen($this->key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            throw new \InvalidArgumentException(sprintf(
                'Clé de chiffrement de taille invalide : %d octets fournis, %d attendus.',
                strlen($this->key),
                SODIUM_CRYPTO_SECRETBOX_KEYBYTES,
            ));
        }
    }

    /**
     * @return array{ciphertext: string, nonce: string} both base64-encoded
     */
    public function encrypt(string $plaintext): array
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = sodium_crypto_secretbox($plaintext, $nonce, $this->key);

        return [
            'ciphertext' => base64_encode($ciphertext),
            'nonce' => base64_encode($nonce),
        ];
    }

    /**
     * @throws \RuntimeException if the key is wrong or the data has been tampered with
     */
    public function decrypt(string $ciphertextB64, string $nonceB64): string
    {
        $ciphertext = base64_decode($ciphertextB64, strict: true);
        $nonce = base64_decode($nonceB64, strict: true);

        if ($ciphertext === false || $nonce === false) {
            throw new \RuntimeException('Échec déchiffrement token (entrée base64 invalide).');
        }

        $plaintext = sodium_crypto_secretbox_open($ciphertext, $nonce, $this->key);
        if ($plaintext === false) {
            throw new \RuntimeException('Échec déchiffrement token (clé invalide ou ciphertext altéré).');
        }

        return $plaintext;
    }
}
