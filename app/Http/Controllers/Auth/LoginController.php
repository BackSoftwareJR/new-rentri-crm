<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->intended($this->homeForUser(Auth::user()));
        }

        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => __('Credenziali non valide.')])
                ->onlyInput('email');
        }

        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            Auth::logout();

            $request->session()->put('login.two_factor.id', $user->id);
            $request->session()->put('login.two_factor.remember', $request->boolean('remember'));

            return redirect()->route('two-factor.challenge');
        }

        $request->session()->regenerate();

        return redirect()->intended($this->homeForUser($user));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function homeForUser($user): string
    {
        if ($user->hasRole('admin') || $user->hasRole('editor')) {
            return route('admin.audit');
        }

        if ($user->hasRole('segreteria')) {
            return route('segreteria.dashboard');
        }

        if ($user->hasRole('operatore')) {
            return route('operatore.dashboard');
        }

        return route('login');
    }
}
