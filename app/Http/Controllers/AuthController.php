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

    public function logout()
    {
        Auth::logout();

        return redirect(Socialite::driver('keycloak')->getLogoutUrl(config('app.url')));
    }
}
