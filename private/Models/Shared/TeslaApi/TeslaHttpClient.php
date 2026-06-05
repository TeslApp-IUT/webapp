<?php

declare(strict_types=1);

namespace Teslapp\Models\Shared\TeslaApi;

use JsonException;
use Teslapp\Models\Shared\Exceptions\TeslaApiException;
use Teslapp\Models\Shared\ValueObjects\AccessToken;

final class TeslaHttpClient
{
    private const DEFAULT_BASE_URL = 'https://proxy.teslapp.feyli.dev';
    private const TIMEOUT_SECONDS = 10;

    private static string $partnerAccessToken = '';
    private static int $partnerAccessTokenExpiresAt = 0;

    /**
     * Sends the request and returns the decoded JSON body.
     *
     * @param string $method The method to use for the request
     * @param string $path The path that will be added to the base URL
     * @param AccessToken|null $token The access token to include as Bearer in the request
     * @param array<string, mixed>|null $body Encoded as JSON for POST; ignored for GET.
     * @param string|null $overrideBaseURL If set, this will override the base URL to add before the path
     *
     * @return array<string, mixed>
     *
     * @throws TeslaApiException
     */
    private static function send(
        string       $method,
        string       $path,
        ?AccessToken $token,
        ?array       $body,
        ?string      $overrideBaseURL
    ): array
    {
        $baseUrl = $overrideBaseURL ?: getenv('TESLACE_BASE_URL') ?: self::DEFAULT_BASE_URL;

        $ch = curl_init($baseUrl . $path);
        if ($ch === false) {
            throw new TeslaApiException(
                "Could not initialise the HTTP request for $method $path.",
            );
        }

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => self::TIMEOUT_SECONDS,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token->value,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
        ];

        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            try {
                $options[CURLOPT_POSTFIELDS] = json_encode($body ?? [], JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new TeslaApiException(
                    "Could not encode the request body for POST $path.",
                    previous: $e,
                );
            }
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);

        if (!is_string($response)) {
            throw new TeslaApiException("Tesla API network error on $method $path : $error.");
        }

        if ($status >= 400) {
            throw new TeslaApiException("Tesla API retruned  HTTP $status on $method $path.");
        }

        try {
            $decoded = json_decode($response, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new TeslaApiException(
                "Tesla API retruned invalid JSON on $method $path.",
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

    private static function getPartnerAccessToken(): string
    {
        if (time() >= self::$partnerAccessTokenExpiresAt) {
            $body = [
                'grant_type' => 'client_credentials',
                'client_id' => getenv('CLIENT_ID'),
                'client_secret' => getenv('CLIENT_SECRET'),
                'audience' => 'https://fleet-api.prd.eu.vn.cloud.tesla.com',
                'scope' => 'openid offline_access user_data vehicle_device_data vehicle_location vehicle_cmds vehicle_charging_cmds vehicle_specs'
            ];
            $res = self::send('POST', '/oauth2/v3/token', null, $body, 'https://fleet-auth.prd.vn.cloud.tesla.com');
            self::$partnerAccessToken = $res['access_token'];
            // Subtract TIMEOUT_SECONDS + another 60 seconds buffer so we never use a token that could expire mid-request.
            self::$partnerAccessTokenExpiresAt = time() + (int)$res['expires_in'] - (self::TIMEOUT_SECONDS + 60);
        }

        return self::$partnerAccessToken;
    }

    private static function getUserAccessToken(): string
    {

    }

    /**
     * @return array<string, mixed>
     *
     * @throws TeslaApiException on a network, HTTP (>= 400), or JSON error.
     */
    public static function get(string $path, AccessToken $token): array
    {
        return self::send('GET', $path, $token, null, null);
    }

    /**
     * @param array<string, mixed> $body
     *
     * @return array<string, mixed>
     *
     * @throws TeslaApiException on a network, HTTP (>= 400), or JSON error.
     */
    public static function post(string $path, AccessToken $token, array $body = []): array
    {
        return self::send('POST', $path, $token, $body, null);
    }
}
