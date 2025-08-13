<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View; // Add this line

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind Lcobucci JWT Validator
        $this->app->bind(Validator::class, function () {
            return new ConstraintValidator();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // In boot() method
   

    }
}
