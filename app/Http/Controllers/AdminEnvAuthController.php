<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminEnvAuthController extends Controller
{
    public function showLoginForm(): View
    {
        return view('auth.admin-login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $expectedUser = trim((string) (config('admin.username') ?? ''));
        $givenUser = trim((string) $request->input('username'));
        if ($expectedUser === '' || ! hash_equals($expectedUser, $givenUser)) {
            return $this->failedLogin($request);
        }

        if (! $this->passwordValid((string) $request->input('password'))) {
            return $this->failedLogin($request);
        }

        $request->session()->put(config('admin.session_key'), true);
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget(config('admin.session_key'));
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    private function passwordValid(string $password): bool
    {
        $bcrypt = config('admin.password_bcrypt');
        if (is_string($bcrypt) && $bcrypt !== '') {
            return password_verify($password, $bcrypt);
        }

        $plain = config('admin.password_plain');
        if (! is_string($plain) || $plain === '') {
            return false;
        }

        // Частая ошибка: bcrypt-хеш положили в ADMIN_PASSWORD — раньше шло сравнение как с открытым текстом.
        if ($this->looksLikeBcryptHash($plain)) {
            return password_verify($password, $plain);
        }

        return hash_equals($plain, $password);
    }

    private function looksLikeBcryptHash(string $value): bool
    {
        return preg_match('/^\$2[aby]\$\d{2}\$[.\/A-Za-z0-9]{53}$/', $value) === 1;
    }

    private function failedLogin(Request $request): RedirectResponse
    {
        return back()
            ->withErrors(['username' => __('Неверный логин или пароль.')])
            ->onlyInput('username');
    }
}
