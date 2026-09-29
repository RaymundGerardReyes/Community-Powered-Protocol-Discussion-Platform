<?php

namespace App\Providers;

use App\Events\VoteCast;
use App\Listeners\SyncSearchIndex;
use App\Models\Review;
use App\Observers\ReviewObserver;
use App\Repositories\Contracts\ProtocolRepositoryInterface;
use App\Repositories\Contracts\ThreadRepositoryInterface;
use App\Repositories\Eloquent\ProtocolRepository;
use App\Repositories\Eloquent\ThreadRepository;
use Illuminate\Support\Facades\Event;
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

        $this->app->bind(
            ThreadRepositoryInterface::class,
            ThreadRepository::class
        );

        $this->app->singleton(\Typesense\Client::class, function () {
            $config = config('scout.typesense.client-settings', []);
            return new \Typesense\Client($config);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Review::observe(ReviewObserver::class);

        Event::listen(
            VoteCast::class,
            SyncSearchIndex::class
        );
    }
}
