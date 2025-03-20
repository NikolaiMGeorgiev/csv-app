<?php

namespace App\Providers;

use App\Models\Products;
use App\Models\Uploads;
use App\Policies\ProductsPolicy;
use App\Policies\UploadsPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Products::class => ProductsPolicy::class,
        Uploads::class => UploadsPolicy::class
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
