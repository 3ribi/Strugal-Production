<?php

namespace App\Livewire\Admin\Users;

use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Utilisateurs')]
class Index extends Component
{
    use WithPagination;

    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $role = 'department';
    public ?string $department_id = null;

    public ?int $editingId = null;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function mount(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->editingId),
            ],
            'role' => ['required', Rule::in(['admin', 'department'])],
            'department_id' => [
                Rule::requiredIf($this->role === 'department'),
                'nullable',
                'integer',
                'exists:departments,id',
            ],
            'password' => [
                $this->editingId ? 'nullable' : 'required',
                'string',
                'min:8',
            ],
        ];
    }

    public function save(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $validated = $this->validate();

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'department_id' => $validated['role'] === 'department'
                ? $validated['department_id']
                : null,
        ];

        if ($this->password !== '') {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->editingId) {
            User::findOrFail($this->editingId)->update($data);
        } else {
            User::create($data);
        }

        $this->resetForm();
        session()->flash('success', 'Utilisateur enregistré avec succès.');
    }

    public function edit(int $id): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        $user = User::findOrFail($id);

        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role;
        $this->department_id = $user->department_id
            ? (string) $user->department_id
            : null;
        $this->password = '';

        $this->resetValidation();
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        if ($id === auth()->id()) {
            $this->addError('delete', 'Vous ne pouvez pas supprimer votre propre compte.');
            return;
        }

        User::findOrFail($id)->delete();

        if ($this->editingId === $id) {
            $this->resetForm();
        }

        session()->flash('success', 'Utilisateur supprimé avec succès.');
    }

    public function resetForm(): void
    {
        $this->reset([
            'name',
            'email',
            'password',
            'department_id',
            'editingId',
        ]);

        $this->role = 'department';
        $this->resetValidation();
    }

    public function render()
    {
        return view('components.admin.users.index', [
            'users' => User::with('department')
                ->when(trim($this->search) !== '', function ($query) {
                    $like = '%'.trim($this->search).'%';
                    $query->where(fn ($query) => $query->where('name', 'like', $like)->orWhere('email', 'like', $like));
                })
                ->orderBy('name')
                ->paginate(10),
            'departments' => Department::orderBy('name')->get(),
        ]);
    }
}