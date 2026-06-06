<?php

declare(strict_types=1);

namespace Teslapp\Tests\Unit\Utils;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Teslapp\Utils\Csrf;

#[CoversClass(Csrf::class)]
final class CsrfTest extends TestCase
{
    /** A realistic 64-char hex token (bin2hex(random_bytes(32))). */
    private const TOKEN = 'a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2';

    protected function setUp(): void
    {
        parent::setUp();
        unset(
            $_SESSION['csrf_token'],
            $_SERVER['HTTP_X_CSRF_TOKEN'],
            $_SERVER['HTTP_X_CUSTOM_CSRF'],
        );
    }

    protected function tearDown(): void
    {
        unset(
            $_SESSION['csrf_token'],
            $_SERVER['HTTP_X_CSRF_TOKEN'],
            $_SERVER['HTTP_X_CUSTOM_CSRF'],
        );
        parent::tearDown();
    }

    #[Test]
    public function returnsTrueWhenTheHeaderTokenMatchesTheSessionToken(): void
    {
        $_SESSION['csrf_token'] = self::TOKEN;
        $_SERVER['HTTP_X_CSRF_TOKEN'] = self::TOKEN;

        self::assertTrue(Csrf::checkFromHeader());
    }

    #[Test]
    public function returnsFalseWhenTheHeaderTokenDiffersFromTheSessionToken(): void
    {
        $_SESSION['csrf_token'] = self::TOKEN;
        $_SERVER['HTTP_X_CSRF_TOKEN'] = str_repeat('0', 64);

        self::assertFalse(Csrf::checkFromHeader());
    }

    #[Test]
    public function returnsFalseWhenNoSessionTokenExists(): void
    {
        $_SERVER['HTTP_X_CSRF_TOKEN'] = self::TOKEN;

        self::assertFalse(Csrf::checkFromHeader());
    }

    #[Test]
    public function returnsFalseWhenNoHeaderIsSent(): void
    {
        $_SESSION['csrf_token'] = self::TOKEN;

        self::assertFalse(Csrf::checkFromHeader());
    }

    #[Test]
    public function readsACustomHeaderNameWhenProvided(): void
    {
        $_SESSION['csrf_token'] = self::TOKEN;
        $_SERVER['HTTP_X_CUSTOM_CSRF'] = self::TOKEN;

        self::assertTrue(Csrf::checkFromHeader('HTTP_X_CUSTOM_CSRF'));
    }
}
