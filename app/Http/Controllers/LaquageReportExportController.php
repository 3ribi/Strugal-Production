<?php

namespace App\Http\Controllers;

use App\Models\LaquageReport;
use App\Services\LaquageExcelExporter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaquageReportExportController extends Controller
{
    public function __invoke(Request $request, int $report, LaquageExcelExporter $exporter): StreamedResponse
    {
        $user = $request->user();

        abort_unless($user->canAccessDepartment('laquage'), 403);

        $model = LaquageReport::query()
            ->visibleTo($user)
            ->with('rows')
            ->findOrFail($report);

        return $exporter->download($model);
    }
}
