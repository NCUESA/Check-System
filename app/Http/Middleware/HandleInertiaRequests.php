<?php

namespace App\Http\Middleware;

use App\Models\AuthIp;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Session;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $clientIp= '127.0.0.1';
        if (isset($_SERVER['HTTP_CF_CONNECTING_IPV6'])) {
            $clientIp = $_SERVER['HTTP_CF_CONNECTING_IPV6'];
        } 
        elseif (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $clientIp = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } 
        elseif (isset($_SERVER["HTTP_X_REAL_IP"])) {
            $clientIp = $_SERVER["HTTP_X_REAL_IP"];
        } 
        elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $clientIp = $_SERVER('HTTP_X_FORWARDED_FOR');
            $clientIps = explode(',', $clientIp);
            $clientIp = $clientIps[0];
        } 
        elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $clientIp = $_SERVER['HTTP_CLIENT_IP'];
        } 
        else {
            if (isset($_SERVER['REMOTE_ADDR']))
                $clientIp = $_SERVER['REMOTE_ADDR'];
        }

        $isAllowed = AuthIp::where('ip_address', $clientIp)->exists();
        return array_merge(parent::share($request), [
            'ip' => $clientIp, 
            'is_allowed' => $isAllowed,
            'message' => Session::get('message')
        ]);
    }
}
