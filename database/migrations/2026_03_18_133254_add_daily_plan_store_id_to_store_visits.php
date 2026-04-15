<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('store_visits', function (Blueprint $table) {
            // Nullable — null means walk-in / unplanned visit
            // Set means visit was part of a daily plan
            $table->foreignId('daily_plan_store_id')
                ->nullable()
                ->after('visit_summary')
                ->constrained('daily_plan_stores')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('store_visits', function (Blueprint $table) {
            $table->dropForeign(['daily_plan_store_id']);
            $table->dropColumn('daily_plan_store_id');
        });
    }
};