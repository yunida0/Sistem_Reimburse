<?php

namespace App\Providers;

use App\Models\Reimbursement;
use App\Policies\ReimbursementPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Register Policy
        Gate::policy(Reimbursement::class, ReimbursementPolicy::class);
    }
}
