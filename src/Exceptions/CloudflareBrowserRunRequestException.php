<?php

namespace Ziming\LaravelCloudflareBrowserRun\Exceptions;

use Illuminate\Http\Client\Response;
use Throwable;

class CloudflareBrowserRunRequestException extends CloudflareBrowserRunException
{
    /**
     * @param  array<int, mixed>  $errors
     */
    public function __construct(
        string $message,
        protected int $statusCode = 0,
        protected array $errors = [],
        protected ?int $retryAfter = null,
    ) {
        parent::__construct($message, $statusCode);
    }

    /**
     * Build an exception from a failed Cloudflare HTTP response, decoding the
     * error envelope when present without choking on binary/non-JSON bodies.
     */
    public static function fromResponse(Response $response): self
    {
        $status = $response->status();
        $errors = [];
        $message = "Cloudflare Browser Rendering request failed with status {$status}.";

        $data = null;
        try {
            $data = $response->json();
        } catch (Throwable) {
            $data = null;
        }

        if (is_array($data) && isset($data['errors']) && is_array($data['errors'])) {
            $errors = $data['errors'];

            $first = $errors[0] ?? null;
            if (is_array($first) && isset($first['message']) && is_string($first['message'])) {
                $message = $first['message'];
            }
        }

        $retryAfter = null;
        $retryHeader = $response->header('Retry-After');
        if (is_numeric($retryHeader)) {
            $retryAfter = (int) $retryHeader;
        }

        return new self($message, $status, $errors, $retryAfter);
    }

    /**
     * The HTTP status code returned by Cloudflare (0 if unknown).
     */
    public function status(): int
    {
        return $this->statusCode;
    }

    /**
     * The Cloudflare error envelope entries, if any.
     *
     * @return array<int, mixed>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Parsed Retry-After seconds for a 429 response, or null when absent.
     */
    public function retryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
