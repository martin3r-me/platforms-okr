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
        Schema::create('okr_key_results', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('objective_id')->constrained('okr_objectives')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // Erstellt von
            $table->foreignId('manager_user_id')->nullable()->constrained('users')->nullOnDelete(); // Verantwortlich

            $table->string('title');
            $table->text('description')->nullable();

            $table->boolean('is_ambitious')->default(false); // Optional: Ambitioniertes Ziel
            $table->decimal('performance_score', 4, 3)->default(0.0); // 0.000–1.000

            $table->unsignedSmallInteger('order')->default(0); // Sortierreihenfolge innerhalb des Objectives

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('okr_key_results');
    }
};
