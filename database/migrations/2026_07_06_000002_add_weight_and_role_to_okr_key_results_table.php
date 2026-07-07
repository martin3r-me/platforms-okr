<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gewichtung + Rolle für die Rollup-Aggregation:
 *  - okr_key_results.weight  → Gewicht des KR im Objective-Score (Default 1.00)
 *  - okr_key_results.role    → score | gate | info (analog KeyResultMeasure-Rollen,
 *                              nur eine Ebene höher: gate blockt Objective-Completion,
 *                              verdünnt aber den Score nicht; info = nur Anzeige)
 *  - okr_objectives.weight   → Gewicht des Objectives im Cycle-Score (Default 1.00)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('okr_key_results', function (Blueprint $table) {
            $table->decimal('weight', 5, 2)->default(1)->after('performance_score');
            $table->string('role', 16)->default('score')->after('weight');
        });

        Schema::table('okr_objectives', function (Blueprint $table) {
            $table->decimal('weight', 5, 2)->default(1)->after('performance_score');
        });
    }

    public function down(): void
    {
        Schema::table('okr_key_results', function (Blueprint $table) {
            $table->dropColumn(['weight', 'role']);
        });

        Schema::table('okr_objectives', function (Blueprint $table) {
            $table->dropColumn('weight');
        });
    }
};
