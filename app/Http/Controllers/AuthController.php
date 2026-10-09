<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        if (! Auth::attempt($data)) {
            throw ValidationException::withMessages(['email' => 'Those details did not match. Please try again.']);
        }
        $request->session()->regenerate();

        return redirect()->intended('/learning');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => 'required|string|max:100', 'email' => 'required|email:filter|lowercase|max:255|unique:users', 'password' => ['required', 'confirmed', Password::min(12)]]);
        $user = User::create($data);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/learning');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
