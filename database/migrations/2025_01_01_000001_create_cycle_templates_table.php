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
        Schema::create('okr_cycle_templates', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('label');        // z. B. Q2/2025
            $table->date('starts_at');
            $table->date('ends_at');
            $table->string('type')->default('quarter'); // quarter, project, sprint, etc.
            $table->boolean('is_current')->default(false);
            $table->integer('sort_index')->nullable();  // für manuelle Reihenfolge

            $table->boolean('is_standard')->default(true); // von System generiert
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('okr_cycle_templates');
    }
};
