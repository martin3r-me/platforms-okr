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
        Schema::create('okrs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('title');
            $table->text('description')->nullable();

            $table->decimal('performance_score', 4, 3)->default(0.0);

            $table->boolean('auto_transfer')->default(false);
            $table->boolean('is_template')->default(false);

            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('manager_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('okrs');
    }
};
