<?php

namespace App\Providers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
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
        // if($this->app->environment('production')) {
        //     URL::forceScheme('https');
        // }
    
        if(!$this->app->request->is('api/*')){
            request()->headers->set('Accept-Language','ar-sa,ar:q=0.9');
        }
        $lang=request()->header('X-Language')??'ar';
        $lang=str_contains($lang,'en')?'en':'ar';
        App::setLocale($lang);

    }
}
