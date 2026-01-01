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
        Schema::table('okr_objectives', function (Blueprint $table) {
            $table->foreignId('vision_id')->nullable()->constrained('okr_strategic_documents')->nullOnDelete()->after('description');
            $table->foreignId('regnose_id')->nullable()->constrained('okr_strategic_documents')->nullOnDelete()->after('vision_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('okr_objectives', function (Blueprint $table) {
            $table->dropForeign(['vision_id']);
            $table->dropForeign(['regnose_id']);
            $table->dropColumn(['vision_id', 'regnose_id']);
        });
    }
};

