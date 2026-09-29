<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Typesense\Client as TypesenseClient;

class SearchController extends Controller
{
    /**
     * Check Typesense search sidecar status and cluster health.
     */
    public function status(): JsonResponse
    {
        $driver = config('scout.driver');
        $configured = $driver === 'typesense';

        $healthy = false;
        $details = null;

        if ($configured) {
            try {
                /** @var TypesenseClient $client */
                $client = app(TypesenseClient::class);
                $health = $client->health->retrieve();
                $healthy = ($health['ok'] ?? false) === true;
                $details = $health;
            } catch (\Throwable $e) {
                $healthy = false;
                $details = ['error' => $e->getMessage()];
            }
        }

        return response()->json([
            'status' => $healthy ? 'healthy' : ($configured ? 'unreachable' : 'disabled'),
            'scout_driver' => $driver,
            'is_typesense_active' => $healthy,
            'typesense' => [
                'host' => config('scout.typesense.client-settings.nodes.0.host'),
                'port' => config('scout.typesense.client-settings.nodes.0.port'),
                'protocol' => config('scout.typesense.client-settings.nodes.0.protocol'),
                'has_api_key' => !empty(config('scout.typesense.client-settings.api_key')),
            ],
            'details' => $details,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Trigger on-demand reindexing of searchable models into Typesense.
     */
    public function reindex(Request $request): JsonResponse
    {
        $model = $request->input('model');
        $params = [];

        if ($model) {
            $params['--model'] = $model;
        }

        try {
            $exitCode = Artisan::call('search:reindex', $params);
            $output = Artisan::output();

            return response()->json([
                'success' => $exitCode === 0,
                'message' => 'Search reindexing completed successfully.',
                'model' => $model ?? 'all',
                'output' => trim($output),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search reindexing failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

