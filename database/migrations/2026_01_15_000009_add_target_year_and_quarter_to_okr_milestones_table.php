<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('okr_milestones', function (Blueprint $table) {
            $table->year('target_year')->nullable()->after('target_date');
            $table->tinyInteger('target_quarter')->nullable()->after('target_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('okr_milestones', function (Blueprint $table) {
            $table->dropColumn(['target_year', 'target_quarter']);
        });
    }
};
