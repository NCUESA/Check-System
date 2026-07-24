<?php

namespace App\Providers;

use App\Models\AuthIp;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Keycloak\KeycloakExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;

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
        //
        Event::listen(SocialiteWasCalled::class, [KeycloakExtendSocialite::class, 'handle']);

        View::composer('*', function ($view) {
          $clientIp= request()->ip();
            $isAllowed = AuthIp::where('ip_address', $clientIp)
                ->first();
    
            $hasAccess = $isAllowed;
    
            $view->with(compact('clientIp', 'hasAccess'));
        });
    }
}
