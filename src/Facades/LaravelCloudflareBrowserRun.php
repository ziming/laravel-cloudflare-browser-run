<?php

namespace Ziming\LaravelCloudflareBrowserRun\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Ziming\LaravelCloudflareBrowserRun\LaravelCloudflareBrowserRun
 *
 * @method static \Ziming\LaravelCloudflareBrowserRun\BrowserRunRequest url(string $url)
 * @method static \Ziming\LaravelCloudflareBrowserRun\BrowserRunRequest html(string $html)
 * @method static \Ziming\LaravelCloudflareBrowserRun\CrawlRequest crawl(string $url)
 */
class LaravelCloudflareBrowserRun extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Ziming\LaravelCloudflareBrowserRun\LaravelCloudflareBrowserRun::class;
    }
}
