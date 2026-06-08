<?php

namespace App\Http\Livewire\Operatore;

use App\Domain\Operatore\OperatoreProfiloService;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;

#[Title('Profilo operatore')]
class Profilo extends OperatorePage
{
    use AuthorizesRequests;

    public string $name = '';

    public string $email = '';

    public function mount(): void
    {
        /** @var User $user */
        $user = auth()->user();
        $this->authorize('updateProfile', $user);

        $this->name = $user->name;
        $this->email = $user->email;
    }

    public function salva(OperatoreProfiloService $profilo): void
    {
        /** @var User $user */
        $user = auth()->user();
        $this->authorize('updateProfile', $user);

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        try {
            $profilo->updateProfilo($user, $this->name);
        } catch (\InvalidArgumentException $e) {
            $this->addError('name', $e->getMessage());

            return;
        }

        session()->flash('success', 'Profilo aggiornato.');
    }

    public function render(): View
    {
        return $this->operatoreView(
            'livewire.operatore.profilo',
            [],
            'profilo',
            'Profilo',
        );
    }
}
