<?php

namespace App\Livewire;

use App\Models\Department;
use App\Models\LaquageReport;
use App\Models\LaquageReportLine;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Tableau de bord')]
class Dashboard extends Component
{
    public function render(): View
    {
        $user = Auth::user();
        $hasLaquage = $user->canAccessDepartment('laquage');

        $data = [
            'user' => $user,
            'hasLaquage' => $hasLaquage,
            'isAdmin' => $user->isAdmin(),
        ];

        if ($hasLaquage) {
            $base = fn () => LaquageReport::query()->visibleTo($user);
            $lines = fn () => LaquageReportLine::query()
                ->whereHas('report', fn ($query) => $query->visibleTo($user));

            $bars = (int) $lines()->sum('bars_count');
            $rejected = (int) $lines()->sum('rejected');

            $days = collect(range(13, 0))->map(fn (int $i) => now()->subDays($i)->startOfDay());
            $perDay = $base()
                ->whereDate('report_date', '>=', now()->subDays(13)->toDateString())
                ->get(['id', 'report_date'])
                ->countBy(fn (LaquageReport $report) => $report->report_date->toDateString());

            $activity = $days->map(fn ($day) => [
                'label' => $day->format('d/m'),
                'date' => $day->format('d/m'),
                'count' => (int) ($perDay[$day->toDateString()] ?? 0),
            ]);

            $data += [
                'totalReports' => $base()->count(),
                'monthReports' => $base()
                    ->whereBetween('report_date', [now()->startOfMonth(), now()->endOfMonth()])
                    ->count(),
                'totalLines' => $lines()->count(),
                'totalBars' => $bars,
                'totalRejected' => $rejected,
                'rejectRate' => $bars > 0 ? round($rejected / $bars * 100, 1) : null,
                'activity' => $activity,
                'recent' => $base()->with('user')->withCount('rows')
                    ->orderByDesc('report_date')->orderByDesc('id')->limit(5)->get(),
            ];
        }

        if ($user->isAdmin()) {
            $data += [
                'usersCount' => User::count(),
                'departmentsCount' => Department::count(),
            ];
        }

        return view('components.dashboard', $data);
    }
}
