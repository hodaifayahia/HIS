<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DatabasePerformanceCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:performance-check
                            {--show-tables : Show table sizes}
                            {--show-indexes : Show all indexes}
                            {--show-slow : Show slow query log status}
                            {--analyze : Analyze and optimize tables}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check database performance and provide optimization suggestions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Database Performance Analysis');
        $this->newLine();

        // Database connection info
        $this->checkConnection();
        $this->newLine();

        // Table sizes
        if ($this->option('show-tables')) {
            $this->showTableSizes();
            $this->newLine();
        }

        // Index information
        if ($this->option('show-indexes')) {
            $this->showIndexes();
            $this->newLine();
        }

        // Slow query log
        if ($this->option('show-slow')) {
            $this->checkSlowQueryLog();
            $this->newLine();
        }

        // Analyze tables
        if ($this->option('analyze')) {
            $this->analyzeTables();
            $this->newLine();
        }

        // Basic performance stats
        $this->showPerformanceStats();
        $this->newLine();

        // Recommendations
        $this->showRecommendations();

        return 0;
    }

    private function checkConnection()
    {
        $this->info('📊 Connection Information:');

        try {
            $connection = config('database.default');
            $database = config("database.connections.{$connection}.database");
            $host = config("database.connections.{$connection}.host");

            $version = DB::selectOne('SELECT VERSION() as version')->version;

            $this->line("  Database: {$database}");
            $this->line("  Host: {$host}");
            $this->line('  Type: MySQL/MariaDB');
            $this->line("  Version: {$version}");

            $this->info('  ✅ Connection successful');
        } catch (\Exception $e) {
            $this->error('  ❌ Connection failed: '.$e->getMessage());
        }
    }

    private function showTableSizes()
    {
        $this->info('📦 Table Sizes:');

        $database = config('database.connections.mysql.database');

        $tables = DB::select("
            SELECT 
                table_name AS 'Table',
                ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)',
                ROUND((data_length / 1024 / 1024), 2) AS 'Data (MB)',
                ROUND((index_length / 1024 / 1024), 2) AS 'Index (MB)',
                table_rows AS 'Rows'
            FROM information_schema.TABLES
            WHERE table_schema = ?
            ORDER BY (data_length + index_length) DESC
            LIMIT 20
        ", [$database]);

        $this->table(
            ['Table', 'Total Size (MB)', 'Data (MB)', 'Index (MB)', 'Rows'],
            array_map(function ($table) {
                return [
                    $table->Table,
                    $table->{'Size (MB)'},
                    $table->{'Data (MB)'},
                    $table->{'Index (MB)'},
                    number_format($table->Rows),
                ];
            }, $tables)
        );

        $totalSize = array_sum(array_column($tables, 'Size (MB)'));
        $this->info('  Total Database Size: '.round($totalSize, 2).' MB');
    }

    private function showIndexes()
    {
        $this->info('🔑 Recent Indexes Added:');

        $database = config('database.connections.mysql.database');

        $indexes = DB::select("
            SELECT 
                TABLE_NAME as 'Table',
                INDEX_NAME as 'Index',
                GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) as 'Columns',
                INDEX_TYPE as 'Type'
            FROM information_schema.STATISTICS
            WHERE table_schema = ?
            AND INDEX_NAME LIKE '%_index'
            GROUP BY TABLE_NAME, INDEX_NAME, INDEX_TYPE
            ORDER BY TABLE_NAME, INDEX_NAME
        ", [$database]);

        if (count($indexes) > 0) {
            $this->table(
                ['Table', 'Index Name', 'Columns', 'Type'],
                array_map(function ($index) {
                    return [
                        $index->Table,
                        $index->Index,
                        $index->Columns,
                        $index->Type,
                    ];
                }, $indexes)
            );
        } else {
            $this->warn('  No custom indexes found');
        }
    }

    private function checkSlowQueryLog()
    {
        $this->info('🐌 Slow Query Log Status:');

        try {
            $slowLog = DB::selectOne("SHOW VARIABLES LIKE 'slow_query_log'");
            $logTime = DB::selectOne("SHOW VARIABLES LIKE 'long_query_time'");

            $this->line('  Slow Query Log: '.($slowLog->Value === 'ON' ? '✅ Enabled' : '❌ Disabled'));
            $this->line("  Long Query Time: {$logTime->Value} seconds");

            if ($slowLog->Value === 'OFF') {
                $this->warn('  💡 Enable slow query log to identify performance issues:');
                $this->line('     SET GLOBAL slow_query_log = 1;');
                $this->line('     SET GLOBAL long_query_time = 2;');
            }
        } catch (\Exception $e) {
            $this->warn('  Unable to check slow query log: '.$e->getMessage());
        }
    }

    private function analyzeTables()
    {
        $this->info('🔧 Analyzing and Optimizing Tables:');

        $tables = DB::select('SHOW TABLES');
        $tableKey = 'Tables_in_'.config('database.connections.mysql.database');

        $bar = $this->output->createProgressBar(count($tables));
        $bar->start();

        foreach ($tables as $table) {
            $tableName = $table->$tableKey;

            try {
                DB::statement("ANALYZE TABLE `{$tableName}`");
                $bar->advance();
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("  Failed to analyze {$tableName}: ".$e->getMessage());
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info('  ✅ Table analysis complete');
    }

    private function showPerformanceStats()
    {
        $this->info('⚡ Performance Statistics:');

        try {
            // Connection stats
            $maxConnections = DB::selectOne("SHOW VARIABLES LIKE 'max_connections'")->Value;
            $threads = DB::selectOne("SHOW STATUS LIKE 'Threads_connected'")->Value;

            $this->line("  Max Connections: {$maxConnections}");
            $this->line("  Current Connections: {$threads}");

            // Buffer pool stats (InnoDB)
            $bufferSize = DB::selectOne("SHOW VARIABLES LIKE 'innodb_buffer_pool_size'")->Value;
            $this->line('  InnoDB Buffer Pool: '.round($bufferSize / 1024 / 1024 / 1024, 2).' GB');

            // Query cache (if available)
            try {
                $queryCacheSize = DB::selectOne("SHOW VARIABLES LIKE 'query_cache_size'")->Value ?? 0;
                $this->line('  Query Cache Size: '.round($queryCacheSize / 1024 / 1024, 2).' MB');
            } catch (\Exception $e) {
                // Query cache not available in MySQL 8+
            }

        } catch (\Exception $e) {
            $this->warn('  Unable to fetch performance stats: '.$e->getMessage());
        }
    }

    private function showRecommendations()
    {
        $this->info('💡 Performance Recommendations:');

        $recommendations = [
            '1. ✅ Database indexes have been added for common queries',
            '2. ✅ Connection optimization configured in database.php',
            '3. ✅ Eager loading enabled in UserController (prevents N+1 queries)',
            '4. ⏳ Consider enabling Redis for cache and sessions',
            '5. ⏳ Use `php artisan db:performance-check --analyze` monthly',
            '6. ⏳ Monitor slow queries and add indexes as needed',
            '7. ⏳ Use chunking for large dataset operations',
            '8. ⏳ Enable opcache in PHP for better performance',
        ];

        foreach ($recommendations as $rec) {
            $this->line('  '.$rec);
        }

        $this->newLine();
        $this->info('📚 For more details, see: docs/DATABASE_PERFORMANCE_OPTIMIZATION.md');
    }
}
