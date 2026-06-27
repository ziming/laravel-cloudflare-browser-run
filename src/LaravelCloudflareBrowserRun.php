<?php

namespace Ziming\LaravelCloudflareBrowserRun;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Ziming\LaravelCloudflareBrowserRun\Exceptions\CloudflareBrowserRunRequestException;
use Ziming\LaravelCloudflareBrowserRun\Exceptions\InvalidConfigurationException;
use Ziming\LaravelCloudflareBrowserRun\Responses\CloudflareBinaryResponse;
use Ziming\LaravelCloudflareBrowserRun\Responses\CloudflareJsonResponse;
use Ziming\LaravelCloudflareBrowserRun\Responses\CrawlResult;

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
     * Start an asynchronous crawl beginning at the given URL.
     */
    public function crawl(string $url): CrawlRequest
    {
        if (trim($url) === '') {
            throw new InvalidArgumentException('A non-empty URL is required.');
        }

        return new CrawlRequest($this, $url);
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

        return new CloudflareJsonResponse($this->decodeEnvelope($response), $response->status());
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
     * Submit an asynchronous crawl job and return a handle for polling it.
     *
     * @param  array<string, mixed>  $body
     */
    public function submitCrawl(array $body): CrawlJob
    {
        $response = $this->pendingRequest()->post('crawl', $body);
        $data = $this->decodeEnvelope($response);

        $id = $data['result'] ?? null;

        if (! is_string($id) || $id === '') {
            throw new CloudflareBrowserRunRequestException(
                'Cloudflare Browser Rendering did not return a crawl job id.',
                $response->status(),
            );
        }

        return new CrawlJob($this, $id);
    }

    /**
     * Fetch the status and a page of records for a crawl job.
     *
     * @param  array<string, mixed>  $query  Optional: limit, cursor, status filter.
     */
    public function crawlResult(string $jobId, array $query = []): CrawlResult
    {
        $response = $this->pendingRequest()->get('crawl/'.rawurlencode($jobId), $query);
        $data = $this->decodeEnvelope($response);

        $result = $data['result'] ?? [];

        return new CrawlResult(is_array($result) ? $result : []);
    }

    /**
     * Cancel a crawl job. Returns true on success.
     */
    public function cancelCrawl(string $jobId): bool
    {
        $response = $this->pendingRequest()->delete('crawl/'.rawurlencode($jobId));

        $this->decodeEnvelope($response);

        return true;
    }

    /**
     * Validate credentials, then POST a Quick Action (with the cacheTTL query)
     * through Laravel's HTTP client.
     *
     * @param  array<string, mixed>  $body
     */
    protected function send(string $path, array $body, ?int $cacheTtl): Response
    {
        $ttl = $cacheTtl ?? $this->config['cache_ttl'] ?? null;
        $query = $ttl === null ? '' : '?'.http_build_query(['cacheTTL' => (int) $ttl]);

        return $this->pendingRequest()->post($path.$query, $body);
    }

    /**
     * Build a credential-validated, base-URL-scoped HTTP client. Uses Laravel's
     * Http facade so Http::fake() intercepts requests in tests.
     */
    protected function pendingRequest(): PendingRequest
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

        return Http::baseUrl("{$baseUrl}/accounts/{$accountId}/browser-rendering")
            ->timeout($timeout)
            ->withToken((string) $apiToken)
            ->asJson();
    }

    /**
     * Validate a JSON envelope response and return the decoded body, throwing
     * on transport failure, malformed JSON, or success: false.
     *
     * @return array<string, mixed>
     */
    protected function decodeEnvelope(Response $response): array
    {
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

        return $data;
    }
}
