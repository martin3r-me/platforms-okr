<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Remove regnose_id foreign key and column from objectives
        Schema::table('okr_objectives', function (Blueprint $table) {
            $table->dropForeign(['regnose_id']);
            $table->dropColumn('regnose_id');
        });

        // 2. Soft-delete existing regnose strategic documents
        DB::table('okr_strategic_documents')
            ->where('type', 'regnose')
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now()]);

        // 3. Update enum to remove 'regnose' type
        DB::statement("ALTER TABLE okr_strategic_documents MODIFY COLUMN type ENUM('mission', 'vision') NOT NULL");
    }

    public function down(): void
    {
        // 1. Restore enum with regnose
        DB::statement("ALTER TABLE okr_strategic_documents MODIFY COLUMN type ENUM('mission', 'vision', 'regnose') NOT NULL");

        // 2. Restore soft-deleted regnose documents
        DB::table('okr_strategic_documents')
            ->where('type', 'regnose')
            ->whereNotNull('deleted_at')
            ->update(['deleted_at' => null]);

        // 3. Re-add regnose_id column to objectives
        Schema::table('okr_objectives', function (Blueprint $table) {
            $table->foreignId('regnose_id')->nullable()->constrained('okr_strategic_documents')->nullOnDelete()->after('vision_id');
        });
    }
};
