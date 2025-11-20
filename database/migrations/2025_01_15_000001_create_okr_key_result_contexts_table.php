<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('okr_key_result_contexts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('key_result_id')->constrained('okr_key_results')->cascadeOnDelete();
            $table->string('context_type');
            $table->unsignedBigInteger('context_id');
            $table->unsignedInteger('depth')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_root')->default(false);
            $table->string('context_label')->nullable();
            $table->timestamps();

            // Unique-Index verhindert Doppelzählung
            $table->unique(['key_result_id', 'context_type', 'context_id'], 'okr_key_result_context_unique');

            // Indizes für schnelle Filterung
            $table->index(['context_type', 'context_id'], 'okr_key_result_context_index');
            $table->index('depth', 'okr_key_result_context_depth_index');
            $table->index('is_root', 'okr_key_result_context_root_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('okr_key_result_contexts');
    }
};

