<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Audit\ActivityLogService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $audit,
    ) {}

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

        if (! $user->active) {
            Auth::logout();

            $message = $user->deletion_requested_at
                ? 'Account disattivato: richiesta di cancellazione in corso.'
                : 'Account disattivato. Contatta l\'amministratore.';

            return back()
                ->withErrors(['email' => $message])
                ->onlyInput('email');
        }

        if ($user->hasTwoFactorEnabled()) {
            Auth::logout();

            $request->session()->put('login.two_factor.id', $user->id);
            $request->session()->put('login.two_factor.remember', $request->boolean('remember'));

            return redirect()->route('two-factor.challenge');
        }

        $request->session()->regenerate();

        $this->audit->record(
            'auth',
            'Accesso effettuato',
            $user,
            ['method' => 'password', 'remember' => $request->boolean('remember')],
            $user->id,
        );

        return redirect()->intended($this->homeForUser($user));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user) {
            $this->audit->record(
                'auth',
                'Disconnessione effettuata',
                $user,
                [],
                $user->id,
            );
        }

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
