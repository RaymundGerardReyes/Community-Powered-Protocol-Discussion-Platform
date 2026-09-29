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
        $driver = config('scout.driver');
        $displayDriver = $driver ?: 'null (Search disabled)';
        $this->info("Search Driver: {$displayDriver}");

        if ($driver === null || $driver === 'null' || empty($driver)) {
            $this->warn("⚠ Notice: SCOUT_DRIVER is set to 'null' in your backend/.env file.");
            $this->line("  Scout is currently running in local offline mode. Records will NOT be sent to Typesense Cloud.");
            $this->line("  To upload records to your Typesense Cloud cluster, set:");
            $this->line("    <comment>SCOUT_DRIVER=typesense</comment> in backend/.env");
            $this->newLine();
        }

        if ($driver === 'typesense') {
            $node = config('scout.typesense.client-settings.nodes.0', []);
            $host = $node['host'] ?? 'localhost';
            $port = $node['port'] ?? '8108';
            $protocol = $node['protocol'] ?? 'http';
            $apiKey = config('scout.typesense.client-settings.api_key');
            $hostStatus = config('scout.typesense.client-settings.host_status', 'local');
            $originalHost = config('scout.typesense.client-settings.original_host', $host);

            $this->line("Target Node: <comment>{$protocol}://{$host}:{$port}</comment>");

            $keySource = config('scout.typesense.client-settings.api_key_source', 'TYPESENSE_API_KEY');
            $this->line("API Key: " . (empty($apiKey) || $apiKey === 'xyz' ? '<fg=red>Not configured (using placeholder "xyz")</>' : "<fg=green>Configured via {$keySource} (" . strlen($apiKey) . " chars)</>"));

            if ($hostStatus === 'auto_corrected') {
                $this->info("✓ Auto-detected and formatted host from '{$originalHost}' to: <info>{$host}</info>");
            }

            if ($host !== 'localhost' && $host !== '127.0.0.1') {
                $resolvedIp = gethostbyname($host);
                if ($resolvedIp === $host) {
                    $this->warn("⚠ DNS Resolution Failed for host: '{$host}'");
                    $this->line("  1. Check your Typesense Cloud cluster status: must be 'HEALTHY' (green badge).");
                    $this->line("  2. In your Typesense Cloud dashboard, verify the exact host under 'Nodes' or 'API Keys'.");
                    $this->line("     Expected format: <comment>your-cluster-id-1.a1.typesense.net</comment> or <comment>your-cluster-id.a1.typesense.net</comment>");
                    $this->line("  3. If your cluster was recently created, flush local Windows DNS cache:");
                    $this->line("     <comment>ipconfig /flushdns</comment>");
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
                $msg = $e->getMessage();
                $this->error("Failed to re-index {$modelName}: {$msg}");
                if (str_contains($msg, 'Could not resolve host') || str_contains($msg, 'cURL error 6')) {
                    $this->warn("  Diagnosis: cURL could not resolve host '{$host}'.");
                    $this->line("  - Verify that the cluster is in 'HEALTHY' status in Typesense Cloud.");
                    $this->line("  - Run 'ipconfig /flushdns' in PowerShell to clear Windows DNS cache.");
                } elseif (str_contains($msg, 'Forbidden') || str_contains($msg, '401') || str_contains($msg, '403')) {
                    $this->warn("  Diagnosis: Typesense rejected the API key.");
                    $this->line("  - Ensure TYPESENSE_ADMIN_API_KEY is your 'Admin API Key' from Typesense Cloud (Search-Only keys cannot write data).");
                }
            }
        }

        $this->info('Search reindexing process completed.');

        return Command::SUCCESS;
    }
}
