<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DropArchiveTables extends Command
{
    protected $signature = 'db:drop-archive-tables';
    protected $description = 'Drop all tables that start with "archive"';

    public function handle()
    {
        $this->info('Dropping all tables that start with "archive"...');

        // Disable foreign key checks to avoid constraint errors
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

        // Get list of matching tables
        $tables = DB::select("SHOW TABLES LIKE 'archive%'");

        if (empty($tables)) {
            $this->info('No archive tables found.');
            return 0;
        }

        foreach ($tables as $table) {
            // Extract the table name
            $tableName = array_values((array)$table)[0];

            // Drop the table
            DB::statement("DROP TABLE `$tableName`");
            $this->line("Dropped table: $tableName");
        }

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');

        $this->info('All archive tables dropped successfully.');
        return 0;
    }
}
