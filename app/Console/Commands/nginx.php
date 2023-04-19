<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use File;
class nginx extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'install:nginx';

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
        File::copy(resource_path() . '/nginx/default', '/etc/nginx/sites-available/default');
    }
}
