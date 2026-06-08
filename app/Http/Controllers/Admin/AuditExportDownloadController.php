<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Audit\AuditExportDownloadService;
use App\Http\Controllers\Controller;
use App\Models\AuditExportRun;
use Illuminate\Http\Request;

class AuditExportDownloadController extends Controller
{
    public function __invoke(AuditExportRun $run, Request $request, AuditExportDownloadService $downloads)
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Link download non valido o scaduto.');
        }

        $user = $request->user();
        abort_unless($user !== null && $user->hasRole('admin'), 403);

        return $downloads->streamDownload($run);
    }
}
