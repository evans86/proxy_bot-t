<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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

        $givenUser = (string) $request->getUser();
        $givenPass = (string) $request->getPassword();

        if (! hash_equals($expectedUser, $givenUser) || ! hash_equals($expectedPass, $givenPass)) {
            return response(__('Требуется авторизация.'), 401, [
                'WWW-Authenticate' => 'Basic realm="SMM"',
                'Cache-Control' => 'no-store, private',
            ]);
        }

        return $next($request);
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
