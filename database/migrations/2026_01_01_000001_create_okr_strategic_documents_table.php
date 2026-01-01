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
        Schema::create('okr_strategic_documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->enum('type', ['mission', 'vision', 'regnose']);
            $table->string('title');
            $table->text('content')->nullable(); // Markdown / Rich Text
            $table->integer('version')->default(1);
            $table->boolean('is_active')->default(false);
            $table->date('valid_from');
            $table->text('change_note')->nullable(); // Optional: Kurzbeschreibung / Änderungsgrund

            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('okr_strategic_documents');
    }
};

