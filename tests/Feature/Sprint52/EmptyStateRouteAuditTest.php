<?php

namespace Tests\Feature\Sprint52;

use App\Domain\Dashboard\DashboardKpiService;
use App\Http\Livewire\Operatore\Bonifica;
use App\Http\Livewire\Segreteria\Fir\FirIndex;
use App\Http\Livewire\Segreteria\Trasporti\TrasportiIndex;
use App\Http\Livewire\Segreteria\Vfu\VfuIndex;
use App\Models\User;
use App\Models\VfuRegistration;
use App\Enums\VfuStato;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TestCase;

class EmptyStateRouteAuditTest extends TestCase
{
    public function test_vfu_index_shows_empty_state_when_no_results(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(VfuIndex::class)
            ->set('search', 'ZZZ-NO-MATCH-SPRINT52')
            ->assertSee('seg-empty-state')
            ->assertSee('Nessun veicolo trovato');
    }

    public function test_fir_index_shows_empty_state_when_no_results(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(FirIndex::class)
            ->set('search', 'FIR-NO-MATCH-SPRINT52')
            ->assertSee('seg-empty-state')
            ->assertSee('Nessun FIR trovato');
    }

    public function test_trasporti_index_shows_empty_state_when_no_results(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(TrasportiIndex::class)
            ->set('search', 'TR-NO-MATCH-SPRINT52')
            ->assertSee('seg-empty-state')
            ->assertSee('Nessun trasporto trovato');
    }

    public function test_app_routes_require_auth_except_public_allowlist(): void
    {
        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();

            if ($name === null || $this->isPublicRoute($route)) {
                continue;
            }

            $middleware = collect($route->gatherMiddleware());

            $this->assertTrue(
                $middleware->contains('auth')
                    || $middleware->contains(\Illuminate\Auth\Middleware\Authenticate::class),
                "Route [{$name}] ({$route->uri()}) should require auth middleware.",
            );
        }
    }

    private function isPublicRoute(\Illuminate\Routing\Route $route): bool
    {
        $name = $route->getName();
        $uri = $route->uri();

        if ($uri === '/') {
            return true;
        }

        foreach (config('auth_audit.skip_route_name_prefixes', []) as $prefix) {
            if (str_starts_with((string) $name, $prefix)) {
                return true;
            }
        }

        foreach (config('auth_audit.skip_uri_prefixes', []) as $prefix) {
            if (str_starts_with($uri, $prefix)) {
                return true;
            }
        }

        if (in_array($name, config('auth_audit.public_route_names', []), true)) {
            return true;
        }

        $middleware = collect($route->gatherMiddleware());

        foreach (config('auth_audit.public_middleware', []) as $publicMiddleware) {
            if ($middleware->contains($publicMiddleware)) {
                return true;
            }
        }

        return false;
    }

    public function test_livewire_update_route_requires_auth(): void
    {
        $updateRoute = collect(Route::getRoutes())->first(
            fn ($route) => $route->getName() === 'default-livewire.update'
                || (str_contains($route->uri(), 'livewire') && in_array('POST', $route->methods(), true)),
        );

        $this->assertNotNull($updateRoute);

        $middleware = collect($updateRoute->gatherMiddleware());

        $this->assertTrue(
            $middleware->contains('auth')
                || $middleware->contains(\Illuminate\Auth\Middleware\Authenticate::class),
            'Livewire update route should require auth middleware.',
        );
    }

    public function test_dashboard_kpi_vfu_counts_use_single_grouped_query(): void
    {
        VfuRegistration::factory()->create(['stato' => VfuStato::Accettato]);
        VfuRegistration::factory()->create(['stato' => VfuStato::AttesaBonifica]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        app(DashboardKpiService::class)->aggregate();

        $groupedVfuQueries = collect(DB::getQueryLog())
            ->filter(fn (array $entry) => str_contains(strtolower($entry['query']), 'vfu_registrations')
                && str_contains(strtolower($entry['query']), 'group by'))
            ->count();

        $this->assertSame(1, $groupedVfuQueries);
    }

    public function test_bonifica_policy_allows_operatore_and_denies_segreteria(): void
    {
        $operatore = User::where('email', 'operatore@example.com')->firstOrFail();
        $segreteria = User::where('email', 'segreteria@example.com')->firstOrFail();
        $vfu = VfuRegistration::factory()->create(['stato' => VfuStato::Accettato]);

        $this->assertTrue(Gate::forUser($operatore)->allows('bonifica.viewAny'));
        $this->assertTrue(Gate::forUser($operatore)->allows('bonifica.perform', $vfu));
        $this->assertFalse(Gate::forUser($segreteria)->allows('bonifica.viewAny'));
        $this->assertFalse(Gate::forUser($segreteria)->allows('bonifica.perform', $vfu));
    }

    public function test_segreteria_cannot_access_operatore_bonifica_livewire(): void
    {
        $segreteria = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($segreteria)
            ->test(Bonifica::class)
            ->assertForbidden();
    }
}
