<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('daily_plan_stores', function (Blueprint $table) {
            $table->id();

            $table->foreignId('daily_plan_id')
                ->constrained('daily_plans')
                ->cascadeOnDelete();

            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete();

            // Drag-sorted order employee set in app (1, 2, 3...)
            $table->unsignedSmallInteger('visit_order')->default(1);

            // Optional time employee expects to arrive
            $table->time('planned_time')->nullable();

            // Updated as employee visits / skips
            $table->enum('status', ['pending', 'visited', 'skipped'])->default('pending');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_plan_stores');
    }
};