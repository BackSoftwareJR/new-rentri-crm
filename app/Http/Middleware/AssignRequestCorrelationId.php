<?php

namespace App\Http\Middleware;

use App\Support\Logging\RequestContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AssignRequestCorrelationId
{
    public function handle(Request $request, Closure $next): Response
    {
        $traceId = $request->headers->get('X-Request-Id')
            ?? $request->headers->get('X-Correlation-Id')
            ?? (string) Str::uuid();

        RequestContext::setTraceId($traceId);
        Log::shareContext(['trace_id' => $traceId]);

        /** @var Response $response */
        $response = $next($request);

        if (! $response->headers->has('X-Request-Id')) {
            $response->headers->set('X-Request-Id', $traceId);
        }

        return $response;
    }
}
