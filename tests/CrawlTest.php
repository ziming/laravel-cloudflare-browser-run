<?php

use Illuminate\Support\Facades\Http;
use Ziming\LaravelCloudflareBrowserRun\CrawlJob;
use Ziming\LaravelCloudflareBrowserRun\CrawlRequest;
use Ziming\LaravelCloudflareBrowserRun\Exceptions\CloudflareBrowserRunRequestException;
use Ziming\LaravelCloudflareBrowserRun\Facades\LaravelCloudflareBrowserRun;
use Ziming\LaravelCloudflareBrowserRun\LaravelCloudflareBrowserRun as Client;
use Ziming\LaravelCloudflareBrowserRun\Responses\CrawlResult;

beforeEach(function () {
    config()->set('cloudflare-browser-run', [
        'account_id' => 'acc-123',
        'api_token' => 'token-abc',
        'base_url' => 'https://api.cloudflare.com/client/v4',
        'timeout' => 60,
        'cache_ttl' => 5,
    ]);
});

it('returns a crawl builder from crawl()', function () {
    expect(LaravelCloudflareBrowserRun::crawl('https://example.com'))->toBeInstanceOf(CrawlRequest::class);
});

it('rejects an empty crawl url', function () {
    expect(fn () => LaravelCloudflareBrowserRun::crawl('  '))->toThrow(InvalidArgumentException::class);
});

it('submits a crawl with scope, output and shared render options', function () {
    Http::fake(['*' => Http::response(['success' => true, 'result' => 'job-uuid-1'], 200)]);

    $job = LaravelCloudflareBrowserRun::crawl('https://example.com')
        ->limit(50)
        ->depth(3)
        ->source('sitemaps')
        ->formats(['Markdown'])
        ->render(false)
        ->includePatterns(['/docs/*'])
        ->excludePatterns(['/admin/*'])
        ->viewport(['width' => 1024, 'height' => 768])
        ->start();

    expect($job)->toBeInstanceOf(CrawlJob::class)
        ->and($job->id())->toBe('job-uuid-1');

    Http::assertSent(function ($request) {
        return $request->method() === 'POST'
            && $request->url() === 'https://api.cloudflare.com/client/v4/accounts/acc-123/browser-rendering/crawl'
            && $request->hasHeader('Authorization', 'Bearer token-abc')
            && $request['url'] === 'https://example.com'
            && $request['limit'] === 50
            && $request['depth'] === 3
            && $request['source'] === 'sitemaps'
            && $request['formats'] === ['Markdown']
            && $request['render'] === false
            && $request['options'] === ['includePatterns' => ['/docs/*'], 'excludePatterns' => ['/admin/*']]
            && $request['viewport'] === ['width' => 1024, 'height' => 768];
    });
});

it('does not send a cacheTTL query param when submitting a crawl', function () {
    Http::fake(['*' => Http::response(['success' => true, 'result' => 'job-1'], 200)]);

    LaravelCloudflareBrowserRun::crawl('https://example.com')->start();

    Http::assertSent(fn ($request) => ! str_contains($request->url(), 'cacheTTL'));
});

it('polls status and records via the job handle', function () {
    Http::fake([
        '*/browser-rendering/crawl' => Http::response(['success' => true, 'result' => 'job-1'], 200),
        '*/browser-rendering/crawl/*' => Http::response([
            'success' => true,
            'result' => [
                'id' => 'job-1',
                'status' => 'completed',
                'total' => 2,
                'finished' => 2,
                'records' => [
                    ['url' => 'https://example.com', 'status' => 'completed', 'markdown' => '# Hi'],
                ],
                'cursor' => 10,
            ],
        ], 200),
    ]);

    $result = LaravelCloudflareBrowserRun::crawl('https://example.com')->start()->result();

    expect($result)->toBeInstanceOf(CrawlResult::class)
        ->and($result->id())->toBe('job-1')
        ->and($result->status())->toBe('completed')
        ->and($result->isCompleted())->toBeTrue()
        ->and($result->total())->toBe(2)
        ->and($result->finished())->toBe(2)
        ->and($result->records())->toHaveCount(1)
        ->and($result->cursor())->toBe(10);

    Http::assertSent(fn ($request) => $request->method() === 'GET'
        && str_starts_with($request->url(), 'https://api.cloudflare.com/client/v4/accounts/acc-123/browser-rendering/crawl/job-1'));
});

it('passes pagination and status filters when fetching results', function () {
    Http::fake(['*' => Http::response(['success' => true, 'result' => ['records' => []]], 200)]);

    app(Client::class)->crawlResult('job-1', ['cursor' => 10, 'status' => 'completed']);

    Http::assertSent(fn ($request) => str_contains($request->url(), 'cursor=10')
        && str_contains($request->url(), 'status=completed'));
});

it('cancels a crawl by job id', function () {
    Http::fake(['*' => Http::response(['success' => true, 'result' => null], 200)]);

    $cancelled = app(Client::class)->cancelCrawl('job-1');

    expect($cancelled)->toBeTrue();

    Http::assertSent(fn ($request) => $request->method() === 'DELETE'
        && str_ends_with($request->url(), '/browser-rendering/crawl/job-1'));
});

it('throws when the submit response carries no job id', function () {
    Http::fake(['*' => Http::response(['success' => true, 'result' => null], 200)]);

    expect(fn () => LaravelCloudflareBrowserRun::crawl('https://example.com')->start())
        ->toThrow(CloudflareBrowserRunRequestException::class);
});

it('throws when fetching a missing or expired crawl', function () {
    Http::fake(['*' => Http::response(['success' => false, 'errors' => [['message' => 'not found']]], 404)]);

    expect(fn () => app(Client::class)->crawlResult('missing'))
        ->toThrow(CloudflareBrowserRunRequestException::class);
});
