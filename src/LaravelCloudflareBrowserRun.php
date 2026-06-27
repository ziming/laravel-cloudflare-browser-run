<?php

namespace Ziming\LaravelCloudflareBrowserRun;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Ziming\LaravelCloudflareBrowserRun\Exceptions\CloudflareBrowserRunRequestException;
use Ziming\LaravelCloudflareBrowserRun\Exceptions\InvalidConfigurationException;
use Ziming\LaravelCloudflareBrowserRun\Responses\CloudflareBinaryResponse;
use Ziming\LaravelCloudflareBrowserRun\Responses\CloudflareJsonResponse;

class LaravelCloudflareBrowserRun
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        protected array $config = [],
    ) {}

    /**
     * Start a request that renders the given URL.
     */
    public function url(string $url): BrowserRunRequest
    {
        if (trim($url) === '') {
            throw new InvalidArgumentException('A non-empty URL is required.');
        }

        return new BrowserRunRequest($this, ['url' => $url]);
    }

    /**
     * Start a request that renders the given raw HTML.
     */
    public function html(string $html): BrowserRunRequest
    {
        if (trim($html) === '') {
            throw new InvalidArgumentException('Non-empty HTML is required.');
        }

        return new BrowserRunRequest($this, ['html' => $html]);
    }

    /**
     * Execute a JSON Quick Action (/content, /snapshot, /scrape, /json,
     * /links, /markdown) and unwrap the Cloudflare envelope.
     *
     * @param  array<string, mixed>  $body
     */
    public function executeJson(string $path, array $body, ?int $cacheTtl = null): CloudflareJsonResponse
    {
        $response = $this->send($path, $body, $cacheTtl);

        if ($response->failed()) {
            throw CloudflareBrowserRunRequestException::fromResponse($response);
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new CloudflareBrowserRunRequestException(
                'Cloudflare Browser Rendering returned a malformed JSON response.',
                $response->status(),
            );
        }

        if (($data['success'] ?? false) !== true) {
            throw new CloudflareBrowserRunRequestException(
                'Cloudflare Browser Rendering reported an unsuccessful response.',
                $response->status(),
                is_array($data['errors'] ?? null) ? $data['errors'] : [],
            );
        }

        return new CloudflareJsonResponse($data, $response->status());
    }

    /**
     * Execute a binary Quick Action (/screenshot, /pdf) and return raw bytes.
     *
     * @param  array<string, mixed>  $body
     */
    public function executeBinary(string $path, array $body, ?int $cacheTtl = null): CloudflareBinaryResponse
    {
        $response = $this->send($path, $body, $cacheTtl);

        if ($response->failed()) {
            throw CloudflareBrowserRunRequestException::fromResponse($response);
        }

        $contentType = $response->header('Content-Type');

        return new CloudflareBinaryResponse(
            $response->body(),
            $response->status(),
            $contentType !== '' ? $contentType : null,
        );
    }

    /**
     * Validate credentials, then POST to the Quick Action endpoint via Laravel's
     * HTTP client (so Http::fake() intercepts it in tests).
     *
     * @param  array<string, mixed>  $body
     */
    protected function send(string $path, array $body, ?int $cacheTtl): Response
    {
        $accountId = $this->config['account_id'] ?? null;
        $apiToken = $this->config['api_token'] ?? null;

        if (blank($accountId)) {
            throw new InvalidConfigurationException('Cloudflare Browser Run "account_id" is not configured.');
        }

        if (blank($apiToken)) {
            throw new InvalidConfigurationException('Cloudflare Browser Run "api_token" is not configured.');
        }

        $baseUrl = rtrim((string) ($this->config['base_url'] ?? 'https://api.cloudflare.com/client/v4'), '/');
        $timeout = (int) ($this->config['timeout'] ?? 60);

        $ttl = $cacheTtl ?? $this->config['cache_ttl'] ?? null;
        $query = $ttl === null ? '' : '?'.http_build_query(['cacheTTL' => (int) $ttl]);

        return Http::baseUrl("{$baseUrl}/accounts/{$accountId}/browser-rendering")
            ->timeout($timeout)
            ->withToken((string) $apiToken)
            ->asJson()
            ->post($path.$query, $body);
    }
}
