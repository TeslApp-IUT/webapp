<?php

declare(strict_types=1);

namespace Teslapp\Models\Shared\TeslaApi;

use JsonException;
use Teslapp\Models\Auth\AuthRepository;
use Teslapp\Models\Database;
use Teslapp\Models\Shared\Exceptions\TeslaApiException;
use Teslapp\Models\Shared\TokenCipher;
use Teslapp\Models\Shared\ValueObjects\AccessToken;

final class TeslaHttpClient
{
    private const DEFAULT_BASE_URL = 'https://proxy.teslapp.feyli.dev';
    private const TIMEOUT_SECONDS = 10;

    private const FLEET_AUTH_BASE_URL = 'https://fleet-auth.prd.vn.cloud.tesla.com';
    private const FLEET_API_AUDIENCE = 'https://fleet-api.prd.eu.vn.cloud.tesla.com';
    private const TOKEN_PATH = '/oauth2/v3/token';

    /** Tesla offline refresh tokens are long-lived; the token response carries no refresh expiry. */
    private const REFRESH_TTL_SECONDS = 90 * 24 * 60 * 60;

    private static string $partnerAccessToken = '';
    private static int $partnerAccessTokenExpiresAt = 0;

    /**
     * Sends the request and returns the decoded JSON body.
     *
     * @param string $method The method to use for the request
     * @param string $path The path that will be added to the base URL
     * @param AccessToken|null $token The access token to include as Bearer in the request
     * @param array<string, mixed>|null $body Encoded as JSON or form-urlencoded for POST; ignored for GET.
     * @param string|null $overrideBaseURL If set, this will override the base URL to add before the path
     * @param bool $formEncoded If true, encodes the body as application/x-www-form-urlencoded instead of JSON.
     *
     * @return array<string, mixed>
     *
     * @throws TeslaApiException
     */
    private static function send(
        string $method,
        string $path,
        ?AccessToken $token,
        ?array $body,
        ?string $overrideBaseURL,
        bool $formEncoded = false,
    ): array {
        $baseUrl = $overrideBaseURL ?: getenv('TESLACE_BASE_URL') ?: self::DEFAULT_BASE_URL;

        $ch = curl_init($baseUrl . $path);
        if ($ch === false) {
            throw new TeslaApiException("Could not initialise the HTTP request for $method $path.");
        }

        $contentType = $formEncoded ? 'application/x-www-form-urlencoded' : 'application/json';
        $headers = ["Content-Type: $contentType", 'Accept: application/json'];
        // Token requests (partner/user OAuth) are unauthenticated; only add the header when present.
        if ($token !== null) {
            $headers[] = 'Authorization: Bearer ' . $token->value;
        }

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => self::TIMEOUT_SECONDS,
            CURLOPT_HTTPHEADER => $headers,
        ];

        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            if ($formEncoded) {
                $options[CURLOPT_POSTFIELDS] = http_build_query($body ?? []);
            } else {
                try {
                    // Tesla (and the signing proxy) parse the request body as a JSON OBJECT.
                    // PHP's json_encode([]) yields "[]" (a JSON array), which the proxy rejects
                    // for parameterless commands (door_lock, door_unlock, honk_horn, flash_lights,
                    // charge_port_door_open/close, wake_up)
                    // Force "{}" when there is no payload so every command carries a valid object.
                    $options[CURLOPT_POSTFIELDS] = $body
                        ? json_encode($body, JSON_THROW_ON_ERROR)
                        : '{}';
                } catch (JsonException $e) {
                    throw new TeslaApiException(
                        "Could not encode the request body for POST $path.",
                        previous: $e,
                    );
                }
            }
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);

        if (!is_string($response)) {
            throw new TeslaApiException("Tesla API network error on $method $path : $error.");
        }

        if ($status >= 400) {
            error_log("Tesla API error $status on $method $path: $response");
            throw new TeslaApiException("Tesla API returned HTTP $status on $method $path.");
        }

        try {
            $decoded = json_decode($response, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new TeslaApiException(
                "Tesla API returned invalid JSON on $method $path.",
                previous: $e,
            );
        }

        if (!is_array($decoded)) {
            throw new TeslaApiException(
                "Tesla API returned an unexpected JSON shape on $method $path.",
            );
        }

        return $decoded;
    }

    private static function getPartnerAccessToken(): AccessToken
    {
        if (time() >= self::$partnerAccessTokenExpiresAt) {
            $body = [
                'grant_type' => 'client_credentials',
                'client_id' => getenv('CLIENT_ID'),
                'client_secret' => getenv('CLIENT_SECRET'),
                'audience' => 'https://fleet-api.prd.eu.vn.cloud.tesla.com',
                'scope' =>
                    'openid email offline_access user_data vehicle_device_data vehicle_location vehicle_cmds vehicle_charging_cmds vehicle_specs',
            ];
            $res = self::send(
                'POST',
                '/oauth2/v3/token',
                null,
                $body,
                'https://fleet-auth.prd.vn.cloud.tesla.com',
                formEncoded: true,
            );
            self::$partnerAccessToken = $res['access_token'];
            // Subtract TIMEOUT_SECONDS + another 60 seconds buffer so we never use a token that could expire mid-request.
            self::$partnerAccessTokenExpiresAt =
                time() + (int) $res['expires_in'] - (self::TIMEOUT_SECONDS + 60);
        }

        return new AccessToken(self::$partnerAccessToken);
    }

    /**
     * Returns a working user access token.
     *
     * If the session already holds a non-expired token it is returned as-is; otherwise the token
     * is refreshed via the stored refresh token, the new material is persisted (session + DB), and
     * the fresh access token is returned.
     *
     * @throws TeslaApiException if no valid token and no usable refresh token are available.
     */
    public static function getUserAccessToken(): AccessToken
    {
        $cached = $_SESSION['access_token'] ?? null;
        $expiresAt = (int) ($_SESSION['access_token_expires_at'] ?? 0);

        if (is_string($cached) && $cached !== '' && time() < $expiresAt) {
            return new AccessToken($cached);
        }

        $refreshToken = $_SESSION['refresh_token'] ?? null;
        if (!is_string($refreshToken) || $refreshToken === '') {
            throw new TeslaApiException('No authenticated user: missing refresh token in session.');
        }

        $body = [
            'grant_type' => 'refresh_token',
            'client_id' => getenv('CLIENT_ID'),
            'refresh_token' => $refreshToken,
        ];
        $res = self::send(
            'POST',
            self::TOKEN_PATH,
            null,
            $body,
            self::FLEET_AUTH_BASE_URL,
            formEncoded: true,
        );

        return self::persistTokenResponse($res);
    }

    /**
     * Exchanges the OAuth authorization code (from the /auth/callback redirect) for the initial
     * user tokens, persists them (session + DB), and returns the access token.
     *
     * @throws TeslaApiException
     */
    public static function exchangeCodeForUserToken(string $code): AccessToken
    {
        $appUrl = rtrim(getenv('APP_URL') ?: 'http://localhost', '/');
        $redirectUri = $appUrl . '/auth/callback';

        // PKCE verifier + replay nonce stored by AuthController::handleGet(); consume them.
        $codeVerifier = $_SESSION['oauth_code_verifier'] ?? '';
        unset($_SESSION['oauth_code_verifier']);
        $expectedNonce = $_SESSION['oauth_nonce'] ?? null;
        unset($_SESSION['oauth_nonce']);

        $body = [
            'grant_type' => 'authorization_code',
            'client_id' => getenv('CLIENT_ID'),
            'client_secret' => getenv('CLIENT_SECRET'),
            'code' => $code,
            'audience' => 'https://fleet-api.prd.eu.vn.cloud.tesla.com',
            'redirect_uri' => $redirectUri,
            'code_verifier' => is_string($codeVerifier) ? $codeVerifier : '',
        ];
        $res = self::send(
            'POST',
            self::TOKEN_PATH,
            null,
            $body,
            self::FLEET_AUTH_BASE_URL,
            formEncoded: true,
        );

        return self::persistTokenResponse($res, is_string($expectedNonce) ? $expectedNonce : null);
    }

    /**
     * Stores freshly fetched token material in the session and the database, then returns the
     * access token. Shared by the code-exchange and refresh flows.
     *
     * The user identity is the Tesla `sub` claim (so `users.id = sub`). The id_token carries that
     * claim and is decoded into the `jwt` table; it is present on the initial exchange and on a
     * refresh that returns the `openid` scope. On a refresh without an id_token the `sub` is taken
     * from the session so the tokens can still be persisted.
     *
     * @param array<string, mixed> $res The decoded token endpoint response.
     *
     * @throws TeslaApiException
     */
    private static function persistTokenResponse(
        array $res,
        ?string $expectedNonce = null,
    ): AccessToken {
        $accessToken = $res['access_token'] ?? null;
        $refreshToken = $res['refresh_token'] ?? null;
        $expiresIn = (int) ($res['expires_in'] ?? 0);

        if (!is_string($accessToken) || $accessToken === '') {
            throw new TeslaApiException('Token endpoint response is missing the access token.');
        }
        if (!is_string($refreshToken) || $refreshToken === '') {
            throw new TeslaApiException('Token endpoint response is missing the refresh token.');
        }

        $now = time();
        $accessExpiresAt = $now + $expiresIn;
        $refreshExpiresAt = $now + self::REFRESH_TTL_SECONDS;

        $claims = null;
        $idToken = $res['id_token'] ?? null;
        if (is_string($idToken) && $idToken !== '') {
            $claims = self::decodeJwtPayload($idToken);
        }

        // Replay protection: when a nonce was sent at /authorize, the id_token must echo it.
        if ($expectedNonce !== null) {
            $tokenNonce = isset($claims['nonce']) ? (string) $claims['nonce'] : '';
            if ($tokenNonce === '' || !hash_equals($expectedNonce, $tokenNonce)) {
                throw new TeslaApiException('OAuth id_token nonce mismatch.');
            }
        }

        $sub = isset($claims['sub'])
            ? (string) $claims['sub']
            : (string) ($_SESSION['user_id'] ?? '');

        if ($sub !== '') {
            $cipher = new TokenCipher(
                base64_decode(getenv('TESLAPP_TOKEN_ENCRYPTION_KEY'), strict: true),
            );
            $accessEnc = $cipher->encrypt($accessToken);
            $refreshEnc = $cipher->encrypt($refreshToken);

            $repository = new AuthRepository(Database::pdo());

            if ($claims !== null) {
                $aud = $claims['aud'] ?? '';
                if (is_array($aud)) {
                    $aud = (string) ($aud[0] ?? '');
                }

                $_SESSION['tmp_user_email'] = $claims['email'] ?? '';

                //TO-DO: change the database insertion
                /**
                $repository->ensureUser(
                    $sub,
                    (string) ($claims['email'] ?? ''),
                    (string) ($claims['given_name'] ?? ''),
                    (string) ($claims['family_name'] ?? ''),
                );
                **/
                $repository->saveJwt(
                    $sub,
                    (string) ($claims['iss'] ?? ''),
                    (string) $aud,
                    (int) ($claims['auth_time'] ?? $now),
                    (int) ($claims['exp'] ?? $accessExpiresAt),
                    (int) ($claims['iat'] ?? $now),
                );
            }

            $repository->saveOAuthToken(
                $sub,
                $accessEnc['ciphertext'],
                $accessEnc['nonce'],
                $accessExpiresAt,
                $refreshEnc['ciphertext'],
                $refreshEnc['nonce'],
                $refreshExpiresAt,
            );

            $_SESSION['user_id'] = $sub;
        }

        $_SESSION['access_token'] = $accessToken;
        $_SESSION['refresh_token'] = $refreshToken;
        // Same safety buffer as the partner token so we refresh before the real expiry.
        $_SESSION['access_token_expires_at'] = $accessExpiresAt - (self::TIMEOUT_SECONDS + 60);

        return new AccessToken($accessToken);
    }

    /**
     * Decodes the payload (claims) of a JWT. The signature is NOT verified — the token comes
     * straight from Tesla's token endpoint over TLS, so this only extracts the claims.
     *
     * @return array<string, mixed>
     *
     * @throws TeslaApiException if the token is malformed.
     */
    private static function decodeJwtPayload(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new TeslaApiException('Malformed JWT: expected three segments.');
        }

        $payload = strtr($parts[1], '-_', '+/');
        $payload .= str_repeat('=', (4 - (strlen($payload) % 4)) % 4);

        $decoded = base64_decode($payload, strict: true);
        if ($decoded === false) {
            throw new TeslaApiException('Malformed JWT: payload is not valid base64url.');
        }

        try {
            $claims = json_decode($decoded, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new TeslaApiException('Malformed JWT: payload is not valid JSON.', previous: $e);
        }

        if (!is_array($claims)) {
            throw new TeslaApiException('Malformed JWT: payload is not a JSON object.');
        }

        return $claims;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws TeslaApiException on a network, HTTP (>= 400), or JSON error.
     */
    public static function get(string $path): array
    {
        return self::send('GET', $path, self::getUserAccessToken(), null, null);
    }

    /**
     * @param array<string, mixed> $body
     *
     * @return array<string, mixed>
     *
     * @throws TeslaApiException on a network, HTTP (>= 400), or JSON error.
     */
    public static function post(string $path, array $body = []): array
    {
        return self::send('POST', $path, self::getUserAccessToken(), $body, null);
    }
}
