<?php

namespace App\Console\Commands;

use App\Models\ShortUrl;
use Illuminate\Console\Command;

class AddShortUrl extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:add-short-url';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        ShortUrl::factory()
            ->count(1000)
            ->create();
    }
}
