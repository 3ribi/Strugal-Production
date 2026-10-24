<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Deleting a user must not delete their reports: the reports are production history.
 * The foreign key becomes "set null" instead of "cascade"; the report then shows
 * "Utilisateur supprimé" as its author.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laquage_reports', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('laquage_reports', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * The column stays nullable on rollback because reports without an author may exist.
     */
    public function down(): void
    {
        Schema::table('laquage_reports', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('laquage_reports', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
