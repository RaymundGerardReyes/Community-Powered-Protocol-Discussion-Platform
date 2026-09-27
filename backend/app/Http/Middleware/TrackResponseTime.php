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

        $response->headers->set('X-Response-Time', "{$durationMs}ms");
        $response->headers->set('Server-Timing', "app;dur={$durationMs};desc=\"Laravel Execution\"");

        return $response;
    }
}
