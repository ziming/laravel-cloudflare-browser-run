# Laravel Package for Cloudflare Browser Rendering

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ziming/laravel-cloudflare-browser-run.svg?style=flat-square)](https://packagist.org/packages/ziming/laravel-cloudflare-browser-run)
[![GitHub Tests Action Status](https://github.com/ziming/laravel-cloudflare-browser-run/actions/workflows/run-tests.yml/badge.svg)](https://github.com/ziming/laravel-cloudflare-browser-run/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://github.com/ziming/laravel-cloudflare-browser-run/actions/workflows/fix-php-code-style-issues.yml/badge.svg)](https://github.com/ziming/laravel-cloudflare-browser-run/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/ziming/laravel-cloudflare-browser-run.svg?style=flat-square)](https://packagist.org/packages/ziming/laravel-cloudflare-browser-run)

A fluent Laravel client for [Cloudflare Browser Rendering](https://developers.cloudflare.com/browser-run/) "Quick Actions" — the REST endpoints that render a URL or raw HTML and return content, a screenshot, a PDF, a snapshot, scraped elements, AI-extracted JSON, links, or Markdown.

It is built on Laravel's HTTP client, so every call is interceptable with `Http::fake()` in your tests.

```php
use Ziming\LaravelCloudflareBrowserRun\Facades\LaravelCloudflareBrowserRun;

$png = LaravelCloudflareBrowserRun::url('https://example.com')
    ->viewport(['width' => 1280, 'height' => 720])
    ->screenshot(['fullPage' => true]);

$png->save(storage_path('app/example.png'));
```

## Installation

Install the package via composer:

```bash
composer require ziming/laravel-cloudflare-browser-run
```

Publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-cloudflare-browser-run-config"
```

## Configuration

Add your Cloudflare credentials to `.env`. The API token needs the **Browser Rendering: Edit** permission.

```dotenv
CLOUDFLARE_BROWSER_RUN_ACCOUNT_ID=your-account-id
CLOUDFLARE_BROWSER_RUN_API_TOKEN=your-api-token
```

The published `config/cloudflare-browser-run.php`:

```php
return [
    'account_id' => env('CLOUDFLARE_BROWSER_RUN_ACCOUNT_ID'),
    'api_token'  => env('CLOUDFLARE_BROWSER_RUN_API_TOKEN'),
    'base_url'   => env('CLOUDFLARE_BROWSER_RUN_BASE_URL', 'https://api.cloudflare.com/client/v4'),
    'timeout'    => (int) env('CLOUDFLARE_BROWSER_RUN_TIMEOUT', 60),
    'cache_ttl'  => (int) env('CLOUDFLARE_BROWSER_RUN_CACHE_TTL', 5),
];
```

## Usage

Start a request from a URL or raw HTML, chain shared render options, then call one of the endpoint methods. You can use the `LaravelCloudflareBrowserRun` facade or inject `Ziming\LaravelCloudflareBrowserRun\LaravelCloudflareBrowserRun`.

### JSON endpoints

These return a `CloudflareJsonResponse` wrapping the Cloudflare envelope (`successful()`, `result()`, `status()`, `raw()`).

```php
use Ziming\LaravelCloudflareBrowserRun\Facades\LaravelCloudflareBrowserRun;

// Rendered HTML
$html = LaravelCloudflareBrowserRun::url('https://example.com')->content()->result();

// Snapshot (request at least two formats)
$snapshot = LaravelCloudflareBrowserRun::url('https://example.com')
    ->snapshot(['content', 'screenshot', 'markdown'])
    ->result();

// Scrape specific elements
$data = LaravelCloudflareBrowserRun::url('https://example.com')
    ->scrape([['selector' => 'h1'], ['selector' => '.price']])
    ->result();

// AI-extracted JSON
$json = LaravelCloudflareBrowserRun::url('https://example.com')
    ->json('Extract the product name and price', [
        'type' => 'json_schema',
        'schema' => ['type' => 'object'],
    ])
    ->result();

// Links and Markdown
$links = LaravelCloudflareBrowserRun::url('https://example.com')->links()->result();
$markdown = LaravelCloudflareBrowserRun::url('https://example.com')->markdown()->result();
```

### Binary endpoints

`/screenshot` and `/pdf` return raw bytes in a `CloudflareBinaryResponse` (`body()`, `contentType()`, `status()`, `save($path)`).

```php
$pdf = LaravelCloudflareBrowserRun::html('<h1>Invoice</h1>')
    ->pdf(['format' => 'A4', 'printBackground' => true]);

return response($pdf->body(), 200, ['Content-Type' => $pdf->contentType()]);
```

### Shared render options

Chainable before any endpoint method:

```php
LaravelCloudflareBrowserRun::url('https://example.com')
    ->gotoOptions(['waitUntil' => 'networkidle0', 'timeout' => 30000])
    ->viewport(['width' => 1280, 'height' => 720])
    ->waitForSelector('#app')
    ->cookies([['name' => 'session', 'value' => 'abc', 'domain' => 'example.com']])
    ->headers(['X-Custom' => 'value'])          // -> setExtraHTTPHeaders
    ->rejectResourceTypes(['image', 'font'])
    ->userAgent('MyBot/1.0')
    ->cacheTtl(0)                                // override default cacheTTL (0 disables)
    ->withOption('emulateMediaType', 'screen')  // any other Cloudflare option
    ->markdown();
```

### Error handling

| Exception | When |
|-----------|------|
| `InvalidConfigurationException` | `account_id`/`api_token` missing — thrown before any HTTP request |
| `CloudflareBrowserRunRequestException` | non-2xx response, malformed JSON, or `success: false` |

`CloudflareBrowserRunRequestException` exposes `status()`, `errors()`, and `retryAfter()` (parsed from the `Retry-After` header on `429`). All three extend `CloudflareBrowserRunException`. No automatic retries are performed.

```php
use Ziming\LaravelCloudflareBrowserRun\Exceptions\CloudflareBrowserRunRequestException;

try {
    $shot = LaravelCloudflareBrowserRun::url('https://example.com')->screenshot();
} catch (CloudflareBrowserRunRequestException $e) {
    if ($e->status() === 429) {
        // back off for $e->retryAfter() seconds
    }
}
```

### Testing

Because the package uses Laravel's HTTP client, you can fake Cloudflare entirely:

```php
use Illuminate\Support\Facades\Http;

Http::fake([
    '*browser-rendering/content' => Http::response([
        'success' => true,
        'result' => '<html>...</html>',
    ]),
]);
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [ziming](https://github.com/ziming)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
