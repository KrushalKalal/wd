<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('daily_plans', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();

            $table->date('plan_date');

            // Employee's own note when creating the plan
            $table->text('notes')->nullable();

            // Manager adds remark from web (no approval, just a note)
            $table->text('manager_remark')->nullable();
            $table->foreignId('remark_by')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();
            $table->timestamp('remark_at')->nullable();

            // Day-level route — start of day
            $table->time('day_start_time')->nullable();
            $table->decimal('start_lat', 10, 7)->nullable();
            $table->decimal('start_lng', 10, 7)->nullable();

            // Day-level route — end of day
            $table->time('day_end_time')->nullable();
            $table->decimal('end_lat', 10, 7)->nullable();
            $table->decimal('end_lng', 10, 7)->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // One plan per employee per day
            $table->unique(['employee_id', 'plan_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_plans');
    }
};