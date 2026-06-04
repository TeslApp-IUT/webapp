<?php

declare(strict_types=1);

namespace Teslapp\Models\Shared\TeslaApi;

use JsonException;
use Teslapp\Models\Shared\Exceptions\TeslaApiException;
use Teslapp\Models\Shared\ValueObjects\AccessToken;

final class TeslaHttpClient
{
    private const DEFAULT_BASE_URL = 'https://fleet-api.prd.eu.vn.cloud.tesla.com';
    private const TIMEOUT_SECONDS = 10;

    /** Pure static helper — not meant to be instantiated. */
    private function __construct() {}

    /**
     * Sends the request and returns the decoded JSON body.
     *
     * @param array<string, mixed>|null $body Encoded as JSON for POST; ignored for GET.
     *
     * @return array<string, mixed>
     *
     * @throws TeslaApiException
     */
    private static function send(
        string $method,
        string $path,
        AccessToken $token,
        ?array $body,
    ): array {
        $baseUrl = getenv('TESLACE_BASE_URL') ?: self::DEFAULT_BASE_URL;

        $ch = curl_init($baseUrl . $path);
        if ($ch === false) {
            throw new TeslaApiException(
                "Could not initialise the HTTP request for {$method} {$path}.",
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
                    "Could not encode the request body for POST {$path}.",
                    previous: $e,
                );
            }
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if (!is_string($response)) {
            throw new TeslaApiException("Tesla API network error on {$method} {$path} : {$error}.");
        }

        if ($status >= 400) {
            throw new TeslaApiException("Tesla API retruned  HTTP {$status} on {$method} {$path}.");
        }

        try {
            $decoded = json_decode($response, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new TeslaApiException(
                "Tesla API retruned invalid JSON on {$method} {$path}.",
                previous: $e,
            );
        }

        if (!is_array($decoded)) {
            throw new TeslaApiException(
                "Tesla API returned an unexpected JSON shape on {$method} {$path}.",
            );
        }

        return $decoded;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws TeslaApiException on a network, HTTP (>= 400), or JSON error.
     */
    public static function get(string $path, AccessToken $token): array
    {
        return self::send('GET', $path, $token, null);
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
        return self::send('POST', $path, $token, $body);
    }
}
