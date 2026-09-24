<?php

namespace App\Providers;

use App\Repositories\Contracts\ProtocolRepositoryInterface;
use App\Repositories\Eloquent\ProtocolRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            ProtocolRepositoryInterface::class,
            ProtocolRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
