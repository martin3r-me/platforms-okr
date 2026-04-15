<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('okr_key_result_milestone', function (Blueprint $table) {
            $table->id();
            $table->foreignId('key_result_id')->constrained('okr_key_results')->cascadeOnDelete();
            $table->foreignId('milestone_id')->constrained('okr_milestones')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['key_result_id', 'milestone_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('okr_key_result_milestone');
    }
};
