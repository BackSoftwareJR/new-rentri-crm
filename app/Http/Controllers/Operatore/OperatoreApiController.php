<?php

namespace App\Http\Controllers\Operatore;

use App\Domain\Operatore\OperatoreMobileApiService;
use App\Http\Controllers\Controller;
use App\Models\EcommerceProdotto;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OperatoreApiController extends Controller
{
    use AuthorizesRequests;
    public function bonifica(Request $request, OperatoreMobileApiService $api): JsonResponse
    {
        $this->authorize('bonifica.viewAny');

        $validated = $request->validate([
            'q'      => ['nullable', 'string', 'max:120'],
            'filtro' => ['nullable', 'string', 'in:tutti,scaduti,in_tempo,dopo_pericolosi'],
        ]);

        return response()->json($api->bonifica([
            'search' => $validated['q'] ?? '',
            'filtro' => $validated['filtro'] ?? 'tutti',
        ]));
    }

    public function ricambi(Request $request, OperatoreMobileApiService $api): JsonResponse
    {
        $this->authorize('viewAny', EcommerceProdotto::class);

        $validated = $request->validate([
            'q'         => ['nullable', 'string', 'max:120'],
            'categoria' => ['nullable', 'string', 'max:80'],
            'per_page'  => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return response()->json($api->ricambi(array_filter([
            'q'         => $validated['q'] ?? null,
            'categoria' => $validated['categoria'] ?? null,
            'per_page'  => $validated['per_page'] ?? null,
        ], fn ($v) => $v !== null && $v !== '')));
    }

    public function vetrina(Request $request, OperatoreMobileApiService $api): JsonResponse
    {
        $this->authorize('viewAny', EcommerceProdotto::class);

        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:24'],
        ]);

        return response()->json($api->vetrina((int) ($validated['limit'] ?? 12)));
    }
}
