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
        Schema::create('okr_forecasts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('title'); // e.g. "Forecast 2028"
            $table->date('target_date'); // Target date of the forecast
            $table->text('content')->nullable(); // Current Markdown content

            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('okr_forecasts');
    }
};
