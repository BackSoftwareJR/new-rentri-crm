<?php

namespace App\Http\Livewire\Admin;

use App\Mail\UserWelcomeInvitation;
use App\Models\User;
use App\Rules\StrongPassword;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

#[Title('Gestione utenti')]
class UsersIndex extends AdminPage
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $role = '';

    // Modal state
    public bool $showModal      = false;
    public bool $isEditing      = false;
    public ?int $editingUserId  = null;

    // Form fields
    public string $formName          = '';
    public string $formEmail         = '';
    public string $formRole          = '';
    public string $formPassword      = '';
    public bool   $formSendWelcome   = true;

    // Form validation rules
    protected function rules(): array
    {
        $emailRules = ['required', 'email'];
        $emailRules[] = $this->isEditing
            ? 'unique:users,email,'.$this->editingUserId
            : 'unique:users,email';

        return [
            'formName'        => ['required', 'string', 'max:255'],
            'formEmail'       => $emailRules,
            'formRole'        => ['required', 'in:admin,segreteria,operatore,editor'],
            'formPassword'    => $this->isEditing ? [] : ['required', new StrongPassword],
            'formSendWelcome' => ['boolean'],
        ];
    }

    protected function messages(): array
    {
        return [
            'formName.required'  => 'Il nome è obbligatorio.',
            'formName.max'       => 'Il nome non può superare 255 caratteri.',
            'formEmail.required' => 'L\'email è obbligatoria.',
            'formEmail.email'    => 'Inserire un\'email valida.',
            'formEmail.unique'   => 'Questa email è già registrata.',
            'formRole.required'  => 'Il ruolo è obbligatorio.',
            'formRole.in'        => 'Ruolo non valido.',
            'formPassword.required' => 'La password è obbligatoria per i nuovi utenti.',
        ];
    }

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedRole(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->authorize('create', User::class);

        $this->resetForm();
        $this->isEditing       = false;
        $this->editingUserId   = null;
        $this->formPassword    = Str::password(12);
        $this->formSendWelcome = true;
        $this->showModal       = true;
    }

    public function openEditModal(int $userId): void
    {
        $user = User::findOrFail($userId);
        $this->authorize('update', $user);

        $this->resetForm();
        $this->isEditing      = true;
        $this->editingUserId  = $userId;
        $this->formName       = $user->name;
        $this->formEmail      = $user->email;
        $this->formRole       = $user->getRoleNames()->first() ?? '';
        $this->formPassword   = '';
        $this->showModal      = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
        $this->resetValidation();
    }

    public function save(): void
    {
        if ($this->isEditing) {
            $this->updateUser();
        } else {
            $this->createUser();
        }
    }

    private function createUser(): void
    {
        $this->authorize('create', User::class);
        $this->validate();

        /** @var User $actor */
        $actor = auth()->user();

        $user = User::create([
            'name'     => $this->formName,
            'email'    => $this->formEmail,
            'password' => Hash::make($this->formPassword),
            'active'   => true,
        ]);

        $user->syncRoles([$this->formRole]);

        Log::channel('security')->info('admin.user.created', [
            'actor_id'    => $actor->id,
            'actor_email' => $actor->email,
            'user_id'     => $user->id,
            'user_email'  => $user->email,
            'role'        => $this->formRole,
        ]);

        if ($this->formSendWelcome) {
            Mail::to($user)->queue(new UserWelcomeInvitation($user, $this->formPassword));

            Log::channel('security')->info('admin.user.welcome_email_queued', [
                'actor_id'    => $actor->id,
                'actor_email' => $actor->email,
                'user_id'     => $user->id,
                'user_email'  => $user->email,
            ]);
        }

        $this->closeModal();
        session()->flash('success', 'Utente creato con successo.');
    }

    private function updateUser(): void
    {
        $user = User::findOrFail($this->editingUserId);
        $this->authorize('update', $user);
        $this->validate();

        /** @var User $actor */
        $actor = auth()->user();

        $user->name  = $this->formName;
        $user->email = $this->formEmail;
        $user->save();

        // Admin cannot demote themselves; allow role change only for other users
        if ($actor->id !== $user->id) {
            $user->syncRoles([$this->formRole]);
        }

        Log::channel('security')->info('admin.user.updated', [
            'actor_id'    => $actor->id,
            'actor_email' => $actor->email,
            'user_id'     => $user->id,
            'user_email'  => $user->email,
        ]);

        $this->closeModal();
        session()->flash('success', 'Utente aggiornato.');
    }

    public function toggleActive(int $userId): void
    {
        $user = User::findOrFail($userId);
        $this->authorize('toggleActive', $user);

        /** @var User $actor */
        $actor = auth()->user();

        $user->active = ! $user->active;
        $user->save();

        $stato = $user->active ? 'attivato' : 'disattivato';

        Log::channel('security')->info('admin.user.toggle_active', [
            'actor_id'    => $actor->id,
            'actor_email' => $actor->email,
            'user_id'     => $user->id,
            'user_email'  => $user->email,
            'active'      => $user->active,
        ]);

        session()->flash('success', "Utente {$stato}.");
    }

    public function sendPasswordReset(int $userId): void
    {
        $user = User::findOrFail($userId);
        $this->authorize('resetPassword', $user);

        /** @var User $actor */
        $actor = auth()->user();

        $status = Password::sendResetLink(['email' => $user->email]);

        Log::channel('security')->info('admin.user.password_reset_sent', [
            'actor_id'    => $actor->id,
            'actor_email' => $actor->email,
            'user_id'     => $user->id,
            'user_email'  => $user->email,
            'status'      => $status,
        ]);

        if ($status === Password::RESET_LINK_SENT) {
            session()->flash('success', 'Email di reset password inviata.');
        } else {
            session()->flash('error', 'Impossibile inviare l\'email di reset.');
        }
    }

    public function forceDisable2fa(int $userId): void
    {
        $user = User::findOrFail($userId);
        $this->authorize('forceDisable2fa', $user);

        /** @var User $actor */
        $actor = auth()->user();

        $user->two_factor_secret       = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

        Log::channel('security')->warning('admin.user.2fa_force_disabled', [
            'actor_id'    => $actor->id,
            'actor_email' => $actor->email,
            'user_id'     => $user->id,
            'user_email'  => $user->email,
        ]);

        session()->flash('success', '2FA disabilitato per l\'utente.');
    }

    private function resetForm(): void
    {
        $this->formName        = '';
        $this->formEmail       = '';
        $this->formRole        = '';
        $this->formPassword    = '';
        $this->formSendWelcome = true;
    }

    public function render(): View
    {
        $query = User::query()
            ->with('roles')
            ->when($this->search !== '', function ($q) {
                $q->where(function ($inner) {
                    $inner->where('name', 'like', '%' . $this->search . '%')
                          ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->role !== '', function ($q) {
                $q->whereHas('roles', fn ($r) => $r->where('name', $this->role));
            })
            ->orderByDesc('created_at');

        $users = $query->paginate(20);

        return $this->adminView(
            'livewire.admin.users-index',
            [
                'users'      => $users,
                'rolesAvail' => ['admin', 'segreteria', 'operatore', 'editor'],
            ],
            'Gestione utenti',
            'Admin',
            'Gestione utenti',
            'utenti',
        );
    }
}
