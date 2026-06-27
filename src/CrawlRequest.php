<?php

namespace Ziming\LaravelCloudflareBrowserRun;

use Ziming\LaravelCloudflareBrowserRun\Concerns\HasRenderOptions;

/**
 * Fluent builder for an asynchronous Cloudflare Browser Rendering crawl job.
 *
 * Created via LaravelCloudflareBrowserRun::crawl(); call start() to submit the
 * job and receive a CrawlJob handle for polling results.
 */
class CrawlRequest
{
    use HasRenderOptions;

    public function __construct(
        protected LaravelCloudflareBrowserRun $client,
        protected string $url,
    ) {}

    // ------------------------------------------------------------------
    // Crawl scope / output options
    // ------------------------------------------------------------------

    /**
     * Maximum number of pages to crawl (default 10, max 100000).
     */
    public function limit(int $limit): static
    {
        return $this->withOption('limit', $limit);
    }

    /**
     * Maximum link depth to follow (max 100000).
     */
    public function depth(int $depth): static
    {
        return $this->withOption('depth', $depth);
    }

    /**
     * URL discovery source: "all", "sitemaps", or "links".
     */
    public function source(string $source): static
    {
        return $this->withOption('source', $source);
    }

    /**
     * Output formats: any of "HTML", "Markdown", "JSON".
     *
     * @param  list<string>  $formats
     */
    public function formats(array $formats): static
    {
        return $this->withOption('formats', $formats);
    }

    /**
     * When true (default) a headless browser executes page JS; false does a
     * fast HTML fetch without JS.
     */
    public function render(bool $render = true): static
    {
        return $this->withOption('render', $render);
    }

    /**
     * AI extraction options for the JSON format.
     *
     * @param  array{prompt?: string, response_format?: array<string, mixed>, custom_ai?: array<string, mixed>}  $jsonOptions
     */
    public function jsonOptions(array $jsonOptions): static
    {
        return $this->withOption('jsonOptions', $jsonOptions);
    }

    /**
     * Cache freshness window in seconds (default 86400, max 604800).
     */
    public function maxAge(int $seconds): static
    {
        return $this->withOption('maxAge', $seconds);
    }

    /**
     * Only crawl pages modified since this Unix timestamp.
     */
    public function modifiedSince(int $timestamp): static
    {
        return $this->withOption('modifiedSince', $timestamp);
    }

    /**
     * Declared crawl purposes: any of "search", "ai-input", "ai-train".
     *
     * @param  list<string>  $purposes
     */
    public function crawlPurposes(array $purposes): static
    {
        return $this->withOption('crawlPurposes', $purposes);
    }

    /**
     * @param  list<string>  $patterns  Wildcard URL patterns to include.
     */
    public function includePatterns(array $patterns): static
    {
        return $this->nestedOption('includePatterns', $patterns);
    }

    /**
     * @param  list<string>  $patterns  Wildcard URL patterns to exclude.
     */
    public function excludePatterns(array $patterns): static
    {
        return $this->nestedOption('excludePatterns', $patterns);
    }

    public function includeSubdomains(bool $value = true): static
    {
        return $this->nestedOption('includeSubdomains', $value);
    }

    public function includeExternalLinks(bool $value = true): static
    {
        return $this->nestedOption('includeExternalLinks', $value);
    }

    // ------------------------------------------------------------------
    // Terminal
    // ------------------------------------------------------------------

    /**
     * Submit the crawl job and return a handle for polling its results.
     */
    public function start(): CrawlJob
    {
        return $this->client->submitCrawl($this->body());
    }

    /**
     * @return array<string, mixed>
     */
    protected function body(): array
    {
        return array_merge(['url' => $this->url], $this->options);
    }

    /**
     * Set a key inside the nested `options` object.
     */
    protected function nestedOption(string $key, mixed $value): static
    {
        $options = $this->options['options'] ?? [];

        if (! is_array($options)) {
            $options = [];
        }

        $options[$key] = $value;
        $this->options['options'] = $options;

        return $this;
    }
}
