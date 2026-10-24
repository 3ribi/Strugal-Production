<?php

namespace App\Livewire\Admin\Users;

use App\Models\Department;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Modifier l\'utilisateur')]
class Edit extends Component
{
    public User $user;

    public string $name = '';

    public string $email = '';

    public $department_id = '';

    public function mount(User $user): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->department_id = $user->department_id ?? '';
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
        ]);

        // Administrators do not belong to a department.
        if ($this->user->role === 'admin') {
            $validated['department_id'] = null;
        }

        $this->user->update($validated);

        session()->flash('success', 'Utilisateur mis à jour avec succès.');

        $this->redirectRoute('admin.users.index');
    }

    public function render()
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        return view('components.admin.users.edit', [
            'departments' => Department::orderBy('name')->get(),
        ]);
    }
}