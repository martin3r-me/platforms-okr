<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Measure-Layer für Key Results.
 *
 * Ein Key Result hat 1..n Measures. Jedes Measure bindet eine Metrik einer Quelle
 * (metric_key + selector) mit einer Rolle (score|gate|cap|info), Ziel, Baseline und
 * Gewicht. Die Engine normalisiert (Gap-Closure) und aggregiert die Measures zum
 * KR-Ergebnis (Erreichungsquote + erreicht/nicht), das weiterhin als
 * KeyResultPerformance-Snapshot geschrieben wird.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('okr_key_result_measures', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('key_result_id')->constrained('okr_key_results')->cascadeOnDelete();
            // Für instance-Bindings: Verweis auf den KeyResultContext (Reverse-Navigation)
            $table->foreignId('key_result_context_id')->nullable()
                ->constrained('okr_key_result_contexts')->nullOnDelete();

            // Quelle & Auswahl
            $table->string('metric_key');                 // z.B. "planner.tasks_done_ratio" oder "manual"
            $table->json('selector')->nullable();         // {project_id: 193}
            $table->string('binding')->default('instance'); // instance|kr_entity|team

            // Bewertungs-Konfiguration (OKR-Seite)
            $table->string('role')->default('score');     // score|gate|cap|info (offenes Enum)
            $table->string('value_type')->nullable();     // ratio|count|boolean|number
            $table->string('polarity')->default('up');    // up|down
            $table->decimal('target_value', 20, 4)->nullable();
            $table->decimal('baseline_value', 20, 4)->nullable(); // null → Auto-Freeze beim ersten Sync
            $table->decimal('weight', 6, 2)->default(1);  // nur bei role=score relevant
            $table->string('window_mode')->nullable();    // cumulative|period

            // Letzter Sync-Stand
            $table->decimal('current_value', 20, 4)->nullable();
            $table->decimal('achievement', 4, 3)->nullable(); // normalisiert [0,1]
            $table->boolean('is_available')->default(true);   // false → N/A, aus der Aggregation raus
            $table->timestamp('last_synced_at')->nullable();

            $table->string('label')->nullable();
            $table->integer('order')->default(0);

            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['key_result_id', 'role']);
            $table->index('metric_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('okr_key_result_measures');
    }
};
