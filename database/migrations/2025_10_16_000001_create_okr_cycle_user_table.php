<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('okr_cycle_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cycle_id');
            $table->unsignedBigInteger('user_id');
            $table->string('role')->default('contributor');
            $table->timestamps();

            $table->unique(['cycle_id', 'user_id']);
            $table->foreign('cycle_id')->references('id')->on('okr_cycles')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('okr_cycle_user');
    }
};


