<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('okr_cycles', function (Blueprint $table) {
            if (!Schema::hasColumn('okr_cycles', 'notes')) {
                $table->text('notes')->nullable()->after('status');
            }
            if (!Schema::hasColumn('okr_cycles', 'description')) {
                $table->text('description')->nullable()->after('notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('okr_cycles', function (Blueprint $table) {
            if (Schema::hasColumn('okr_cycles', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('okr_cycles', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
