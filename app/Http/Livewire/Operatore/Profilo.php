<?php

namespace App\Http\Livewire\Operatore;

use App\Domain\Operatore\OperatoreProfiloService;
use App\Models\User;
use App\Services\Push\WebPushService;
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

    public function subscribePush(WebPushService $push, string $subscriptionJson, ?string $deviceName = null): void
    {
        /** @var User $user */
        $user = auth()->user();
        $this->authorize('updateProfile', $user);

        $data = json_decode($subscriptionJson, true);

        if (! is_array($data) || empty($data['endpoint']) || empty($data['keys'])) {
            $this->addError('push', 'Dati sottoscrizione push non validi.');

            return;
        }

        if ($deviceName !== null && $deviceName !== '') {
            $data['device_name'] = $deviceName;
        }

        $push->subscribe($user, $data);
        session()->flash('success', 'Notifiche push attivate su questo dispositivo.');
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

    public function render(WebPushService $push): View
    {
        return $this->operatoreView(
            'livewire.operatore.profilo',
            [
                'pushEnabled' => $push->publicKey() !== null,
                'vapidPublicKey' => $push->publicKey(),
            ],
            'profilo',
            'Profilo',
        );
    }
}
