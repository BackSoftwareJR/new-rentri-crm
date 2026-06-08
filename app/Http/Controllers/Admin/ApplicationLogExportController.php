<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Logging\ApplicationLogQueryService;
use App\Http\Controllers\Controller;
use App\Models\ApplicationLog;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationLogExportController extends Controller
{
    use AuthorizesRequests;

    public function __invoke(Request $request, ApplicationLogQueryService $logs): StreamedResponse
    {
        $this->authorize('export', ApplicationLog::class);

        $filters = array_filter([
            'module'   => $request->string('module')->toString() ?: null,
            'level'    => $request->string('level')->toString() ?: null,
            'trace_id' => $request->string('trace_id')->toString() ?: null,
            'data_da'  => $request->string('data_da')->toString() ?: $logs->defaultExportFromDate()->format('Y-m-d'),
            'data_a'   => $request->string('data_a')->toString() ?: now()->format('Y-m-d'),
            'demo'     => $request->has('demo') ? $request->boolean('demo') : null,
        ], fn ($v) => $v !== null && $v !== '');

        $filename = 'application-logs-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($logs, $filters): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fputcsv($handle, $logs->csvHeader());

            $logs->exportQuery($filters)->chunkById(500, function ($rows) use ($handle, $logs): void {
                foreach ($rows as $row) {
                    fputcsv($handle, $logs->csvRowFor($row));
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
