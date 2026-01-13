<?php

namespace App\Providers;

use App\Models\DynamicEntity;
use App\Models\MediaFile;
use App\Observers\DynamicEntityObserver;
use App\Observers\MediaFileObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
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
        // Register model observers
        DynamicEntity::observe(DynamicEntityObserver::class);
        MediaFile::observe(MediaFileObserver::class);
    }
}
