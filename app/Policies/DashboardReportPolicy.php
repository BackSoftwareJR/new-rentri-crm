<?php

namespace App\Policies;

use App\Models\User;
use App\Support\DashboardReport;

class DashboardReportPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole(['admin', 'editor', 'segreteria'])) {
            return null;
        }

        return false;
    }

    public function view(User $user, DashboardReport $report): bool
    {
        return true;
    }

    public function export(User $user, DashboardReport $report): bool
    {
        return true;
    }

    public function refreshKpi(User $user, DashboardReport $report): bool
    {
        return true;
    }
}
