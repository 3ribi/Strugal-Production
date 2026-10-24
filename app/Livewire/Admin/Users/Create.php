<?php

namespace App\Livewire\Admin\Users;

use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Nouvel utilisateur')]
class Create extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public ?string $departmentId = null;

    public function createUser(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12'],
            'departmentId' => [
                'required',
                'integer',
                Rule::exists('departments', 'id'),
            ],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'department',
            'department_id' => $validated['departmentId'],
        ]);

        session()->flash('success', 'Utilisateur créé avec succès.');

        $this->redirectRoute('admin.users.index');
    }

    public function render()
    {
        return view('components.admin.users.create', [
            'departments' => Department::orderBy('name')->get(),
        ]);
    }
}