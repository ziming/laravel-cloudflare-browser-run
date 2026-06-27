<?php

use Illuminate\Support\Facades\Http;
use Ziming\LaravelCloudflareBrowserRun\BrowserRunRequest;
use Ziming\LaravelCloudflareBrowserRun\Exceptions\CloudflareBrowserRunRequestException;
use Ziming\LaravelCloudflareBrowserRun\Exceptions\InvalidConfigurationException;
use Ziming\LaravelCloudflareBrowserRun\Facades\LaravelCloudflareBrowserRun;
use Ziming\LaravelCloudflareBrowserRun\LaravelCloudflareBrowserRun as Client;
use Ziming\LaravelCloudflareBrowserRun\Responses\CloudflareBinaryResponse;
use Ziming\LaravelCloudflareBrowserRun\Responses\CloudflareJsonResponse;

beforeEach(function () {
    config()->set('cloudflare-browser-run', [
        'account_id' => 'acc-123',
        'api_token' => 'token-abc',
        'base_url' => 'https://api.cloudflare.com/client/v4',
        'timeout' => 60,
        'cache_ttl' => 5,
    ]);
});

// ---------------------------------------------------------------------------
// Provider / config / facade
// ---------------------------------------------------------------------------

it('binds the client as a singleton', function () {
    expect(app(Client::class))
        ->toBeInstanceOf(Client::class)
        ->and(app(Client::class))->toBe(app(Client::class));
});

it('ships a config file with the documented keys and defaults', function () {
    $config = require __DIR__.'/../config/cloudflare-browser-run.php';

    expect($config)
        ->toHaveKeys(['account_id', 'api_token', 'base_url', 'timeout', 'cache_ttl'])
        ->and($config['base_url'])->toBe('https://api.cloudflare.com/client/v4')
        ->and($config['cache_ttl'])->toBe(5);
});

it('returns a fluent builder from url() and html()', function () {
    expect(LaravelCloudflareBrowserRun::url('https://example.com'))->toBeInstanceOf(BrowserRunRequest::class)
        ->and(LaravelCloudflareBrowserRun::html('<h1>hi</h1>'))->toBeInstanceOf(BrowserRunRequest::class);
});

it('rejects an empty url or html source', function () {
    expect(fn () => LaravelCloudflareBrowserRun::url(''))->toThrow(InvalidArgumentException::class)
        ->and(fn () => LaravelCloudflareBrowserRun::html('   '))->toThrow(InvalidArgumentException::class);
});

// ---------------------------------------------------------------------------
// Request construction
// ---------------------------------------------------------------------------

it('posts to /content with auth header, cacheTTL query and merged body', function () {
    Http::fake(['*' => Http::response(['success' => true, 'result' => '<html></html>'], 200)]);

    $response = LaravelCloudflareBrowserRun::url('https://example.com')
        ->viewport(['width' => 1280, 'height' => 720])
        ->headers(['X-Test' => '1'])
        ->content();

    expect($response)->toBeInstanceOf(CloudflareJsonResponse::class)
        ->and($response->successful())->toBeTrue()
        ->and($response->result())->toBe('<html></html>');

    Http::assertSent(function ($request) {
        return $request->method() === 'POST'
            && $request->url() === 'https://api.cloudflare.com/client/v4/accounts/acc-123/browser-rendering/content?cacheTTL=5'
            && $request->hasHeader('Authorization', 'Bearer token-abc')
            && $request['url'] === 'https://example.com'
            && $request['viewport'] === ['width' => 1280, 'height' => 720]
            && $request['setExtraHTTPHeaders'] === ['X-Test' => '1'];
    });
});

it('sends an html body instead of url', function () {
    Http::fake(['*' => Http::response(['success' => true, 'result' => '<x>'], 200)]);

    LaravelCloudflareBrowserRun::html('<h1>Hi</h1>')->content();

    Http::assertSent(fn ($request) => $request['html'] === '<h1>Hi</h1>' && ! isset($request['url']));
});

it('overrides cacheTTL per request', function () {
    Http::fake(['*' => Http::response(['success' => true, 'result' => []], 200)]);

    LaravelCloudflareBrowserRun::url('https://example.com')->cacheTtl(0)->links();

    Http::assertSent(fn ($request) => str_contains($request->url(), 'cacheTTL=0'));
});

// ---------------------------------------------------------------------------
// Endpoint matrix
// ---------------------------------------------------------------------------

it('targets the right path for simple json endpoints', function (string $method, string $path) {
    Http::fake(['*' => Http::response(['success' => true, 'result' => []], 200)]);

    LaravelCloudflareBrowserRun::url('https://example.com')->{$method}();

    Http::assertSent(fn ($request) => str_starts_with(
        $request->url(),
        "https://api.cloudflare.com/client/v4/accounts/acc-123/browser-rendering/{$path}"
    ));
})->with([
    ['content', 'content'],
    ['links', 'links'],
    ['markdown', 'markdown'],
]);

