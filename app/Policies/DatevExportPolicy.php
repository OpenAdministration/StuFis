<?php

namespace App\Policies;

use App\Models\User;

class DatevExportPolicy
{
    /**
     * Whether the user may generate and download a DATEV export.
     *
     * Gated on the finance role for now, but kept as its own ability so the DATEV
     * permission can diverge from the generic finance check later without touching
     * call sites. Delegating through the gate preserves UserPolicy's admin bypass.
     */
    public function download(User $user): bool
    {
        return $user->can('finance', User::class);
    }
}
