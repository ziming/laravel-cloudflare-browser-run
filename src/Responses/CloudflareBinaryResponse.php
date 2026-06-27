<?php

namespace Ziming\LaravelCloudflareBrowserRun\Responses;

/**
 * Immutable wrapper around a binary Cloudflare Browser Rendering response
 * (the /screenshot and /pdf endpoints return raw bytes, not JSON).
 */
class CloudflareBinaryResponse
{
    public function __construct(
        protected string $body,
        protected int $status,
        protected ?string $contentType = null,
    ) {}

    /**
     * The raw response bytes (PNG/JPEG image or PDF document).
     */
    public function body(): string
    {
        return $this->body;
    }

    public function contentType(): ?string
    {
        return $this->contentType;
    }

    public function status(): int
    {
        return $this->status;
    }

    /**
     * Write the bytes to disk. Returns false on failure.
     */
    public function save(string $path): bool
    {
        return file_put_contents($path, $this->body) !== false;
    }
}
