<?php

namespace App\Livewire\Departments\Laquage;

use App\Models\LaquageReport;
use App\Models\LaquageReportLine;
use Flux\Flux;
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

    public array $lineForm = [];
    public ?int $editingLineId = null;

    public const HEADER_FIELDS = [
        'report_date', 'line', 'work_period', 'operators',
        'micrometer_check', 'brillometer', 'phase', 'furnace_temperature',
    ];

    public const ROW_FIELDS = [
        'source_cart', 'order_number', 'of_number', 'reference', 'bars_count',
        'cart_number', 'rejected', 'thickness_min', 'thickness_max', 'color',
        'supplier', 'category', 'brilliance_average', 'aspect',
    ];

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

        foreach (self::HEADER_FIELDS as $field) {
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

    private function visibleReports()
    {
        return LaquageReport::query()->visibleTo(Auth::user());
    }

    private function headerRules(): array
    {
        return [
            'report_date' => ['required', 'date'],
            'line' => ['nullable', 'string', 'max:255'],
            'work_period' => ['nullable', 'string', 'max:255'],
            'operators' => ['nullable', 'string', 'max:1000'],
            'micrometer_check' => ['nullable', 'string', 'max:255'],
            'brillometer' => ['nullable', 'numeric'],
            'phase' => ['nullable', 'string', 'max:255'],
            'furnace_temperature' => ['nullable', 'numeric'],
        ];
    }

    private function rowRules(): array
    {
        return [
            'lineForm.source_cart' => ['nullable', 'string', 'max:255'],
            'lineForm.order_number' => ['nullable', 'string', 'max:255'],
            'lineForm.of_number' => ['nullable', 'string', 'max:255'],
            'lineForm.reference' => ['nullable', 'string', 'max:255'],
            'lineForm.bars_count' => ['nullable', 'integer', 'min:0'],
            'lineForm.cart_number' => ['nullable', 'string', 'max:255'],
            'lineForm.rejected' => ['nullable', 'integer', 'min:0'],
            'lineForm.thickness_min' => ['nullable', 'numeric'],
            'lineForm.thickness_max' => ['nullable', 'numeric'],
            'lineForm.color' => ['nullable', 'string', 'max:255'],
            'lineForm.supplier' => ['nullable', 'string', 'max:255'],
            'lineForm.category' => ['nullable', 'string', 'max:255'],
            'lineForm.brilliance_average' => ['nullable', 'numeric'],
            'lineForm.aspect' => ['nullable', 'string', 'max:255'],
        ];
    }

    private function cleanHeader(array $validated): array
    {
        $header = [];
        foreach (self::HEADER_FIELDS as $field) {
            $value = $validated[$field] ?? null;
            $header[$field] = $value === '' ? null : $value;
        }

        return $header;
    }

    private function cleanRow(array $source): array
    {
        $row = [];
        foreach (self::ROW_FIELDS as $field) {
            $value = $source[$field] ?? null;
            $row[$field] = $value === '' ? null : $value;
        }

        return $row;
    }

    /**
     * Crée le rapport s'il n'existe pas encore, sinon met à jour ses informations.
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

        $validated = $this->validate($this->headerRules());
        $isNew = $this->reportId === null;

        $this->persistHeader($this->cleanHeader($validated));

        if ($isNew) {
            $this->afterFirstSave('Le rapport a été créé. Ajoutez maintenant vos lignes de production.');

            return;
        }

        Flux::toast(variant: 'success', text: 'Informations du rapport enregistrées.');
    }

    public function saveLine(): void
    {
        $this->authorizeDepartment();

        $validated = $this->validate(array_merge($this->headerRules(), $this->rowRules()));
        $row = $this->cleanRow($validated['lineForm'] ?? []);

        // Chaque colonne est facultative, mais on évite d'enregistrer une ligne entièrement vide.
        if (collect($row)->every(fn ($value) => $value === null)) {
            $this->addError('lineForm', "Saisissez au moins une valeur avant d'ajouter la ligne.");

            return;
        }

        $isNewReport = $this->reportId === null;
        $isUpdate = $this->editingLineId !== null;

        DB::transaction(function () use ($validated, $row) {
            $report = $this->persistHeader($this->cleanHeader($validated));

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
        foreach (self::ROW_FIELDS as $field) {
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
        $this->lineForm = array_fill_keys(self::ROW_FIELDS, '');
    }

    public function render()
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
