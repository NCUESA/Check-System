<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function redirect(Request $request)
    {
        $redirectResponse = Socialite::driver('keycloak')->redirect();

        // Inertia navigates via XHR; a cross-origin redirect (to Keycloak) hit
        // through XHR gets blocked by CORS. Tell Inertia to do a hard browser
        // redirect instead so it leaves the SPA navigation cycle entirely.
        if ($request->header('X-Inertia')) {
            return Inertia::location($redirectResponse->getTargetUrl());
        }

        return $redirectResponse;
    }

    public function callback()
    {
        $keycloakUser = Socialite::driver('keycloak')->user();

        // preferred_username is assumed to carry the school student id (stu_id).
        // Adjust this if your Keycloak realm maps stu_id to a different claim.
        $stuId = $keycloakUser->getNickname();

        $user = User::updateOrCreate(
            ['stu_id' => $stuId],
            [
                'name' => $keycloakUser->getName(),
                'email' => $keycloakUser->getEmail(),
            ]
        );

        Auth::login($user);

        return redirect()->route('home');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        // Auth::logout() 只移除 session 裡的 auth key，session id、CSRF token
        // 和其他資料都會原封不動留著（session fixation）。這兩行才是真的把
        // 整個 session 作廢並換一組新的 id 與 token。
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // 一定要帶 client_id。只傳 redirect uri 的話 provider 會走
        // 「Keycloak v18 之前」的分支，送出的是 redirect_uri 這個舊參數，
        // 新版 Keycloak 直接忽略，登出後就停在 Keycloak 自己的頁面回不來。
        // 網址用 route('home')：config('app.url') 是 http://localhost，少了埠號。
        $logoutUrl = Socialite::driver('keycloak')->getLogoutUrl(
            route('home'),
            config('services.keycloak.client_id')
        );

        // 登出是 Inertia 用 XHR 送出的，直接回 302 到跨網域的 Keycloak
        // 會被 CORS 擋掉，畫面上等於什麼都沒發生。要讓瀏覽器整頁跳轉。
        if ($request->header('X-Inertia')) {
            return Inertia::location($logoutUrl);
        }

        return redirect($logoutUrl);
    }
}
