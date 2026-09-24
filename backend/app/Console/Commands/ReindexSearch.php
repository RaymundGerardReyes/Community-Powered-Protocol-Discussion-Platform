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
    protected $signature = 'search:reindex {--model= : Specific model to reindex (Protocol or Thread)}';

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
        $specificModel = $this->option('model');

        $models = match (strtolower((string) $specificModel)) {
            'protocol' => [Protocol::class],
            'thread' => [Thread::class],
            default => [Protocol::class, Thread::class],
        };

        foreach ($models as $modelClass) {
            $modelName = class_basename($modelClass);
            $this->info("Re-indexing {$modelName}...");

            try {
                $this->call('scout:import', ['model' => $modelClass]);
                $this->info("✓ Successfully re-indexed {$modelName}");
            } catch (\Throwable $e) {
                $this->error("Failed to re-index {$modelName}: {$e->getMessage()}");
            }
        }

        $this->info('Search reindexing process completed.');

        return Command::SUCCESS;
    }
}
