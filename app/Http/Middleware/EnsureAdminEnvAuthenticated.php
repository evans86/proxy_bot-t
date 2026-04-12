<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminEnvAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->guest(route('login'));
        }

        $key = config('admin.session_key');

        if (! $request->session()->get($key, false)) {
            return redirect()->guest(route('admin.login'));
        }

        View::share([
            'admin_panel' => true,
            'admin_username' => config('admin.username') ?: 'admin',
        ]);

        return $next($request);
    }
}
