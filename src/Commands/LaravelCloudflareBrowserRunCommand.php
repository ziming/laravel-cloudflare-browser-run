<?php

namespace Ziming\LaravelCloudflareBrowserRun\Commands;

use Illuminate\Console\Command;

class LaravelCloudflareBrowserRunCommand extends Command
{
    public $signature = 'laravel-cloudflare-browser-run';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
