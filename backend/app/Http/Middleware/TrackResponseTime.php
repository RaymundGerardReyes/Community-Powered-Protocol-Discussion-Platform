<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * TrackResponseTime
 * Attaches real server execution latency headers to the HTTP response:
 *  - X-Response-Time: Human-readable execution time in milliseconds.
 *  - Server-Timing: W3C standard server timing metric for browser DevTools.
 */
class TrackResponseTime
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = defined('LARAVEL_START') ? LARAVEL_START : microtime(true);

        /** @var Response $response */
        $response = $next($request);

        $durationMs = round((microtime(true) - $startTime) * 1000, 2);

        $driver = (string) config('scout.driver', 'null');
        $isTypesense = $driver === 'typesense' || str_starts_with($driver, 'ty');

        $dbConn = (string) config('database.default', 'sqlite');
        $dbTarget = $dbConn === 'sqlite'
            ? basename((string) config("database.connections.sqlite.database", 'database.sqlite'))
            : (string) config("database.connections.{$dbConn}.database", 'unknown');

        $response->headers->set('X-Response-Time', "{$durationMs}ms");
        $response->headers->set('Server-Timing', "app;dur={$durationMs};desc=\"Laravel Execution\"");
        $response->headers->set('X-Search-Driver', $driver);
        $response->headers->set('X-Data-Source', $isTypesense ? 'typesense' : 'database');
        $response->headers->set('X-Database-Connection', $isTypesense ? 'none (typesense-decoupled)' : $dbConn);
        $response->headers->set('X-Database-Target', $isTypesense ? 'typesense-cloud' : $dbTarget);

        if (php_sapi_name() === 'cli-server') {
            $response->headers->set('Connection', 'close');
        }

        return $response;
    }
}
