<?php

namespace App\Livewire\Departments\Laquage;

use App\Models\LaquageReport;
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

    public function delete()
    {
        $user = Auth::user();
        abort_unless($user->canAccessDepartment('laquage'), 403);

        LaquageReport::query()->visibleTo($user)->findOrFail($this->report->id)->delete();

        session()->flash('success', 'Le rapport a été supprimé.');

        return $this->redirectRoute('departments.laquage.index', navigate: true);
    }

    public function render()
    {
        $rows = $this->report->rows;

        // Rapports anciens : les valeurs de production étaient stockées sur le rapport lui-même.
        if ($rows->isEmpty()) {
            $legacy = (object) $this->report->only([
                'source_cart', 'order_number', 'of_number', 'reference', 'bars_count',
                'cart_number', 'rejected', 'thickness_min', 'thickness_max', 'color',
                'supplier', 'category', 'brilliance_average', 'aspect',
            ]);

            if (collect((array) $legacy)->contains(fn ($value) => $value !== null && $value !== '')) {
                $rows = collect([$legacy]);
            }
        }

        return view('components.departments.laquage.show-report', [
            'rows' => $rows,
            'totalBars' => (int) $rows->sum('bars_count'),
            'totalRejected' => (int) $rows->sum('rejected'),
            'averageBrilliance' => $rows->whereNotNull('brilliance_average')->avg('brilliance_average'),
        ]);
    }
}
