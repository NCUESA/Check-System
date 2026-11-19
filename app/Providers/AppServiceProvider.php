<?php

namespace App\Providers;

use App\Models\AuthIp;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
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

        // 正式環境一律用 APP_URL 產生絕對網址。
        //
        // 容器內的 nginx 聽 80 埠，所以 Laravel 會以為公開網址也是 80，
        // 產生的網址會少掉埠號；反向代理是 https 時 scheme 也會錯成 http。
        // 受影響的是 Keycloak 的登入導向、登出跳轉、寄信裡的連結。
        // 本機開發不套用，才不會綁死在某個網址上。
        if (! $this->app->environment('local')) {
            $appUrl = config('app.url');
            if ($appUrl) {
                URL::forceRootUrl($appUrl);
                if (str_starts_with($appUrl, 'https://')) {
                    URL::forceScheme('https');
                }
            }
        }

        View::composer('*', function ($view) {
          $clientIp= request()->ip();
            $isAllowed = AuthIp::where('ip_address', $clientIp)
                ->first();
    
            $hasAccess = $isAllowed;
    
            $view->with(compact('clientIp', 'hasAccess'));
        });
    }
}
