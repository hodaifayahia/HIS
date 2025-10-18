<?php

namespace App\Providers;

use CarbonInmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\ServiceProvider;
use App\Models\CONFIGURATION\Modality;
use App\Observers\CONFIGURATION\ModalityObserver;

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
            Modality::observe(ModalityObserver::class);

        
        // Model::preventLazyLoading(!app()->isProduction());
        // Model::unguard();
        //  Model::shouldBeStrict(!app()->isProduction());
        //  Date::use(CarbonInmutable::class);
        
    }
}
