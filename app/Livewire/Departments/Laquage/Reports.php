<?php

namespace App\Livewire\Departments\Laquage;

use App\Models\LaquageReport;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Rapports Laquage')]
class Reports extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'du', except: '')]
    public string $dateFrom = '';

    #[Url(as: 'au', except: '')]
    public string $dateTo = '';

    public function mount(): void
    {
        abort_unless(Auth::user()->canAccessDepartment('laquage'), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $user = Auth::user();
        abort_unless($user->canAccessDepartment('laquage'), 403);

        LaquageReport::query()->visibleTo($user)->findOrFail($id)->delete();

        session()->flash('success', 'Le rapport a été supprimé.');
    }

    public function render()
    {
        $user = Auth::user();
        $search = trim($this->search);

        $reports = LaquageReport::query()
            ->visibleTo($user)
            ->with('user')
            ->withCount('rows')
            ->withSum('rows as rejected_total', 'rejected')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $like = '%'.$search.'%';
                    $query->where('line', 'like', $like)
                        ->orWhere('operators', 'like', $like)
                        ->orWhere('phase', 'like', $like)
                        ->orWhere('work_period', 'like', $like);
                });
            })
            ->when($this->dateFrom !== '', fn ($query) => $query->whereDate('report_date', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($query) => $query->whereDate('report_date', '<=', $this->dateTo))
            ->orderByDesc('report_date')
            ->orderByDesc('id')
            ->paginate(10);

        return view('components.departments.laquage.reports', [
            'reports' => $reports,
            'isAdmin' => $user->isAdmin(),
            'hasFilters' => $search !== '' || $this->dateFrom !== '' || $this->dateTo !== '',
        ]);
    }
}
