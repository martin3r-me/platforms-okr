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
        Schema::create('okr_key_result_performances', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Bezug zum Key Result
            $table->foreignId('key_result_id')
                  ->constrained('okr_key_results')
                  ->cascadeOnDelete();

            // Typ des Performance-Modells
            $table->enum('type', ['boolean', 'percentage', 'absolute', 'calculated'])->default('calculated');

            // Werte für die Typen
            $table->boolean('is_completed')->default(false); // Nur für type=boolean
            $table->decimal('current_value', 15, 2)->default(0); // Für percentage/absolute
            $table->decimal('target_value', 15, 2)->nullable();  // Optionaler Zielwert
            $table->decimal('calculated_value', 15, 2)->nullable(); // Für type=calculated

            // Einheitlicher Score und Tendenz
            $table->decimal('performance_score', 4, 3)->default(0.0); // z. B. 0.845
            $table->string('tendency', 10)->nullable(); // z. B. "up", "down", "stable"

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('okr_key_result_performances');
    }
};
