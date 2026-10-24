<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laquage_reports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->date('report_date');
            $table->string('line')->nullable();
            $table->string('work_period')->nullable();
            $table->text('operators')->nullable();

            $table->string('micrometer_check')->nullable();
            $table->string('brillometer')->nullable();
            $table->string('phase')->nullable();
            $table->string('furnace_temperature')->nullable();

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

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laquage_reports');
    }
};
