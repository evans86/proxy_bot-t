<?php

namespace App\Http\Middleware;

use App\Services\Admin\AdminBasicAuthTelegramNotifier;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class EnsureHttpBasicFromEnv
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedUser = $this->normalizedCredential(config('http_basic.username'));
        $expectedPass = $this->normalizedCredential(config('http_basic.password'));

        if ($expectedUser === '' || $expectedPass === '') {
            if (app()->environment(['local', 'testing'])) {
                return $next($request);
            }

            abort(503, 'Задайте HTTP_BASIC_USERNAME и HTTP_BASIC_PASSWORD в .env.');
        }

        $givenUser = $request->getUser();
        $givenPassword = $request->getPassword();

        if (
            $givenUser === null
            || $givenPassword === null
            || ! hash_equals($expectedUser, (string) $givenUser)
            || ! hash_equals($expectedPass, (string) $givenPassword)
        ) {
            $reason = ($givenUser === null || $givenPassword === null) ? 'missing' : 'invalid';
            $attempted = $givenUser !== null ? (string) $givenUser : null;

            if ($reason === 'invalid') {
                App::terminating(function () use ($request, $attempted, $reason): void {
                    app(AdminBasicAuthTelegramNotifier::class)->notifyFailure($request, $attempted, $reason);
                });
            }

            return response(__('Требуется авторизация.'), 401, [
                'WWW-Authenticate' => 'Basic realm="SMM"',
                'Cache-Control' => 'no-store, private',
            ]);
        }

        $basicUsername = (string) $givenUser;
        $response = $next($request);

        if ($request->hasSession() && ! $request->session()->get('admin_basic_telegram_success_notified')) {
            $request->session()->put('admin_basic_telegram_success_notified', true);
            App::terminating(function () use ($request, $basicUsername): void {
                app(AdminBasicAuthTelegramNotifier::class)->notifySuccess($request, $basicUsername);
            });
        }

        return $response;
    }

    /**
     * @param mixed $value
     */
    private function normalizedCredential($value): string
    {
        if (! is_string($value)) {
            return '';
        }

        return trim($value);
    }
}
