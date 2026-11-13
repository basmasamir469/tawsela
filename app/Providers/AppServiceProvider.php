<?php

namespace App\Providers;

use App\Services\Chat\AiServices\GeminiAiService;
use App\Services\Chat\Contracts\AiServiceInterface;
use App\Services\Order\Contracts\OrderCancellation;
use App\Services\Order\Decorators\OrderNotificationDecorator;
use App\Services\Order\OrderService;
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
        $this->app->bind(AiServiceInterface::class,GeminiAiService::class);
        $this->app->bind(OrderCancellation::class, function ($app) {
            return new OrderNotificationDecorator($app->make(OrderService::class));
        });
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
