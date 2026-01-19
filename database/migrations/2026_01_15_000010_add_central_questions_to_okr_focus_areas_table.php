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
        Schema::table('okr_focus_areas', function (Blueprint $table) {
            $table->text('central_question_vision_images')->nullable()->after('content');
            $table->text('central_question_obstacles')->nullable()->after('central_question_vision_images');
            $table->text('central_question_milestones')->nullable()->after('central_question_obstacles');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('okr_focus_areas', function (Blueprint $table) {
            $table->dropColumn([
                'central_question_vision_images',
                'central_question_obstacles',
                'central_question_milestones',
            ]);
        });
    }
};
