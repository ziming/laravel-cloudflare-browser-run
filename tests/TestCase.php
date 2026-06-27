<?php

namespace Ziming\LaravelCloudflareBrowserRun\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Ziming\LaravelCloudflareBrowserRun\LaravelCloudflareBrowserRunServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            LaravelCloudflareBrowserRunServiceProvider::class,
        ];
    }
}
