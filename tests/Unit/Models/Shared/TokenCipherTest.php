<?php

declare(strict_types=1);

namespace Teslapp\Tests\Unit\Models\Shared;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Teslapp\Models\Shared\TokenCipher;

#[CoversClass(TokenCipher::class)]
final class TokenCipherTest extends TestCase
{
    private TokenCipher $cipher;

    protected function setUp(): void
    {
        $this->cipher = new TokenCipher(str_repeat('k', SODIUM_CRYPTO_SECRETBOX_KEYBYTES));
    }

    #[Test]
    public function roundTripReturnsTheOriginalToken(): void
    {
        $token = 'eyJhbGciOiJSUzI1NiJ9.payload.signature';

        $encrypted = $this->cipher->encrypt($token);

        self::assertSame($token, $this->cipher->decrypt($encrypted['ciphertext'], $encrypted['nonce']));
    }

    #[Test]
    public function encryptingTwiceProducesDifferentCiphertexts(): void
    {
        $first = $this->cipher->encrypt('token');
        $second = $this->cipher->encrypt('token');

        self::assertNotSame($first['ciphertext'], $second['ciphertext']);
        self::assertNotSame($first['nonce'], $second['nonce']);
    }

    #[Test]
    public function decryptingWithTheWrongKeyFails(): void
    {
        $encrypted = $this->cipher->encrypt('token');
        $otherCipher = new TokenCipher(str_repeat('x', SODIUM_CRYPTO_SECRETBOX_KEYBYTES));

        $this->expectException(\RuntimeException::class);
        $otherCipher->decrypt($encrypted['ciphertext'], $encrypted['nonce']);
    }

    #[Test]
    public function decryptingTamperedDataFails(): void
    {
        $encrypted = $this->cipher->encrypt('token');
        $tampered = base64_encode(base64_decode($encrypted['ciphertext']) . "\x00");

        $this->expectException(\RuntimeException::class);
        $this->cipher->decrypt($tampered, $encrypted['nonce']);
    }

    #[Test]
    #[DataProvider('wrongSizedKeys')]
    public function constructorRejectsAKeyOfTheWrongSize(string $key): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new TokenCipher($key);
    }

    public static function wrongSizedKeys(): array
    {
        return [
            'too short' => [str_repeat('k', 16)],
            'too long' => [str_repeat('k', 40)],
        ];
    }
}
