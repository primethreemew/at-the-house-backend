<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class LogRequestResponse
{
    public function handle(Request $request, Closure $next)
    {
        $requestId = (string) Str::uuid(); // Generate a unique request ID
        $user = $request->user();
        $userId = $user ? $user->id : null;

        Log::info('Incoming Request', [
            'request_id' => $requestId,
            'user_id' => $userId,
            'method' => $request->getMethod(),
            'url' => $request->fullUrl(),
            'headers' => array_filter(
                $request->headers->all(),
                fn($key) => in_array($key, ['content-type', 'accept', 'user-agent']),
                ARRAY_FILTER_USE_KEY
            ),
            'user_agent' => $request->header('User-Agent'),
            'ip_address' => $request->ip(),
            'body' => $request->except(['password', 'token']),
        ]);

        try {
            $response = $next($request);

            Log::info('Outgoing Response', [
                'request_id' => $requestId,
                'user_id' => $userId,
                'status' => $response->status(),
                'content_preview' => substr($response->getContent(), 0, 500),
            ]);

            return $response;
        } catch (Throwable $e) {
            Log::error('Exception Thrown', [
                'request_id' => $requestId,
                'user_id' => $userId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
