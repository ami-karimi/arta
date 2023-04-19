<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use File;

class radius extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'install:radius';

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
        File::copy(resource_path() . '/radius/default', '/etc/freeradius/3.0/sites-available/default');
        File::copy(resource_path() . '/radius/dynamic-clients', '/etc/freeradius/3.0/sites-available/dynamic-clients');
        File::copy(resource_path() . '/radius/sql', '/etc/freeradius/3.0/mods-available/sql');
        File::copy(resource_path() . '/radius/eap', '/etc/freeradius/3.0/mods-available/eap');
        File::copy(resource_path() . '/radius/queries.conf', '/etc/freeradius/3.0/mods-config/sql/main/mysql/queries.conf');
    }
}
