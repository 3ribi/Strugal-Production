<?php

namespace App\Livewire\Admin\Users;

use App\Concerns\PasswordValidationRules;
use App\Models\Department;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Modifier l\'utilisateur')]
class Edit extends Component
{
    use PasswordValidationRules;

    public User $user;

    public string $name = '';

    public string $email = '';

    public string $department_id = '';

    /**
     * Optional: when filled, an administrator sets a new password for this user
     * (this is how a forgotten password is recovered).
     */
    public string $password = '';

    public function mount(User $user): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->department_id = (string) ($user->department_id ?? '');
    }

    public function update(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user->id),
            ],
            'department_id' => [
                'required_if:user.role,department',
                'nullable',
                'exists:departments,id',
            ],
            'password' => $this->assignedPasswordRules(required: false),
        ]);

        $newPassword = $validated['password'] ?? '';
        unset($validated['password']);

        // Administrators do not belong to a department.
        if ($this->user->role === 'admin') {
            $validated['department_id'] = null;
        }

        $this->user->update($validated);

        if (filled($newPassword)) {
            $this->user->forceFill([
                'password' => Hash::make($newPassword),
                'remember_token' => Str::random(60),
            ])->save();

            $this->endOtherSessionsOfUser();
        }

        session()->flash('success', filled($newPassword)
            ? 'Utilisateur mis à jour. Le nouveau mot de passe est actif : l\'utilisateur devra se reconnecter.'
            : 'Utilisateur mis à jour avec succès.');

        $this->redirectRoute('admin.users.index');
    }

    /**
     * After a password reset, the user's existing sessions must stop working
     * (never the session of the administrator who is making the change).
     */
    private function endOtherSessionsOfUser(): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $this->user->id)
            ->where('id', '!=', session()->getId())
            ->delete();
    }

    public function render(): View
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        return view('components.admin.users.edit', [
            'departments' => Department::orderBy('name')->get(),
        ]);
    }
}
