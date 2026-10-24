<?php

namespace App\Livewire\Departments\Laquage;

use App\Models\LaquageReport;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Détail du rapport')]
class ShowReport extends Component
{
    public LaquageReport $report;

    public function mount(int $reportId): void
    {
        $user = Auth::user();
        abort_unless($user->canAccessDepartment('laquage'), 403);

        $this->report = LaquageReport::query()
            ->visibleTo($user)
            ->with(['rows', 'user'])
            ->findOrFail($reportId);
    }

    public function delete(): void
    {
        $user = Auth::user();
        abort_unless($user->canAccessDepartment('laquage'), 403);

        $report = LaquageReport::query()->visibleTo($user)->findOrFail($this->report->id);
        abort_unless($report->canBeDeletedBy($user), 403);

        $report->delete();

        session()->flash('success', 'Le rapport a été supprimé.');

        $this->redirectRoute('departments.laquage.index', navigate: true);
    }

    public function render(): View
    {
        $rows = $this->report->rows;

        return view('components.departments.laquage.show-report', [
            'rows' => $rows,
            'canDelete' => $this->report->canBeDeletedBy(Auth::user()),
            'totalBars' => (int) $rows->sum('bars_count'),
            'totalRejected' => (int) $rows->sum('rejected'),
            'averageBrilliance' => $rows->whereNotNull('brilliance_average')->avg('brilliance_average'),
        ]);
    }
}
