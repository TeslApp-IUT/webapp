<?php

declare(strict_types=1);

namespace Teslapp\Tests\Unit\Models\Shared\TeslaApi;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Teslapp\Models\Shared\Exceptions\TeslaApiException;
use Teslapp\Models\Shared\TeslaApi\TeslaHttpClient;

#[CoversClass(TeslaHttpClient::class)]
final class TeslaHttpClientTest extends TestCase
{
    /**
     * Invokes the private static TeslaHttpClient::decodeJwtPayload().
     *
     * @return array<string, mixed>
     */
    private static function decode(string $jwt): array
    {
        $method = new ReflectionMethod(TeslaHttpClient::class, 'decodeJwtPayload');

        return $method->invoke(null, $jwt);
    }

    /** Builds a JWT from the given claims, base64url-encoding without padding (as Tesla does). */
    private static function makeJwt(array $claims): string
    {
        $b64url = static fn(string $raw): string => rtrim(
            strtr(base64_encode($raw), '+/', '-_'),
            '=',
        );

        $header = $b64url((string) json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payload = $b64url((string) json_encode($claims));

        return "$header.$payload.signature";
    }

    #[Test]
    public function decodesClaimsFromABase64UrlPayloadWithoutPadding(): void
    {
        $claims = [
            'iss' => 'https://auth.tesla.com/oauth2/v3',
            'sub' => '11111111-2222-3333-4444-555555555555',
            'aud' => '72e768fc-40fb-48e1-8c39-c72ecf11b038',
            'auth_time' => 1717500000,
            'exp' => 1717503600,
            'iat' => 1717500000,
            'email' => 'driver@example.com',
        ];

        self::assertSame($claims, self::decode(self::makeJwt($claims)));
    }

    #[Test]
    public function throwsWhenTheTokenDoesNotHaveThreeSegments(): void
    {
        $this->expectException(TeslaApiException::class);
        self::decode('only.two');
    }

    #[Test]
    public function throwsWhenThePayloadIsNotValidJson(): void
    {
        $notJson = rtrim(strtr(base64_encode('not-json'), '+/', '-_'), '=');

        $this->expectException(TeslaApiException::class);
        self::decode("header.$notJson.signature");
    }
}
