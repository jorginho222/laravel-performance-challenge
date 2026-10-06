<?php

namespace Src\Identity\Infrastructure\Http\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Src\Identity\Infrastructure\Http\Requests\LoginRequest;
use Src\Identity\Infrastructure\Http\Requests\RegisterRequest;
use Src\Identity\Infrastructure\Persistence\User;

/**
 * Session authentication for the web frontend (the API authenticates with Sanctum tokens).
 */
class AuthWebController
{
    public function showLogin(): Response
    {
        return Inertia::render('Identity/Login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        if (! Auth::attempt($request->validated(), $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'The provided credentials are incorrect.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended('/products');
    }

    public function showRegister(): Response
    {
        return Inertia::render('Identity/Register');
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        Auth::login(User::create($request->validated()));
        $request->session()->regenerate();

        return redirect('/products');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
