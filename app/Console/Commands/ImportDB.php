<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportDB extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-db {--db_version=1 : Version de la base de datos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     * @throws \Exception
     */
    public function handle(): void
    {
        $version = $this->option('db_version');
        $this->info('Importing database version '.$version);
        $this->importDB($version);
    }

    /**
     * @throws \Exception
     */
    public function importDB($version): void
    {
        $this->info('Importing database version '.$version);

        switch($version){
            case '1':
                $this->importDBv1();
                break;
            default:
                $this->error('Version not found');
                exit(1);
        }

        $this->info('Database imported successfully');
    }

    /**
     * @throws \Exception
     */
    public function importDBv1(): bool
    {
        return V1ImportDB::version(1);
    }
}
