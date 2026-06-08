<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Auth\TwoFactorService;
use App\Domain\Audit\ActivityLogService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $audit,
    ) {}

    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('login.two_factor.id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function store(Request $request, TwoFactorService $twoFactor): RedirectResponse
    {
        $request->validate([
            'code'          => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        $userId = $request->session()->get('login.two_factor.id');

        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);

        if (! $user || ! $user->hasTwoFactorEnabled()) {
            $request->session()->forget(['login.two_factor.id', 'login.two_factor.remember']);

            return redirect()->route('login')
                ->withErrors(['code' => __('Sessione scaduta. Effettua di nuovo l\'accesso.')]);
        }

        $authenticated = false;

        if ($request->filled('recovery_code')) {
            $authenticated = $twoFactor->useRecoveryCode($user, (string) $request->input('recovery_code'));

            if (! $authenticated) {
                return back()->withErrors(['recovery_code' => __('Codice di recupero non valido o già utilizzato.')]);
            }
        } elseif ($request->filled('code')) {
            $authenticated = $twoFactor->verifyUser($user, (string) $request->input('code'));

            if (! $authenticated) {
                return back()->withErrors(['code' => __('Codice non valido.')]);
            }
        } else {
            return back()->withErrors(['code' => __('Inserisci un codice TOTP o un codice di recupero.')]);
        }

        $remember = (bool) $request->session()->get('login.two_factor.remember', false);
        $request->session()->forget(['login.two_factor.id', 'login.two_factor.remember']);

        Auth::login($user, $remember);
        $request->session()->regenerate();

        $this->audit->record(
            'auth',
            'Accesso effettuato',
            $user,
            [
                'method'   => $request->filled('recovery_code') ? 'recovery_code' : 'two_factor',
                'remember' => $remember,
            ],
            $user->id,
        );

        return redirect()->intended($this->homeForUser($user));
    }

    private function homeForUser(User $user): string
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
