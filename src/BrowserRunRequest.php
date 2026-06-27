<?php

namespace Ziming\LaravelCloudflareBrowserRun;

use Ziming\LaravelCloudflareBrowserRun\Responses\CloudflareBinaryResponse;
use Ziming\LaravelCloudflareBrowserRun\Responses\CloudflareJsonResponse;

/**
 * Fluent, per-request builder for a single Cloudflare Browser Rendering call.
 *
 * Created via LaravelCloudflareBrowserRun::url()/html(); shared render options
 * are set with the fluent methods below and the terminal endpoint methods
 * execute the request through the client.
 */
class BrowserRunRequest
{
    /**
     * Shared top-level Cloudflare render/load options.
     *
     * @var array<string, mixed>
     */
    protected array $options = [];

    protected ?int $cacheTtl = null;

    /**
     * @param  array<string, mixed>  $source  Exactly one of ['url' => ...] or ['html' => ...].
     */
    public function __construct(
        protected LaravelCloudflareBrowserRun $client,
        protected array $source,
    ) {}

    // ------------------------------------------------------------------
    // Shared option setters
    // ------------------------------------------------------------------

    public function withOption(string $key, mixed $value): static
    {
        $this->options[$key] = $value;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function withOptions(array $options): static
    {
        foreach ($options as $key => $value) {
            $this->options[$key] = $value;
        }

        return $this;
    }

    /**
     * @param  array<string, mixed>  $gotoOptions
     */
    public function gotoOptions(array $gotoOptions): static
    {
        return $this->withOption('gotoOptions', $gotoOptions);
    }

    /**
     * @param  array<string, mixed>  $viewport
     */
    public function viewport(array $viewport): static
    {
        return $this->withOption('viewport', $viewport);
    }

    /**
     * @param  array<string, mixed>  $options  Extra keys (hidden, timeout, visible).
     */
    public function waitForSelector(string $selector, array $options = []): static
    {
        return $this->withOption('waitForSelector', array_merge(['selector' => $selector], $options));
    }

    public function waitForTimeout(int $milliseconds): static
    {
        return $this->withOption('waitForTimeout', $milliseconds);
    }

    /**
     * @param  array<int, array<string, mixed>>  $cookies
     */
    public function cookies(array $cookies): static
    {
        return $this->withOption('cookies', $cookies);
    }

    /**
     * @param  array<int, string>  $types
     */
    public function rejectResourceTypes(array $types): static
    {
        return $this->withOption('rejectResourceTypes', $types);
    }

    /**
     * Maps to Cloudflare's setExtraHTTPHeaders option.
     *
     * @param  array<string, string>  $headers
     */
    public function headers(array $headers): static
    {
        return $this->withOption('setExtraHTTPHeaders', $headers);
    }

    public function userAgent(string $userAgent): static
    {
        return $this->withOption('userAgent', $userAgent);
    }

    public function authenticate(string $username, string $password): static
    {
        return $this->withOption('authenticate', ['username' => $username, 'password' => $password]);
    }

    /**
     * @param  array<string, mixed>  $scriptTag  { id?, content?, type?, url? }
     */
    public function addScriptTag(array $scriptTag): static
    {
        $tags = $this->options['addScriptTag'] ?? [];
        $tags[] = $scriptTag;

        return $this->withOption('addScriptTag', $tags);
    }

    /**
     * @param  array<string, mixed>  $styleTag  { content?, url? }
     */
    public function addStyleTag(array $styleTag): static
    {
        $tags = $this->options['addStyleTag'] ?? [];
        $tags[] = $styleTag;

        return $this->withOption('addStyleTag', $tags);
    }

    /**
     * Override the configured cacheTTL for this request (0 disables caching).
     */
    public function cacheTtl(int $seconds): static
    {
        $this->cacheTtl = $seconds;

        return $this;
    }

    // ------------------------------------------------------------------
    // Terminal endpoint methods
    // ------------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $options
     */
    public function content(array $options = []): CloudflareJsonResponse
    {
        return $this->client->executeJson('content', $this->body($options), $this->cacheTtl);
    }

    /**
     * @param  array<string, mixed>  $screenshotOptions  fullPage, clip, type, quality, ...
     * @param  array<string, mixed>  $options
     */
    public function screenshot(array $screenshotOptions = [], array $options = []): CloudflareBinaryResponse
    {
        $endpoint = $screenshotOptions === [] ? [] : ['screenshotOptions' => $screenshotOptions];

        return $this->client->executeBinary('screenshot', $this->body($options, $endpoint), $this->cacheTtl);
    }

    /**
     * @param  array<string, mixed>  $pdfOptions  format, landscape, margin, printBackground, ...
     * @param  array<string, mixed>  $options
     */
    public function pdf(array $pdfOptions = [], array $options = []): CloudflareBinaryResponse
    {
        $endpoint = $pdfOptions === [] ? [] : ['pdfOptions' => $pdfOptions];

        return $this->client->executeBinary('pdf', $this->body($options, $endpoint), $this->cacheTtl);
    }

    /**
     * @param  array<int, string>  $formats  At least two of content/screenshot/markdown/accessibilityTree.
     * @param  array<string, mixed>  $options
     */
    public function snapshot(array $formats = [], array $options = []): CloudflareJsonResponse
    {
        $endpoint = $formats === [] ? [] : ['formats' => $formats];

        return $this->client->executeJson('snapshot', $this->body($options, $endpoint), $this->cacheTtl);
    }

    /**
     * @param  array<int, array<string, mixed>>  $elements  e.g. [['selector' => 'h1']]
     * @param  array<string, mixed>  $options
     */
    public function scrape(array $elements, array $options = []): CloudflareJsonResponse
    {
        return $this->client->executeJson('scrape', $this->body($options, ['elements' => $elements]), $this->cacheTtl);
    }

    /**
     * @param  array<string, mixed>  $responseFormat  JSON-schema response_format.
     * @param  array<string, mixed>  $options
     */
    public function json(?string $prompt = null, array $responseFormat = [], array $options = []): CloudflareJsonResponse
    {
        $endpoint = [];

        if ($prompt !== null) {
            $endpoint['prompt'] = $prompt;
        }

        if ($responseFormat !== []) {
            $endpoint['response_format'] = $responseFormat;
        }

        return $this->client->executeJson('json', $this->body($options, $endpoint), $this->cacheTtl);
    }

    /**
     * @param  array<string, mixed>  $options  visibleLinksOnly, excludeExternalLinks, ...
     */
    public function links(array $options = []): CloudflareJsonResponse
    {
        return $this->client->executeJson('links', $this->body($options), $this->cacheTtl);
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function markdown(array $options = []): CloudflareJsonResponse
    {
        return $this->client->executeJson('markdown', $this->body($options), $this->cacheTtl);
    }

    /**
     * Merge order: source (url|html) -> shared options -> per-call options -> endpoint block.
     *
     * @param  array<string, mixed>  $options
     * @param  array<string, mixed>  $endpoint
     * @return array<string, mixed>
     */
    protected function body(array $options = [], array $endpoint = []): array
    {
        return array_merge($this->source, $this->options, $options, $endpoint);
    }
}
