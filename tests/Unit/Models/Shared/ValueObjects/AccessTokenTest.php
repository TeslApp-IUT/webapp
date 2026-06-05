<?php

declare(strict_types=1);

namespace Teslapp\Tests\Unit\Models\Shared\ValueObjects;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Teslapp\Models\Shared\ValueObjects\AccessToken;

#[CoversClass(AccessToken::class)]
final class AccessTokenTest extends TestCase
{
    #[Test]
    public function keepsANonEmptyValue(): void
    {
        $token = new AccessToken('eyJhbGciOiJSUzI1NiJ9.payload.signature');

        self::assertSame('eyJhbGciOiJSUzI1NiJ9.payload.signature', $token->value);
    }

    #[Test]
    #[DataProvider('emptyValues')]
    public function rejectsAnEmptyValue(string $empty): void
    {
        $this->expectException(InvalidArgumentException::class);

        new AccessToken($empty);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function emptyValues(): array
    {
        return [
            'empty string' => [''],
            'only whitespace' => ['   '],
        ];
    }
}
