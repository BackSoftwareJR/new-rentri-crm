<?php

namespace App\Http\Middleware;

use App\Domain\Auth\TwoFactorEnforcementService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorEnabled
{
    public function __construct(
        private readonly TwoFactorEnforcementService $enforcement,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $this->enforcement->requiresTwoFactorSetup($user)) {
            return $next($request);
        }

        if ($request->routeIs('segreteria.impostazioni.sicurezza')) {
            return $next($request);
        }

        if ($this->enforcement->isWithinGracePeriod()) {
            return $next($request);
        }

        return redirect()
            ->route('segreteria.impostazioni.sicurezza')
            ->with('warning', $this->enforcement->redirectMessage());
    }
}
