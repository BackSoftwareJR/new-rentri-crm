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
            'description'      => 'Gestione operativa VFU — bonifica, smontaggio, ricambi.',
            'start_url'        => '/operatore',
            'scope'            => '/operatore/',
            'display'          => 'standalone',
            'theme_color'      => '#007AFF',
            'background_color' => '#F2F2F7',
            'orientation'      => 'portrait-primary',
            'lang'             => 'it',
            'categories'       => ['business', 'productivity'],
            'icons'            => [
                ['src' => '/favicon.ico', 'sizes' => '48x48',   'type' => 'image/x-icon'],
                ['src' => '/favicon.ico', 'sizes' => '72x72',   'type' => 'image/x-icon', 'purpose' => 'any'],
                ['src' => '/favicon.ico', 'sizes' => '96x96',   'type' => 'image/x-icon', 'purpose' => 'any'],
                ['src' => '/favicon.ico', 'sizes' => '128x128', 'type' => 'image/x-icon', 'purpose' => 'any'],
                ['src' => '/favicon.ico', 'sizes' => '192x192', 'type' => 'image/x-icon', 'purpose' => 'any maskable'],
                ['src' => '/favicon.ico', 'sizes' => '512x512', 'type' => 'image/x-icon', 'purpose' => 'any maskable'],
            ],
            'shortcuts' => [
                [
                    'name'        => 'Bonifica',
                    'short_name'  => 'Bonifica',
                    'description' => 'Avvia bonifica VFU',
                    'url'         => '/operatore/bonifica',
                    'icons'       => [['src' => '/favicon.ico', 'sizes' => '96x96']],
                ],
                [
                    'name'        => 'Smontaggio',
                    'short_name'  => 'Smontaggio',
                    'description' => 'Sessione smontaggio ricambi',
                    'url'         => '/operatore/smontaggio',
                    'icons'       => [['src' => '/favicon.ico', 'sizes' => '96x96']],
                ],
                [
                    'name'        => 'Ricambi',
                    'short_name'  => 'Ricambi',
                    'description' => 'Catalogo ricambi smontati',
                    'url'         => '/operatore/ricambi',
                    'icons'       => [['src' => '/favicon.ico', 'sizes' => '96x96']],
                ],
            ],
            'prefer_related_applications' => false,
        ], 200, [
            'Content-Type' => 'application/manifest+json',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
