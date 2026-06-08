<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Auth\TwoFactorService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
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
            'code' => ['required', 'string', 'size:6'],
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

        if (! $twoFactor->verifyUser($user, $request->input('code'))) {
            return back()->withErrors(['code' => __('Codice non valido.')]);
        }

        $remember = (bool) $request->session()->get('login.two_factor.remember', false);
        $request->session()->forget(['login.two_factor.id', 'login.two_factor.remember']);

        Auth::login($user, $remember);
        $request->session()->regenerate();

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
