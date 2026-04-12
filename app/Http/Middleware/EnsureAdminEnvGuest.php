<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Для /admin/login: если шаг .env уже пройден — уводим на панель.
 */
class EnsureAdminEnvGuest
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->session()->get(config('admin.session_key'), false)) {
            return redirect()->route('home');
        }

        return $next($request);
    }
}
