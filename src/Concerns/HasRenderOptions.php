<?php

namespace Ziming\LaravelCloudflareBrowserRun\Concerns;

/**
 * Shared, top-level Cloudflare render/load options used by both one-shot Quick
 * Action requests and crawl jobs (the latter when render is enabled).
 */
trait HasRenderOptions
{
    /**
     * @var array<string, mixed>
     */
    protected array $options = [];

    public function withOption(string $key, mixed $value): static
    {
        $this->options[$key] = $value;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function withOptions(array $options): static
    {
        foreach ($options as $key => $value) {
            $this->options[$key] = $value;
        }

        return $this;
    }

    /**
     * @param  array{referer?: string, referrerPolicy?: string, timeout?: int, waitUntil?: string|list<string>}  $gotoOptions  waitUntil: load|domcontentloaded|networkidle0|networkidle2
     */
    public function gotoOptions(array $gotoOptions): static
    {
        return $this->withOption('gotoOptions', $gotoOptions);
    }

    /**
     * @param  array{width: int, height: int, deviceScaleFactor?: float, hasTouch?: bool, isLandscape?: bool, isMobile?: bool}  $viewport
     */
    public function viewport(array $viewport): static
    {
        return $this->withOption('viewport', $viewport);
    }

    /**
     * @param  array{hidden?: bool, timeout?: int, visible?: bool}  $options
     */
    public function waitForSelector(string $selector, array $options = []): static
    {
        return $this->withOption('waitForSelector', array_merge(['selector' => $selector], $options));
    }

    public function waitForTimeout(int $milliseconds): static
    {
        return $this->withOption('waitForTimeout', $milliseconds);
    }

    /**
     * @param  list<array{name: string, value: string, url?: string, domain?: string, path?: string, expires?: int|float, httpOnly?: bool, secure?: bool, sameSite?: string}>  $cookies
     */
    public function cookies(array $cookies): static
    {
        return $this->withOption('cookies', $cookies);
    }

    /**
     * @param  array<int, string>  $types
     */
    public function rejectResourceTypes(array $types): static
    {
        return $this->withOption('rejectResourceTypes', $types);
    }

    /**
     * Maps to Cloudflare's setExtraHTTPHeaders option.
     *
     * @param  array<string, string>  $headers
     */
    public function headers(array $headers): static
    {
        return $this->withOption('setExtraHTTPHeaders', $headers);
    }

    public function userAgent(string $userAgent): static
    {
        return $this->withOption('userAgent', $userAgent);
    }

    public function authenticate(string $username, string $password): static
    {
        return $this->withOption('authenticate', ['username' => $username, 'password' => $password]);
    }

    /**
     * @param  array{id?: string, content?: string, type?: string, url?: string}  $scriptTag
     */
    public function addScriptTag(array $scriptTag): static
    {
        $tags = $this->options['addScriptTag'] ?? [];
        $tags[] = $scriptTag;

        return $this->withOption('addScriptTag', $tags);
    }

    /**
     * @param  array{content?: string, url?: string}  $styleTag
     */
    public function addStyleTag(array $styleTag): static
    {
        $tags = $this->options['addStyleTag'] ?? [];
        $tags[] = $styleTag;

        return $this->withOption('addStyleTag', $tags);
    }
}
