<?php

namespace Tests\Feature\Laquage;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Early reports kept one production row on `laquage_reports` itself. The migration
 * must turn that data into a report line before dropping the columns.
 */
class LegacyRowsMigrationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_legacy_row_data_becomes_a_report_line_and_nothing_is_lost(): void
    {
        $user = User::factory()->create();

        // Go back to the old schema (the two latest migrations).
        $this->artisan('migrate:rollback', ['--step' => 2])->assertExitCode(0);
        $this->assertTrue(Schema::hasColumn('laquage_reports', 'source_cart'));

        $legacyOnly = $this->insertReport($user->id, ['order_number' => 'OLD-1', 'bars_count' => 40, 'rejected' => 3, 'thickness_min' => 55.25, 'cart_number' => 7]);
        $emptyHeaderOnly = $this->insertReport($user->id, []);
        $withLines = $this->insertReport($user->id, ['order_number' => 'IGNORED']);
        DB::table('laquage_report_lines')->insert(['laquage_report_id' => $withLines, 'order_number' => 'REAL', 'created_at' => now(), 'updated_at' => now()]);

        $this->artisan('migrate')->assertExitCode(0);

        $this->assertFalse(Schema::hasColumn('laquage_reports', 'source_cart'));

        $line = DB::table('laquage_report_lines')->where('laquage_report_id', $legacyOnly)->first();
        $this->assertNotNull($line);
        $this->assertSame('OLD-1', $line->order_number);
        $this->assertEquals(40, $line->bars_count);
        $this->assertEquals(3, $line->rejected);
        $this->assertEquals(55.25, $line->thickness_min);
        $this->assertEquals('7', $line->cart_number);

        // A header-only report gets no empty line; a report that already has lines is untouched.
        $this->assertSame(0, DB::table('laquage_report_lines')->where('laquage_report_id', $emptyHeaderOnly)->count());
        $this->assertSame(['REAL'], DB::table('laquage_report_lines')->where('laquage_report_id', $withLines)->pluck('order_number')->all());
        $this->assertSame(3, DB::table('laquage_reports')->count());
    }

    /**
     * @param  array<string, mixed>  $legacy
     */
    private function insertReport(int $userId, array $legacy): int
    {
        return DB::table('laquage_reports')->insertGetId(array_merge([
            'user_id' => $userId,
            'report_date' => '2026-10-01',
            'created_at' => now(),
            'updated_at' => now(),
        ], $legacy));
    }
}
