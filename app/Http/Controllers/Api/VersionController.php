<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class VersionController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'version' => config('app_version.version'),
            'build'   => config('app_version.build'),
            'env'     => config('app_version.env'),
        ]);
    }
}
