<?php

namespace App\Livewire\Admin\Users;

use App\Models\LaquageReport;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Utilisateurs')]
class Index extends Component
{
    use WithPagination;

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

    public function delete(int $id): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);

        if ($id === auth()->id()) {
            $this->addError('delete', 'Vous ne pouvez pas supprimer votre propre compte.');

            return;
        }

        $user = User::findOrFail($id);

        // The reports are production history: they stay, with "Utilisateur supprimé" as author.
        $reports = LaquageReport::query()->where('user_id', $user->id)->count();

        $user->delete();

        session()->flash('success', match (true) {
            $reports === 0 => 'Utilisateur supprimé avec succès.',
            $reports === 1 => 'Utilisateur supprimé. Son rapport est conservé.',
            default => "Utilisateur supprimé. Ses {$reports} rapports sont conservés.",
        });
    }

    public function render(): View
    {
        return view('components.admin.users.index', [
            'users' => User::with('department')
                ->when(trim($this->search) !== '', function ($query) {
                    $like = '%'.trim($this->search).'%';
                    $query->where(fn ($query) => $query->where('name', 'like', $like)->orWhere('email', 'like', $like));
                })
                ->orderBy('name')
                ->paginate(10),
        ]);
    }
}
