<?php

namespace App\Http\Controllers\Operatore;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class OperatorePwaManifestController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $pwa = config('operatore.pwa', []);

        return response()->json([
            'name'             => (string) ($pwa['name'] ?? 'RENTRI Operatore'),
            'short_name'       => (string) ($pwa['short_name'] ?? 'Operatore'),
            'description'      => (string) ($pwa['description'] ?? ''),
            'start_url'        => (string) ($pwa['start_url'] ?? '/operatore'),
            'scope'            => (string) ($pwa['scope'] ?? '/operatore/'),
            'display'          => (string) ($pwa['display'] ?? 'standalone'),
            'theme_color'      => (string) ($pwa['theme_color'] ?? '#F2F2F7'),
            'background_color' => (string) ($pwa['background_color'] ?? '#FFFFFF'),
            'orientation'      => 'portrait-primary',
            'lang'             => 'it',
            'icons'            => [
                [
                    'src'   => '/favicon.ico',
                    'sizes' => '48x48',
                    'type'  => 'image/x-icon',
                ],
            ],
        ], 200, [
            'Content-Type' => 'application/manifest+json',
        ]);
    }
}
