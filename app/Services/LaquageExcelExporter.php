<?php

namespace App\Services;

use App\Models\LaquageReport;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaquageExcelExporter
{
    public function download(LaquageReport $report): StreamedResponse
    {
        $templatePath = storage_path('app/templates/departments.xlsx');

        abort_unless(is_file($templatePath), 500, 'Excel template not found.');

        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getSheetByName('Laquage')
            ?? $spreadsheet->getSheetByName('Feuil1');

        abort_unless($sheet !== null, 500, 'Laquage sheet not found.');

        // Keep only the Laquage worksheet in the downloaded copy.
        foreach ($spreadsheet->getSheetNames() as $sheetName) {
            if ($sheetName !== $sheet->getTitle()) {
                $spreadsheet->removeSheetByIndex(
                    $spreadsheet->getIndex($spreadsheet->getSheetByName($sheetName))
                );
            }
        }

        $sheet->setTitle('Laquage');
        $spreadsheet->setActiveSheetIndex(0);

        // Fill the header values in adjacent input cells without overwriting labels.
        $sheet->setCellValue('B2', $report->line);
        $sheet->setCellValue('D2', $report->work_period);
        $sheet->setCellValue('F2', $report->operators);
        $sheet->setCellValue('K2', $report->micrometer_check);
        $sheet->setCellValue('B3', $report->report_date?->format('d/m/Y'));
        $sheet->setCellValue('M2', $report->brillometer);
        $sheet->setCellValue('O2', $report->phase);
        $sheet->setCellValue('O3', $report->furnace_temperature);

        $rows = $report->rows;

        // Support older reports created before production rows were moved to their own table.
        if ($rows->isEmpty()) {
            $legacyFields = [
                'source_cart', 'order_number', 'of_number', 'reference', 'bars_count',
                'cart_number', 'rejected', 'thickness_min', 'thickness_max', 'color',
                'supplier', 'category', 'brilliance_average', 'aspect',
            ];
            $legacyRow = [];
            foreach ($legacyFields as $field) {
                $legacyRow[$field] = $report->{$field};
            }
            if (collect($legacyRow)->contains(fn ($value) => $value !== null && $value !== '')) {
                $rows = collect([(object) $legacyRow]);
            }
        }

        // The template has production rows 6-28. Extend the table before its footer if needed.
        $firstDataRow = 6;
        $availableRows = 23;
        $extraRows = max(0, $rows->count() - $availableRows);

        if ($extraRows > 0) {
            $sheet->insertNewRowBefore(29, $extraRows);
            for ($row = 29; $row < 29 + $extraRows; $row++) {
                for ($column = 1; $column <= 14; $column++) {
                    $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column);
                    $sheet->duplicateStyle($sheet->getStyle($letter.'28'), $letter.$row);
                }
                $sheet->getRowDimension($row)->setRowHeight($sheet->getRowDimension(28)->getRowHeight());
            }
        }

        $columns = [
            'A' => 'source_cart',
            'B' => 'order_number',
            'C' => 'of_number',
            'D' => 'reference',
            'E' => 'bars_count',
            'F' => 'cart_number',
            'G' => 'rejected',
            'H' => 'thickness_min',
            'I' => 'thickness_max',
            'J' => 'color',
            'K' => 'supplier',
            'L' => 'category',
            'M' => 'brilliance_average',
            'N' => 'aspect',
        ];

        foreach ($rows as $index => $productionRow) {
            $excelRow = $firstDataRow + $index;
            foreach ($columns as $column => $field) {
                $sheet->setCellValue($column.$excelRow, $productionRow->{$field} ?? null);
            }
        }

        $filename = 'laquage-report-'.$report->id.'.xlsx';

        return response()->streamDownload(
            function () use ($spreadsheet): void {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
                $spreadsheet->disconnectWorksheets();
            },
            $filename,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]
        );
    }
}
