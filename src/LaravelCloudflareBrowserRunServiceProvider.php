<?php

namespace Ziming\LaravelCloudflareBrowserRun;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelCloudflareBrowserRunServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-cloudflare-browser-run')
            ->hasConfigFile();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(
            LaravelCloudflareBrowserRun::class,
            fn ($app) => new LaravelCloudflareBrowserRun((array) $app['config']->get('cloudflare-browser-run', []))
        );

        $this->app->alias(LaravelCloudflareBrowserRun::class, 'cloudflare-browser-run');
    }
}
