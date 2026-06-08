<?php

namespace App\Http\Controllers\Operatore;

use App\Http\Controllers\Controller;
use App\Models\SmontaggioRicambio;
use App\Policies\SmontaggioPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SmontaggioRicambioPhotoController extends Controller
{
    public function show(SmontaggioRicambio $ricambio, Request $request, SmontaggioPolicy $policy): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->hasAnyRole(['operatore', 'segreteria', 'admin', 'editor']), 403);

        $ricambio->loadMissing('session');
        abort_unless($policy->viewPhoto($user, $ricambio), 403);

        if (blank($ricambio->foto_path) || ! Storage::disk('local')->exists($ricambio->foto_path)) {
            abort(404);
        }

        return Storage::disk('local')->response($ricambio->foto_path);
    }
}
