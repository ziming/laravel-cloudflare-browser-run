<?php

namespace Ziming\LaravelCloudflareBrowserRun\Responses;

/**
 * Immutable wrapper around a Cloudflare Browser Rendering JSON envelope
 * ({ success, errors, messages, meta, result }).
 */
class CloudflareJsonResponse
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        protected array $data,
        protected int $status,
    ) {}

    public function successful(): bool
    {
        return ($this->data['success'] ?? false) === true;
    }

    /**
     * The `result` payload — shape depends on the endpoint (string, array, ...).
     */
    public function result(): mixed
    {
        return $this->data['result'] ?? null;
    }

    public function status(): int
    {
        return $this->status;
    }

    /**
     * The full decoded envelope, including errors/messages/meta.
     *
     * @return array<string, mixed>
     */
    public function raw(): array
    {
        return $this->data;
    }
}
