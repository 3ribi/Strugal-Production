<?php

namespace App\Services;

use App\Models\LaquageReport;
use App\Support\LaquageFields;
use DateTimeInterface;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
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

        // Fill the header values in the input cells next to the template labels.
        foreach (LaquageFields::header() as $field => $definition) {
            $value = $report->{$field};

            $sheet->setCellValue(
                $definition['excel'],
                $value instanceof DateTimeInterface ? $value->format('d/m/Y') : $value
            );
        }

        $rows = $report->rows;

        // The template has production rows 6-28. Extend the table before its footer if needed.
        $firstDataRow = 6;
        $availableRows = 23;
        $extraRows = max(0, $rows->count() - $availableRows);

        if ($extraRows > 0) {
            $sheet->insertNewRowBefore(29, $extraRows);
            for ($row = 29; $row < 29 + $extraRows; $row++) {
                for ($column = 1; $column <= count(LaquageFields::row()); $column++) {
                    $letter = Coordinate::stringFromColumnIndex($column);
                    $sheet->duplicateStyle($sheet->getStyle($letter.'28'), $letter.$row);
                }
                $sheet->getRowDimension($row)->setRowHeight($sheet->getRowDimension(28)->getRowHeight());
            }
        }

        foreach ($rows as $index => $productionRow) {
            $excelRow = $firstDataRow + $index;
            foreach (LaquageFields::row() as $field => $definition) {
                $sheet->setCellValue($definition['excel'].$excelRow, $productionRow->{$field} ?? null);
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
