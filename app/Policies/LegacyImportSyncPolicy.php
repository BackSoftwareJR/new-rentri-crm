<?php

namespace App\Policies;

use App\Models\User;

class LegacyImportSyncPolicy
{
    public function sync(User $user): bool
    {
        return $user->hasRole(['admin', 'editor', 'segreteria']);
    }

    public function viewRuns(User $user): bool
    {
        return $user->hasRole(['admin', 'editor', 'segreteria']);
    }
}
