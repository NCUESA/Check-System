<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * 只允許管理員通過。
     *
     * 在這之前 is_admin 只用來決定導覽列要不要顯示連結，
     * 但路由本身沒有把關，直接打網址仍然進得去。
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()?->is_admin) {
            abort(403, '此頁面僅限管理員使用');
        }

        return $next($request);
    }
}
