<?php

namespace App\Http\Controllers;

use App\Domain\Infrastructure\ApplicationHealthService;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function __invoke(ApplicationHealthService $health): JsonResponse
    {
        $report = $health->diagnose();
        $status = $report['status'] === 'healthy' ? 200 : 503;

        return response()->json($report, $status);
    }
}
