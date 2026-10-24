<?php

namespace App\Livewire\Departments\Laquage;

use App\Models\LaquageReport;
use App\Services\LaquageExcelExporter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Nouveau rapport Laquage')]
class CreateReport extends Component
{
    public int $step = 1;

    // Shared report information (Step 1).
    public string $report_date = '';
    public string $line = '';
    public string $work_period = '';
    public string $operators = '';
    public string $micrometer_check = '';
    public string $brillometer = '';
    public string $phase = '';
    public string $furnace_temperature = '';

    // Unsaved production rows (Step 2). They are persisted only when the report is saved.
    public array $lineRows = [];
    public array $lineForm = [];
    public bool $showLineForm = false;
    public ?int $editingLineIndex = null;

    public ?int $savedReportId = null;
    public string $successMessage = '';

    private const ROW_FIELDS = [
        'source_cart',
        'order_number',
        'of_number',
        'reference',
        'bars_count',
        'cart_number',
        'rejected',
        'thickness_min',
        'thickness_max',
        'color',
        'supplier',
        'category',
        'brilliance_average',
        'aspect',
    ];

    public function mount(): void
    {
        $this->authorizeDepartment();
        $this->report_date = now()->toDateString();
        $this->resetLineForm();
    }

    private function authorizeDepartment(): void
    {
        $user = Auth::user();

        abort_unless($user?->canAccessDepartment('laquage'), 403);
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

    private function rowRules(string $prefix = 'lineForm.'): array
    {
        return [
            $prefix.'source_cart' => ['nullable', 'string', 'max:255'],
            $prefix.'order_number' => ['nullable', 'string', 'max:255'],
            $prefix.'of_number' => ['nullable', 'string', 'max:255'],
            $prefix.'reference' => ['nullable', 'string', 'max:255'],
            $prefix.'bars_count' => ['nullable', 'integer', 'min:0'],
            $prefix.'cart_number' => ['nullable', 'string', 'max:255'],
            $prefix.'rejected' => ['nullable', 'integer', 'min:0'],
            $prefix.'thickness_min' => ['nullable', 'numeric'],
            $prefix.'thickness_max' => ['nullable', 'numeric'],
            $prefix.'color' => ['nullable', 'string', 'max:255'],
            $prefix.'supplier' => ['nullable', 'string', 'max:255'],
            $prefix.'category' => ['nullable', 'string', 'max:255'],
            $prefix.'brilliance_average' => ['nullable', 'numeric'],
            $prefix.'aspect' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function nextStep(): void
    {
        $this->authorizeDepartment();
        abort_if($this->savedReportId, 403, 'Ce rapport a déjà été enregistré.');

        $this->validate($this->headerRules());
        $this->step = 2;
        $this->successMessage = '';
    }

    public function previousStep(): void
    {
        $this->authorizeDepartment();
        abort_if($this->savedReportId, 403, 'Ce rapport a déjà été enregistré.');

        $this->showLineForm = false;
        $this->step = 1;
    }

    public function openLineForm(): void
    {
        $this->authorizeDepartment();
        abort_if($this->savedReportId, 403, 'Ce rapport a déjà été enregistré.');

        $this->resetLineForm();
        $this->editingLineIndex = null;
        $this->showLineForm = true;
    }

    public function editLine(int $index): void
    {
        $this->authorizeDepartment();
        abort_if($this->savedReportId, 403, 'Ce rapport a déjà été enregistré.');
        abort_unless(array_key_exists($index, $this->lineRows), 404);

        $this->resetErrorBag();
        $this->lineForm = array_merge($this->emptyLine(), $this->lineRows[$index]);
        $this->editingLineIndex = $index;
        $this->showLineForm = true;
    }

    public function saveLine(): void
    {
        $this->authorizeDepartment();
        abort_if($this->savedReportId, 403, 'Ce rapport a déjà été enregistré.');

        $this->validate($this->rowRules());

        $row = [];
        foreach (self::ROW_FIELDS as $field) {
            $value = $this->lineForm[$field] ?? null;
            $row[$field] = $value === '' ? null : $value;
        }

        // Each column is optional, but avoid accidentally adding a completely blank row.
        if (collect($row)->every(fn ($value) => $value === null)) {
            $this->addError('lineForm', "Saisissez au moins une valeur avant d'enregistrer cette ligne.");
            return;
        }

        if ($this->editingLineIndex === null) {
            $this->lineRows[] = $row;
        } else {
            $this->lineRows[$this->editingLineIndex] = $row;
        }

        $this->resetLineForm();
        $this->editingLineIndex = null;
        $this->showLineForm = false;
        $this->resetErrorBag();
    }

    public function cancelLine(): void
    {
        $this->authorizeDepartment();
        $this->resetLineForm();
        $this->editingLineIndex = null;
        $this->showLineForm = false;
    }

    public function removeLine(int $index): void
    {
        $this->authorizeDepartment();
        abort_if($this->savedReportId, 403, 'Ce rapport a déjà été enregistré.');
        abort_unless(array_key_exists($index, $this->lineRows), 404);

        array_splice($this->lineRows, $index, 1);
        $this->lineRows = array_values($this->lineRows);
    }

    public function save(): void
    {
        $this->authorizeDepartment();
        abort_if($this->savedReportId, 403, 'Ce rapport a déjà été enregistré.');
        abort_unless($this->step === 2 && !$this->showLineForm, 403);

        $validated = $this->validate(array_merge(
            $this->headerRules(),
            ['lineRows' => ['array']],
            $this->rowRules('lineRows.*.')
        ));

        $header = [];
        foreach (array_keys($this->headerRules()) as $field) {
            $header[$field] = ($validated[$field] ?? null) === '' ? null : ($validated[$field] ?? null);
        }

        $rows = collect($validated['lineRows'] ?? [])->map(function (array $row): array {
            $clean = [];
            foreach (self::ROW_FIELDS as $field) {
                $value = $row[$field] ?? null;
                $clean[$field] = $value === '' ? null : $value;
            }
            return $clean;
        })->filter(fn (array $row) => collect($row)->contains(fn ($value) => $value !== null))->values()->all();

        $report = DB::transaction(function () use ($header, $rows): LaquageReport {
            $report = LaquageReport::create([
                ...$header,
                'user_id' => Auth::id(),
            ]);

            if ($rows !== []) {
                $report->rows()->createMany($rows);
            }

            return $report;
        });

        $this->savedReportId = $report->id;
        $this->successMessage = 'Le rapport et ses lignes ont été enregistrés.';
    }

    public function export(LaquageExcelExporter $exporter)
    {
        $this->authorizeDepartment();
        abort_if(!$this->savedReportId, 404);

        $query = LaquageReport::query()->with('rows');

        if (!Auth::user()->isAdmin()) {
            $query->where('user_id', Auth::id());
        }

        return $exporter->download($query->findOrFail($this->savedReportId));
    }

    private function emptyLine(): array
    {
        return array_fill_keys(self::ROW_FIELDS, '');
    }

    private function resetLineForm(): void
    {
        $this->lineForm = $this->emptyLine();
    }

    public function render()
    {
        return view('components.departments.laquage.create-report');
    }
}
