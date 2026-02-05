<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('okr_objective_milestone', function (Blueprint $table) {
            $table->id();
            $table->foreignId('objective_id')->constrained('okr_objectives')->cascadeOnDelete();
            $table->foreignId('milestone_id')->constrained('okr_milestones')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['objective_id', 'milestone_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('okr_objective_milestone');
    }
};
