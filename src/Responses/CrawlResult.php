<?php

namespace Ziming\LaravelCloudflareBrowserRun\Responses;

/**
 * Immutable snapshot of a crawl job's status and a page of crawled records,
 * wrapping the `result` object returned by GET /crawl/{id}.
 */
class CrawlResult
{
    /**
     * @param  array<string, mixed>  $result
     */
    public function __construct(
        protected array $result,
    ) {}

    public function id(): ?string
    {
        $id = $this->result['id'] ?? null;

        return is_string($id) ? $id : null;
    }

    /**
     * Job status: running, completed, errored, cancelled_due_to_timeout,
     * cancelled_due_to_limits, or cancelled_by_user.
     */
    public function status(): ?string
    {
        $status = $this->result['status'] ?? null;

        return is_string($status) ? $status : null;
    }

    public function isCompleted(): bool
    {
        return $this->status() === 'completed';
    }

    public function isRunning(): bool
    {
        return $this->status() === 'running';
    }

    /**
     * Total number of URLs discovered for the job.
     */
    public function total(): ?int
    {
        $total = $this->result['total'] ?? null;

        return is_int($total) ? $total : null;
    }

    /**
     * Number of URLs crawled so far.
     */
    public function finished(): ?int
    {
        $finished = $this->result['finished'] ?? null;

        return is_int($finished) ? $finished : null;
    }

    /**
     * The crawled page records on this page of results.
     *
     * @return array<int, mixed>
     */
    public function records(): array
    {
        $records = $this->result['records'] ?? [];

        return is_array($records) ? $records : [];
    }

    /**
     * Pagination cursor for the next page, or null when there are no more.
     */
    public function cursor(): ?int
    {
        $cursor = $this->result['cursor'] ?? null;

        return is_int($cursor) ? $cursor : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function raw(): array
    {
        return $this->result;
    }
}
