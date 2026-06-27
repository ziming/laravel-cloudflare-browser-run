<?php

namespace Ziming\LaravelCloudflareBrowserRun;

use Ziming\LaravelCloudflareBrowserRun\Concerns\HasRenderOptions;
use Ziming\LaravelCloudflareBrowserRun\Responses\CloudflareBinaryResponse;
use Ziming\LaravelCloudflareBrowserRun\Responses\CloudflareJsonResponse;

/**
 * Fluent, per-request builder for a single Cloudflare Browser Rendering Quick
 * Action.
 *
 * Created via LaravelCloudflareBrowserRun::url()/html(); shared render options
 * come from the HasRenderOptions trait and the terminal endpoint methods
 * execute the request through the client.
 */
class BrowserRunRequest
{
    use HasRenderOptions;

    protected ?int $cacheTtl = null;

    /**
     * @param  array<string, mixed>  $source  Exactly one of ['url' => ...] or ['html' => ...].
     */
    public function __construct(
        protected LaravelCloudflareBrowserRun $client,
        protected array $source,
    ) {}

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
     * @param  array{fullPage?: bool, clip?: array{x: float, y: float, width: float, height: float}, captureBeyondViewport?: bool, omitBackground?: bool, quality?: int, type?: string}  $screenshotOptions  type: png|jpeg|webp
     * @param  array<string, mixed>  $options
     */
    public function screenshot(array $screenshotOptions = [], array $options = []): CloudflareBinaryResponse
    {
        $endpoint = $screenshotOptions === [] ? [] : ['screenshotOptions' => $screenshotOptions];

        return $this->client->executeBinary('screenshot', $this->body($options, $endpoint), $this->cacheTtl);
    }

    /**
     * @param  array{format?: string, width?: string|float, height?: string|float, landscape?: bool, margin?: array{top?: string, right?: string, bottom?: string, left?: string}, displayHeaderFooter?: bool, headerTemplate?: string, footerTemplate?: string, printBackground?: bool, pageRanges?: string, scale?: float, preferCSSPageSize?: bool, omitBackground?: bool, outline?: bool, tagged?: bool, timeout?: int}  $pdfOptions
     * @param  array<string, mixed>  $options
     */
    public function pdf(array $pdfOptions = [], array $options = []): CloudflareBinaryResponse
    {
        $endpoint = $pdfOptions === [] ? [] : ['pdfOptions' => $pdfOptions];

        return $this->client->executeBinary('pdf', $this->body($options, $endpoint), $this->cacheTtl);
    }

    /**
     * @param  list<string>  $formats  At least two of content/screenshot/markdown/accessibilityTree.
     * @param  array<string, mixed>  $options
     */
    public function snapshot(array $formats = [], array $options = []): CloudflareJsonResponse
    {
        $endpoint = $formats === [] ? [] : ['formats' => $formats];

        return $this->client->executeJson('snapshot', $this->body($options, $endpoint), $this->cacheTtl);
    }

    /**
     * @param  list<array{selector: string}>  $elements  e.g. [['selector' => 'h1']]
     * @param  array<string, mixed>  $options
     */
    public function scrape(array $elements, array $options = []): CloudflareJsonResponse
    {
        return $this->client->executeJson('scrape', $this->body($options, ['elements' => $elements]), $this->cacheTtl);
    }

    /**
     * @param  array{type?: string, schema?: array<string, mixed>}  $responseFormat  JSON-schema response_format.
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
