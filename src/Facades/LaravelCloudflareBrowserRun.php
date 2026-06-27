<?php

namespace Ziming\LaravelCloudflareBrowserRun\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Ziming\LaravelCloudflareBrowserRun\LaravelCloudflareBrowserRun
 */
class LaravelCloudflareBrowserRun extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Ziming\LaravelCloudflareBrowserRun\LaravelCloudflareBrowserRun::class;
    }
}
