<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('okr_okr_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('okr_id')->constrained('okr_okrs')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 32)->default('contributor'); // contributor|viewer
            $table->timestamps();
            $table->unique(['okr_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('okr_okr_user');
    }
};
