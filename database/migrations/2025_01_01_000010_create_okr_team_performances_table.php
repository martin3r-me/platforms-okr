<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('okr_team_performances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');
            
            // Team Performance Metriken
            $table->decimal('average_score', 5, 2)->default(0);
            $table->integer('total_okrs')->default(0);
            $table->integer('active_okrs')->default(0);
            $table->integer('successful_okrs')->default(0); // ≥80%
            $table->integer('draft_okrs')->default(0);
            $table->integer('completed_okrs')->default(0);
            
            // Objectives & Key Results
            $table->integer('total_objectives')->default(0);
            $table->integer('achieved_objectives')->default(0);
            $table->integer('total_key_results')->default(0);
            $table->integer('achieved_key_results')->default(0);
            $table->integer('open_key_results')->default(0);
            
            // Zyklen
            $table->integer('active_cycles')->default(0);
            $table->integer('current_cycles')->default(0);
            
            // Performance Trends (vs. vorheriger Snapshot)
            $table->decimal('score_trend', 5, 2)->default(0); // +5.2% oder -2.1%
            $table->integer('okr_trend')->default(0); // +3 oder -1
            $table->integer('achievement_trend')->default(0); // +2 oder -1
            
            $table->timestamps();
            
            // Indizes
            $table->unique(['team_id', 'created_at']);
            $table->index(['team_id', 'created_at']);
            $table->index('created_at');
            
            // Foreign Key
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('okr_team_performances');
    }
};
