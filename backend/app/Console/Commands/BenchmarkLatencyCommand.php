<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BenchmarkLatencyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'benchmark:latency 
                            {--endpoint=/api/v1/protocols : The API endpoint to benchmark}
                            {--count=50 : The number of sample requests to evaluate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Benchmark API endpoint latency and evaluate against loopback SLO standards (P50 < 100ms, P95 < 250ms, P99 < 500ms)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $endpoint = (string) $this->option('endpoint');
        $count = max(10, (int) $this->option('count'));

        $this->info("================================================================================");
        $this->info(" Local Loopback Latency Benchmark & SLO Validator");
        $this->info("================================================================================");
        $this->line("Target Endpoint:  <fg=cyan>{$endpoint}</>");
        $this->line("Sample Size:      <fg=cyan>{$count} requests</>");
        $this->line("SLO Targets:      P50 < 100ms | P95 < 250ms | P99 < 500ms");
        $this->newLine();

        $kernel = app(\Illuminate\Contracts\Http\Kernel::class);

        // Warm up the kernel and caches
        $warmupRequest = Request::create($endpoint, 'GET');
        $kernel->handle($warmupRequest);

        $durations = [];

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        for ($i = 0; $i < $count; $i++) {
            $request = Request::create($endpoint, 'GET');
            $request->headers->set('Accept', 'application/json');

            $t0 = microtime(true);
            /** @var Response $response */
            $response = $kernel->handle($request);
            $t1 = microtime(true);

            $durationMs = ($t1 - $t0) * 1000;
            $durations[] = $durationMs;

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        sort($durations);

        $p50Index = (int) floor($count * 0.50);
        $p95Index = (int) floor($count * 0.95);
        $p99Index = (int) min(floor($count * 0.99), $count - 1);

        $p50 = round($durations[$p50Index], 2);
        $p95 = round($durations[$p95Index], 2);
        $p99 = round($durations[$p99Index], 2);
        $min = round(min($durations), 2);
        $max = round(max($durations), 2);
        $avg = round(array_sum($durations) / count($durations), 2);

        $p50Pass = $p50 < 100;
        $p95Pass = $p95 < 250;
        $p99Pass = $p99 < 500;
        $overallPass = $p50Pass && $p95Pass && $p99Pass;

        $rubric = $this->classifyLatency($p50);

        $this->table(
            ['Metric', 'Measured Value', 'SLO Threshold', 'Status'],
            [
                ['P50 (Median)', "{$p50} ms", '< 100 ms', $p50Pass ? '<fg=green>PASS</>' : '<fg=red>FAIL</>'],
                ['P95', "{$p95} ms", '< 250 ms', $p95Pass ? '<fg=green>PASS</>' : '<fg=red>FAIL</>'],
                ['P99', "{$p99} ms", '< 500 ms', $p99Pass ? '<fg=green>PASS</>' : '<fg=red>FAIL</>'],
                ['Average', "{$avg} ms", '—', '—'],
                ['Min', "{$min} ms", '—', '—'],
                ['Max', "{$max} ms", '—', '—'],
            ]
        );

        $this->line("Classification: <options=bold>{$rubric['label']}</> — {$rubric['description']}");
        $this->newLine();

        if ($overallPass) {
            $this->info("✓ Endpoint satisfies all loopback performance SLO criteria!");
            return Command::SUCCESS;
        }

        $this->warn("⚠ Endpoint exceeded one or more SLO latency thresholds.");
        return Command::FAILURE;
    }

    /**
     * Classify latency according to the user's project standard rubric.
     */
    protected function classifyLatency(float $ms): array
    {
        if ($ms < 50) {
            return ['label' => '<fg=green>EXCELLENT</>', 'description' => 'Very responsive (< 50 ms)'];
        }
        if ($ms <= 100) {
            return ['label' => '<fg=green>VERY GOOD</>', 'description' => 'Strong (50–100 ms)'];
        }
        if ($ms <= 250) {
            return ['label' => '<fg=yellow>GOOD</>', 'description' => 'Acceptable/healthy (100–250 ms)'];
        }
        if ($ms <= 500) {
            return ['label' => '<fg=yellow>MODERATE</>', 'description' => 'Investigate if consistently occurring (250–500 ms)'];
        }
        if ($ms <= 1000) {
            return ['label' => '<fg=red>SLOW</>', 'description' => 'Should investigate (> 500 ms)'];
        }

        return ['label' => '<fg=red;options=bold>VERY SLOW</>', 'description' => 'Strong investigation required (> 1 s)'];
    }
}
