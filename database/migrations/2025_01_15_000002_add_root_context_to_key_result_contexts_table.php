<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('okr_key_result_contexts', function (Blueprint $table) {
            $table->string('root_context_type')->nullable()->after('is_root');
            $table->unsignedBigInteger('root_context_id')->nullable()->after('root_context_type');

            $table->index(['root_context_type', 'root_context_id'], 'okr_key_result_context_root_context_index');
        });
    }

    public function down(): void
    {
        Schema::table('okr_key_result_contexts', function (Blueprint $table) {
            $table->dropIndex('okr_key_result_context_root_context_index');
            $table->dropColumn(['root_context_type', 'root_context_id']);
        });
    }
};

