<?php

namespace App\Console\Commands;

use App\Models\Protocol;
use App\Models\Thread;
use Illuminate\Console\Command;

class ReindexSearch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'search:reindex 
                            {--model= : Specific model to reindex (Protocol or Thread)}
                            {--fresh : Flush existing search index before reindexing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reindex searchable models into Typesense via Laravel Scout';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $driver = config('scout.driver', 'null');
        $this->info("Search Driver: {$driver}");

        if ($driver === 'typesense') {
            $node = config('scout.typesense.client-settings.nodes.0', []);
            $host = $node['host'] ?? 'localhost';
            $port = $node['port'] ?? '8108';
            $protocol = $node['protocol'] ?? 'http';
            $apiKey = config('scout.typesense.client-settings.api_key');

            $this->line("Target Node: <comment>{$protocol}://{$host}:{$port}</comment>");
            $this->line("API Key: " . (empty($apiKey) || $apiKey === 'xyz' ? '<fg=red>Not configured</>' : '<fg=green>Configured (' . strlen($apiKey) . ' chars)</>'));

            if ($host !== 'localhost' && $host !== '127.0.0.1') {
                $resolvedIp = gethostbyname($host);
                if ($resolvedIp === $host) {
                    $this->warn("⚠ DNS Warning: Windows cannot resolve host '{$host}'.");
                    $this->line("  Tip: Ensure the node number '-1' is included (e.g. your-cluster-1.a1.typesense.net)");
                    $this->line("  and verify the cluster is in 'HEALTHY' status in Typesense Cloud.");
                } else {
                    $this->line("✓ DNS resolved to IP: <info>{$resolvedIp}</info>");
                }
            }
            $this->newLine();
        }

        $specificModel = $this->option('model');
        $fresh = (bool) $this->option('fresh');

        $models = match (strtolower((string) $specificModel)) {
            'protocol' => [Protocol::class],
            'thread' => [Thread::class],
            default => [Protocol::class, Thread::class],
        };

        foreach ($models as $modelClass) {
            $modelName = class_basename($modelClass);
            $this->info("Re-indexing {$modelName}...");

            try {
                $params = ['model' => $modelClass];
                if ($fresh) {
                    $params['--fresh'] = true;
                }
                $this->call('scout:import', $params);
                $this->info("✓ Successfully re-indexed {$modelName}");
            } catch (\Throwable $e) {
                $this->error("Failed to re-index {$modelName}: {$e->getMessage()}");
            }
        }

        $this->info('Search reindexing process completed.');

        return Command::SUCCESS;
    }
}
