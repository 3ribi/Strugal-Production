<?php

namespace Tests\Feature\Laquage;

use App\Models\LaquageReport;
use App\Models\LaquageReportLine;
use App\Models\User;
use App\Services\LaquageExcelExporter;
use App\Support\LaquageFields;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class LaquageFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_models_take_their_fillable_columns_from_the_field_definition(): void
    {
        $this->assertEqualsCanonicalizing(
            ['user_id', ...LaquageFields::headerKeys()],
            (new LaquageReport)->getFillable()
        );
        $this->assertSame(LaquageFields::rowKeys(), (new LaquageReportLine)->getFillable());
    }

    public function test_database_columns_match_the_field_definition(): void
    {
        $reportColumns = Schema::getColumnListing('laquage_reports');
        $lineColumns = Schema::getColumnListing('laquage_report_lines');

        foreach (LaquageFields::headerKeys() as $field) {
            $this->assertContains($field, $reportColumns, "laquage_reports.$field is missing");
        }

        foreach (LaquageFields::rowKeys() as $field) {
            $this->assertContains($field, $lineColumns, "laquage_report_lines.$field is missing");
            $this->assertNotContains($field, $reportColumns, "laquage_reports.$field is a legacy column");
        }
    }

    public function test_each_row_field_has_one_section_and_one_excel_column(): void
    {
        $inSections = array_merge(...array_values(LaquageFields::sections()));
        $this->assertEqualsCanonicalizing(LaquageFields::rowKeys(), $inSections);

        $columns = array_column(LaquageFields::row(), 'excel');
        $this->assertSame($columns, array_values(array_unique($columns)), 'two fields share an Excel column');

        $cells = array_column(LaquageFields::header(), 'excel');
        $this->assertSame($cells, array_values(array_unique($cells)), 'two header fields share an Excel cell');
    }

    public function test_row_rules_reject_negative_counts_and_non_numeric_measures(): void
    {
        $rules = LaquageFields::rowRules();

        $this->assertTrue(Validator::make(['bars_count' => -1], $rules)->fails());
        $this->assertTrue(Validator::make(['thickness_min' => 'abc'], $rules)->fails());
        $this->assertTrue(Validator::make(['bars_count' => 12, 'thickness_min' => '55.25'], $rules)->passes());
    }

    public function test_blank_strings_become_null_and_unknown_keys_are_dropped(): void
    {
        $clean = LaquageFields::cleanRow(['order_number' => '', 'bars_count' => '5', 'hacker' => 'x']);

        $this->assertNull($clean['order_number']);
        $this->assertSame('5', $clean['bars_count']);
        $this->assertArrayNotHasKey('hacker', $clean);
        $this->assertSame(LaquageFields::rowKeys(), array_keys($clean));
    }

    public function test_excel_export_places_every_field_where_the_definition_says(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $report = LaquageReport::create([
            'user_id' => $user->id,
            'report_date' => '2026-10-20',
            'line' => 'Ligne 2',
            'work_period' => 'Matin',
            'operators' => 'Ali, Sara',
            'micrometer_check' => 'OK',
            'brillometer' => '87.5',
            'phase' => 'Phase A',
            'furnace_temperature' => '195.5',
        ]);

        // 26 lines: more than the 23 the template provides, so extra rows must be inserted.
        for ($i = 0; $i < 26; $i++) {
            $report->rows()->create([
                'source_cart' => "S$i", 'order_number' => "C-$i", 'of_number' => "OF$i", 'reference' => "REF$i",
                'bars_count' => 10 + $i, 'cart_number' => "N$i", 'rejected' => $i % 3,
                'thickness_min' => 55.25, 'thickness_max' => 60.5, 'color' => 'Blanc',
                'supplier' => 'Four-X', 'category' => 'A', 'brilliance_average' => 85.5, 'aspect' => 'OK',
            ]);
        }

        ob_start();
        app(LaquageExcelExporter::class)->download($report->fresh('rows'))->sendContent();
        $path = tempnam(sys_get_temp_dir(), 'laquage').'.xlsx';
        file_put_contents($path, ob_get_clean());

        $sheet = IOFactory::load($path)->getSheet(0);
        unlink($path);

        $this->assertSame('20/10/2026', $sheet->getCell('B3')->getValue());
        $this->assertSame('Ligne 2', $sheet->getCell('B2')->getValue());
        $this->assertSame('Matin', $sheet->getCell('D2')->getValue());
        $this->assertSame('Ali, Sara', $sheet->getCell('F2')->getValue());
        $this->assertSame('OK', $sheet->getCell('K2')->getValue());
        $this->assertEquals(87.5, $sheet->getCell('M2')->getValue());
        $this->assertSame('Phase A', $sheet->getCell('O2')->getValue());
        $this->assertEquals(195.5, $sheet->getCell('O3')->getValue());

        $first = LaquageFields::row();
        $this->assertSame('S0', $sheet->getCell($first['source_cart']['excel'].'6')->getValue());
        $this->assertSame('OF0', $sheet->getCell($first['of_number']['excel'].'6')->getValue());
        $this->assertEquals(10, $sheet->getCell($first['bars_count']['excel'].'6')->getValue());
        $this->assertSame('OK', $sheet->getCell($first['aspect']['excel'].'6')->getValue());

        // The 26th line lands on row 31 (6 + 25).
        $this->assertSame('S25', $sheet->getCell($first['source_cart']['excel'].'31')->getValue());
        $this->assertEquals(35, $sheet->getCell($first['bars_count']['excel'].'31')->getValue());
    }
}
