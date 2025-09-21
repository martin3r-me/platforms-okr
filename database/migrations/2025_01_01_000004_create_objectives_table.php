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
        Schema::create('okr_objectives', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('okr_id')->constrained('okr_okrs')->cascadeOnDelete();
            $table->foreignId('cycle_id')->constrained('okr_cycles')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // Ersteller
            $table->foreignId('manager_user_id')->nullable()->constrained('users')->nullOnDelete(); // Verantwortlich

            $table->string('title');
            $table->text('description')->nullable();

            $table->decimal('performance_score', 4, 3)->default(0.0);
            $table->boolean('is_mountain')->default(false); // optional für "ambitionierte" Ziele
            $table->string('status')->default('draft'); // draft | active | done | cancelled

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
        Schema::dropIfExists('okr_objectives');
    }
};
