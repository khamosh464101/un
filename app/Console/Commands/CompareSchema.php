<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CompareSchema extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:compare-schema 
                            {--main=mysql : Main database connection name}
                            {--temp=temp : Temporary database connection name}
                            {--output=sql : Output format (sql|table|json)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compare database schemas between two connections';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $mainConnection = $this->option('main');
            $tempConnection = $this->option('temp');
            $outputFormat = $this->option('output');

            $this->info("Comparing schemas between '{$mainConnection}' and '{$tempConnection}'...");

            // Get tables from both databases using Laravel's Schema Builder
            $mainTables = $this->getTablesWithColumns($mainConnection);
            $tempTables = $this->getTablesWithColumns($tempConnection);

            // Compare structures
            $differences = $this->compareStructures($mainTables, $tempTables);

            if (empty($differences)) {
                $this->info("✅ No schema differences found between the two databases.");
                return 0;
            }

            // Display results
            if ($outputFormat === 'table') {
                $this->displayAsTable($differences);
            } elseif ($outputFormat === 'json') {
                $this->displayAsJson($differences);
            } else {
                $this->displayAsSql($differences);
            }

            $this->newLine();
            $this->info("Found " . count($differences) . " differences.");

            return 0;

        } catch (\Exception $e) {
            $this->error("Error comparing schemas: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Get all tables with their columns from a database connection
     */
    private function getTablesWithColumns(string $connection): array
    {
        $schemaBuilder = DB::connection($connection)->getSchemaBuilder();
        
        // Get all table names
        $tables = $schemaBuilder->getTables();
        
        $result = [];
        foreach ($tables as $table) {
            $tableName = $table['name'];
            $columns = $schemaBuilder->getColumns($tableName);
            
            $result[$tableName] = [
                'columns' => $columns,
                'indexes' => $schemaBuilder->getIndexes($tableName),
                'foreign_keys' => $schemaBuilder->getForeignKeys($tableName),
            ];
        }
        
        return $result;
    }

    /**
     * Compare two database structures
     */
    private function compareStructures(array $mainTables, array $tempTables): array
    {
        $differences = [];
        
        $mainTableNames = array_keys($mainTables);
        $tempTableNames = array_keys($tempTables);
        
        // Find tables only in main
        $onlyInMain = array_diff($mainTableNames, $tempTableNames);
        foreach ($onlyInMain as $table) {
            $differences[] = [
                'type' => 'TABLE_ONLY_IN_MAIN',
                'table' => $table,
                'sql' => "CREATE TABLE `{$table}` ..."
            ];
        }
        
        // Find tables only in temp
        $onlyInTemp = array_diff($tempTableNames, $mainTableNames);
        foreach ($onlyInTemp as $table) {
            $differences[] = [
                'type' => 'TABLE_ONLY_IN_TEMP',
                'table' => $table,
                'sql' => "DROP TABLE IF EXISTS `{$table}`"
            ];
        }
        
        // Find tables in both - compare columns
        $commonTables = array_intersect($mainTableNames, $tempTableNames);
        foreach ($commonTables as $table) {
            $mainColumns = $this->getColumnNames($mainTables[$table]['columns']);
            $tempColumns = $this->getColumnNames($tempTables[$table]['columns']);
            
            // Columns only in main
            $colsOnlyInMain = array_diff($mainColumns, $tempColumns);
            foreach ($colsOnlyInMain as $column) {
                $colDef = $this->getColumnDefinition($mainTables[$table]['columns'], $column);
                $differences[] = [
                    'type' => 'COLUMN_ONLY_IN_MAIN',
                    'table' => $table,
                    'column' => $column,
                    'sql' => "ALTER TABLE `{$table}` ADD COLUMN {$colDef}"
                ];
            }
            
            // Columns only in temp
            $colsOnlyInTemp = array_diff($tempColumns, $mainColumns);
            foreach ($colsOnlyInTemp as $column) {
                $differences[] = [
                    'type' => 'COLUMN_ONLY_IN_TEMP',
                    'table' => $table,
                    'column' => $column,
                    'sql' => "ALTER TABLE `{$table}` DROP COLUMN `{$column}`"
                ];
            }
        }
        
        return $differences;
    }

    /**
     * Extract column names from column array
     */
    private function getColumnNames(array $columns): array
    {
        return array_column($columns, 'name');
    }

    /**
     * Get column definition for ALTER TABLE statement
     */
    private function getColumnDefinition(array $columns, string $columnName): string
    {
        foreach ($columns as $col) {
            if ($col['name'] === $columnName) {
                $definition = "`{$col['name']}` {$col['type']}";
                
                if ($col['nullable']) {
                    $definition .= " NULL";
                } else {
                    $definition .= " NOT NULL";
                }
                
                if ($col['default'] !== null) {
                    $definition .= " DEFAULT '{$col['default']}'";
                }
                
                return $definition;
            }
        }
        
        return "`{$columnName}`";
    }

    /**
     * Display differences as SQL statements
     */
    private function displayAsSql(array $differences)
    {
        $this->info("\n📝 SQL Differences:");
        $this->line(str_repeat('-', 50));

        foreach ($differences as $diff) {
            $this->line($diff['sql'] . ";");
        }
    }

    /**
     * Display differences as a table
     */
    private function displayAsTable(array $differences)
    {
        $rows = [];
        foreach ($differences as $diff) {
            $rows[] = [
                $diff['type'],
                $diff['table'] ?? '-',
                $diff['column'] ?? '-',
                substr($diff['sql'], 0, 100) . (strlen($diff['sql']) > 100 ? '...' : '')
            ];
        }
        
        $this->table(['Change Type', 'Table', 'Column', 'SQL Preview'], $rows);
    }

    /**
     * Display differences as JSON
     */
    private function displayAsJson(array $differences)
    {
        $this->line(json_encode($differences, JSON_PRETTY_PRINT));
    }
}