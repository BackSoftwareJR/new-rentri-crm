<?php

namespace App\Http\Middleware;

use App\Support\Sito\SitoContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ScopeToActiveSito
{
    public function handle(Request $request, Closure $next): Response
    {
        SitoContext::activeSitoId();

        return $next($request);
    }
}
