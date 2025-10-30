<?php

namespace App\Providers;

use CarbonInmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\ServiceProvider;
use App\Models\CONFIGURATION\Modality;
use App\Models\Reception\ficheNavetteItem;
use App\Observers\CONFIGURATION\ModalityObserver;
use App\Observers\Reception\FicheNavetteItemObserver;

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
            ficheNavetteItem::observe(FicheNavetteItemObserver::class);

        
        // Model::preventLazyLoading(!app()->isProduction());
        // Model::unguard();
        //  Model::shouldBeStrict(!app()->isProduction());
        //  Date::use(CarbonInmutable::class);
        
    }
}
