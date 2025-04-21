<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class LogCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:log-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '定期備份 Log';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 沒做任何事情的 dead code
    }
}
