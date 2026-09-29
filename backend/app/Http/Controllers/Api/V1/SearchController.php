<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class SearchController extends Controller
{
    /**
     * Return current search engine configuration and connectivity status.
     */
    public function status(): JsonResponse
    {
        $driver = config('scout.driver', 'null');
        $typesenseHost = config('scout.typesense.client-settings.nodes.0.host');
        $typesensePort = config('scout.typesense.client-settings.nodes.0.port');
        $typesenseProtocol = config('scout.typesense.client-settings.nodes.0.protocol');

        return response()->json([
            'status' => 'ok',
            'scout_driver' => $driver,
            'is_typesense_active' => $driver === 'typesense',
            'typesense' => [
                'host' => $typesenseHost,
                'port' => $typesensePort,
                'protocol' => $typesenseProtocol,
                'has_api_key' => !empty(config('scout.typesense.client-settings.api_key')),
            ],
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Trigger search reindexing for searchable models.
     */
    public function reindex(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'model' => ['nullable', 'string', 'in:protocol,thread'],
        ]);

        $params = [];
        if (!empty($validated['model'])) {
            $params['--model'] = $validated['model'];
        }

        $exitCode = Artisan::call('search:reindex', $params);
        $output = Artisan::output();

        return response()->json([
            'success' => $exitCode === 0,
            'message' => 'Search reindexing triggered successfully.',
            'model' => $validated['model'] ?? 'all',
            'output' => trim($output),
        ]);
    }
}
