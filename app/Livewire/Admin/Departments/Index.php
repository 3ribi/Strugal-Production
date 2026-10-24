<?php

namespace App\Livewire\Admin\Departments;

use App\Models\Department;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Départements')]
class Index extends Component
{
    public string $name = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    public function createDepartment(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:100', 'unique:departments,name'],
        ]);

        Department::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
        ]);

        $this->reset('name');

        session()->flash('success', 'Département créé avec succès.');
    }

    public function deleteDepartment(int $id): void
    {
        $department = Department::findOrFail($id);

        if ($department->users()->exists()) {
            $this->addError('name', 'Impossible de supprimer un département auquel des utilisateurs sont rattachés.');

            return;
        }

        $department->delete();

        session()->flash('success', 'Département supprimé avec succès.');
    }

    public function render(): View
    {
        return view('components.admin.departments.index', [
            'departments' => Department::withCount('users')
                ->orderBy('name')
                ->get(),
        ]);
    }
}
