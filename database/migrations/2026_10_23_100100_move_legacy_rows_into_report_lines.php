<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Early reports stored one production row directly on `laquage_reports`.
 * Production rows now live in `laquage_report_lines`, so:
 *   1. every old report that has data in those columns but no line gets that data as a line,
 *   2. the now-unused columns are dropped from `laquage_reports`.
 * No production data is lost.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $legacyColumns = [
        'source_cart', 'order_number', 'of_number', 'reference', 'bars_count',
        'cart_number', 'rejected', 'thickness_min', 'thickness_max', 'color',
        'supplier', 'category', 'brilliance_average', 'aspect',
    ];

    public function up(): void
    {
        foreach (DB::table('laquage_reports')->orderBy('id')->get() as $report) {
            $hasLines = DB::table('laquage_report_lines')->where('laquage_report_id', $report->id)->exists();

            if ($hasLines) {
                continue;
            }

            $values = [];
            foreach ($this->legacyColumns as $column) {
                $values[$column] = $report->{$column};
            }

            if (collect($values)->every(fn ($value) => $value === null || $value === '')) {
                continue;
            }

            DB::table('laquage_report_lines')->insert([
                ...$values,
                'laquage_report_id' => $report->id,
                'created_at' => $report->created_at,
                'updated_at' => $report->updated_at,
            ]);
        }

        Schema::table('laquage_reports', function (Blueprint $table) {
            $table->dropColumn($this->legacyColumns);
        });
    }

    /**
     * Restores the (empty) columns only; the data now lives in the report lines.
     */
    public function down(): void
    {
        Schema::table('laquage_reports', function (Blueprint $table) {
            $table->string('source_cart')->nullable();
            $table->string('order_number')->nullable();
            $table->string('of_number')->nullable();
            $table->string('reference')->nullable();
            $table->unsignedInteger('bars_count')->nullable();
            $table->unsignedInteger('cart_number')->nullable();
            $table->unsignedInteger('rejected')->nullable();
            $table->decimal('thickness_min', 10, 2)->nullable();
            $table->decimal('thickness_max', 10, 2)->nullable();
            $table->string('color')->nullable();
            $table->string('supplier')->nullable();
            $table->string('category')->nullable();
            $table->decimal('brilliance_average', 10, 2)->nullable();
            $table->string('aspect')->nullable();
        });
    }
};
