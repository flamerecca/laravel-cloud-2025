<?php

namespace App\Console\Commands;

use App\Models\Customer;
use Illuminate\Console\Command;

class AddCustomer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:add-customer';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Customer::factory()
            ->count(10)
            ->create();
    }
}
