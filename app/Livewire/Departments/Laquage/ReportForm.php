<?php

namespace App\Livewire\Departments\Laquage;

use App\Models\LaquageReport;
use App\Models\LaquageReportLine;
use App\Support\LaquageFields;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Création et modification d'un rapport Laquage sur une seule page.
 * Chaque ligne de production est enregistrée dès qu'elle est ajoutée.
 */
#[Title('Rapport Laquage')]
class ReportForm extends Component
{
    public ?int $reportId = null;

    public string $report_date = '';

    public string $line = '';

    public string $work_period = '';

    public string $operators = '';

    public string $micrometer_check = '';

    public string $brillometer = '';

    public string $phase = '';

    public string $furnace_temperature = '';

    /** @var array<string, string> */
    public array $lineForm = [];

    public ?int $editingLineId = null;

    public function mount(?int $reportId = null): void
    {
        $this->authorizeDepartment();
        $this->resetLineForm();

        if ($reportId === null) {
            $this->report_date = now()->toDateString();

            return;
        }

        $report = $this->visibleReports()->findOrFail($reportId);
        $this->reportId = $report->id;

        foreach (LaquageFields::headerKeys() as $field) {
            $value = $report->{$field};
            $this->{$field} = $field === 'report_date'
                ? $report->report_date->toDateString()
                : (string) ($value ?? '');
        }
    }

    private function authorizeDepartment(): void
    {
        abort_unless(Auth::user()?->canAccessDepartment('laquage'), 403);
    }

    /**
     * @return Builder<LaquageReport>
     */
    private function visibleReports(): Builder
    {
        return LaquageReport::query()->visibleTo(Auth::user());
    }

    /**
     * Crée le rapport s'il n'existe pas encore, sinon met à jour ses informations.
     */
    /**
     * @param  array<string, mixed>  $header
     */
    private function persistHeader(array $header): LaquageReport
    {
        if ($this->reportId === null) {
            $report = LaquageReport::create([...$header, 'user_id' => Auth::id()]);
            $this->reportId = $report->id;

            return $report;
        }

        $report = $this->visibleReports()->findOrFail($this->reportId);
        $report->update($header);

        return $report;
    }

    private function afterFirstSave(string $message): void
    {
        session()->flash('success', $message);

        $this->redirectRoute('departments.laquage.edit', ['reportId' => $this->reportId], navigate: true);
    }

    public function saveHeader(): void
    {
        $this->authorizeDepartment();

        $validated = $this->validate(LaquageFields::headerRules());
        $isNew = $this->reportId === null;

        $this->persistHeader(LaquageFields::cleanHeader($validated));

        if ($isNew) {
            $this->afterFirstSave('Le rapport a été créé. Ajoutez maintenant vos lignes de production.');

            return;
        }

        Flux::toast(variant: 'success', text: 'Informations du rapport enregistrées.');
    }

    public function saveLine(): void
    {
        $this->authorizeDepartment();

        $validated = $this->validate(array_merge(LaquageFields::headerRules(), LaquageFields::rowRules('lineForm.')));
        $row = LaquageFields::cleanRow($validated['lineForm'] ?? []);

        // Chaque colonne est facultative, mais on évite d'enregistrer une ligne entièrement vide.
        if (collect($row)->every(fn ($value) => $value === null)) {
            $this->addError('lineForm', "Saisissez au moins une valeur avant d'ajouter la ligne.");

            return;
        }

        $isNewReport = $this->reportId === null;
        $isUpdate = $this->editingLineId !== null;

        DB::transaction(function () use ($validated, $row) {
            $report = $this->persistHeader(LaquageFields::cleanHeader($validated));

            if ($this->editingLineId !== null) {
                $report->rows()->findOrFail($this->editingLineId)->update($row);
            } else {
                $report->rows()->create($row);
            }
        });

        $this->resetLineForm();
        $this->editingLineId = null;
        $this->resetErrorBag();

        if ($isNewReport) {
            $this->afterFirstSave('Le rapport a été créé et la première ligne enregistrée.');

            return;
        }

        $this->dispatch('line-saved');
        Flux::toast(variant: 'success', text: $isUpdate ? 'Ligne mise à jour.' : 'Ligne ajoutée au rapport.');
    }

    public function editLine(int $lineId): void
    {
        $this->authorizeDepartment();
        abort_if($this->reportId === null, 404);

        $line = $this->visibleReports()->findOrFail($this->reportId)->rows()->findOrFail($lineId);

        $this->resetErrorBag();
        $this->lineForm = [];
        foreach (LaquageFields::rowKeys() as $field) {
            $this->lineForm[$field] = (string) ($line->{$field} ?? '');
        }
        $this->editingLineId = $line->id;

        $this->dispatch('line-editing');
    }

    public function cancelLineEdit(): void
    {
        $this->authorizeDepartment();

        $this->resetLineForm();
        $this->editingLineId = null;
        $this->resetErrorBag();
    }

    public function deleteLine(int $lineId): void
    {
        $this->authorizeDepartment();
        abort_if($this->reportId === null, 404);

        $this->visibleReports()->findOrFail($this->reportId)->rows()->findOrFail($lineId)->delete();

        if ($this->editingLineId === $lineId) {
            $this->cancelLineEdit();
        }

        Flux::toast(variant: 'success', text: 'Ligne supprimée.');
    }

    private function resetLineForm(): void
    {
        $this->lineForm = LaquageFields::emptyRow();
    }

    public function render(): View
    {
        $rows = $this->reportId === null
            ? collect()
            : LaquageReportLine::query()
                ->where('laquage_report_id', $this->reportId)
                ->orderBy('id')
                ->get();

        return view('components.departments.laquage.report-form', [
            'rows' => $rows,
            'totalBars' => (int) $rows->sum('bars_count'),
            'totalRejected' => (int) $rows->sum('rejected'),
        ]);
    }
}
