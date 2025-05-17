<?php

namespace App\Providers;

use App\Policies\TransactionPolicy;
use App\Models\Transaction;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Transaction::class => TransactionPolicy::class,
    ];

    public function boot()
    {
        $this->registerPolicies();

        // Define permissions
        Gate::define('view transactions', function ($user) {
            return $user->hasRole('admin') || $user->hasPermission('view-transactions');
        });

        Gate::define('view any transactions', function ($user) {
            return $user->hasRole('admin');
        });

        Gate::define('create transactions', function ($user) {
            return $user->hasRole('admin') || $user->hasPermission('create-transactions');
        });

        Gate::define('update transactions', function ($user) {
            return $user->hasRole('admin') || $user->hasPermission('update-transactions');
        });

        Gate::define('delete transactions', function ($user) {
            return $user->hasRole('admin') || $user->hasPermission('delete-transactions');
        });
    }
}
