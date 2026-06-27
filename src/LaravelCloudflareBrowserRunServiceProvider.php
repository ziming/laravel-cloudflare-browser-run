<?php

namespace Ziming\LaravelCloudflareBrowserRun;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Ziming\LaravelCloudflareBrowserRun\Commands\LaravelCloudflareBrowserRunCommand;

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
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_cloudflare_browser_run_table')
            ->hasCommand(LaravelCloudflareBrowserRunCommand::class);
    }
}
