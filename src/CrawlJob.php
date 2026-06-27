<?php

namespace Ziming\LaravelCloudflareBrowserRun;

use Ziming\LaravelCloudflareBrowserRun\Responses\CrawlResult;

/**
 * Handle to a submitted crawl job. Use result() to poll status/records and
 * cancel() to stop the job. Results are retained by Cloudflare for 14 days.
 */
class CrawlJob
{
    public function __construct(
        protected LaravelCloudflareBrowserRun $client,
        protected string $id,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    /**
     * Fetch the current status and a page of records.
     *
     * @param  array<string, mixed>  $query  Optional: limit, cursor, status filter.
     */
    public function result(array $query = []): CrawlResult
    {
        return $this->client->crawlResult($this->id, $query);
    }

    /**
     * Cancel the crawl job. Returns true on success.
     */
    public function cancel(): bool
    {
        return $this->client->cancelCrawl($this->id);
    }
}