it('sends formats for /snapshot', function () {
    Http::fake(['*' => Http::response(['success' => true, 'result' => ['content' => '', 'screenshot' => '']], 200)]);

    LaravelCloudflareBrowserRun::url('https://example.com')->snapshot(['content', 'markdown']);

    Http::assertSent(fn ($request) => str_contains($request->url(), '/browser-rendering/snapshot')
        && $request['formats'] === ['content', 'markdown']);
});

it('sends elements for /scrape', function () {
    Http::fake(['*' => Http::response(['success' => true, 'result' => []], 200)]);

    LaravelCloudflareBrowserRun::url('https://example.com')->scrape([['selector' => 'h1']]);

    Http::assertSent(fn ($request) => str_contains($request->url(), '/browser-rendering/scrape')
        && $request['elements'] === [['selector' => 'h1']]);
});

it('sends prompt and response_format for /json', function () {
    Http::fake(['*' => Http::response(['success' => true, 'result' => ['title' => 'x']], 200)]);

    LaravelCloudflareBrowserRun::url('https://example.com')
        ->json('Extract the title', ['type' => 'json_schema', 'schema' => ['type' => 'object']]);

    Http::assertSent(fn ($request) => str_contains($request->url(), '/browser-rendering/json')
        && $request['prompt'] === 'Extract the title'
        && $request['response_format'] === ['type' => 'json_schema', 'schema' => ['type' => 'object']]);
});

// ---------------------------------------------------------------------------
// Binary responses
// ---------------------------------------------------------------------------

it('returns raw bytes and content type for /screenshot', function () {
    Http::fake(['*' => Http::response('PNGBYTES', 200, ['Content-Type' => 'image/png'])]);

    $response = LaravelCloudflareBrowserRun::url('https://example.com')->screenshot(['fullPage' => true]);

    expect($response)->toBeInstanceOf(CloudflareBinaryResponse::class)
        ->and($response->body())->toBe('PNGBYTES')
        ->and($response->contentType())->toBe('image/png')
        ->and($response->status())->toBe(200);

    Http::assertSent(fn ($request) => str_contains($request->url(), '/browser-rendering/screenshot')
        && $request['screenshotOptions'] === ['fullPage' => true]);
});

it('returns raw bytes for /pdf', function () {
    Http::fake(['*' => Http::response('%PDF-1.4', 200, ['Content-Type' => 'application/pdf'])]);

    $response = LaravelCloudflareBrowserRun::url('https://example.com')->pdf(['format' => 'A4']);

    expect($response)->toBeInstanceOf(CloudflareBinaryResponse::class)
        ->and($response->body())->toBe('%PDF-1.4')
        ->and($response->contentType())->toBe('application/pdf');

    Http::assertSent(fn ($request) => $request['pdfOptions'] === ['format' => 'A4']);
});

// ---------------------------------------------------------------------------
// Errors
// ---------------------------------------------------------------------------

it('throws and sends no request when account_id is missing', function () {
    config()->set('cloudflare-browser-run.account_id', null);
    Http::fake();

    expect(fn () => app(Client::class)->url('https://example.com')->content())
        ->toThrow(InvalidConfigurationException::class);

    Http::assertNothingSent();
});

it('throws when api_token is missing', function () {
    config()->set('cloudflare-browser-run.api_token', null);
    Http::fake();

    expect(fn () => app(Client::class)->url('https://example.com')->content())
        ->toThrow(InvalidConfigurationException::class);

    Http::assertNothingSent();
});

it('throws on a 500 response', function () {
    Http::fake(['*' => Http::response(['success' => false, 'errors' => [['message' => 'boom']]], 500)]);

    expect(fn () => LaravelCloudflareBrowserRun::url('https://example.com')->content())
        ->toThrow(CloudflareBrowserRunRequestException::class, 'boom');
});

it('throws a rate limit error with parsed Retry-After on 429', function () {
    Http::fake(['*' => Http::response(
        ['success' => false, 'errors' => [['code' => 1, 'message' => 'rate limited']]],
        429,
        ['Retry-After' => '30'],
    )]);

    try {
        LaravelCloudflareBrowserRun::url('https://example.com')->content();
        $this->fail('Expected a CloudflareBrowserRunRequestException.');
    } catch (CloudflareBrowserRunRequestException $e) {
        expect($e->status())->toBe(429)
            ->and($e->retryAfter())->toBe(30)
            ->and($e->errors())->toBe([['code' => 1, 'message' => 'rate limited']]);
    }
});

it('throws when the envelope reports success: false on a 200', function () {
    Http::fake(['*' => Http::response(['success' => false, 'errors' => [['message' => 'bad']]], 200)]);

    expect(fn () => LaravelCloudflareBrowserRun::url('https://example.com')->content())
        ->toThrow(CloudflareBrowserRunRequestException::class);
});

it('throws on malformed json from a json endpoint', function () {
    Http::fake(['*' => Http::response('not json at all', 200, ['Content-Type' => 'text/plain'])]);

    expect(fn () => LaravelCloudflareBrowserRun::url('https://example.com')->content())
        ->toThrow(CloudflareBrowserRunRequestException::class);
});
