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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('dept_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('state_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('area_id')->nullable()->constrained()->nullOnDelete();
            $table->string('pin_code', 10)->nullable();
            $table->string('country')->default('India');
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('contact_number_1', 20)->nullable();
            $table->string('contact_number_2', 20)->nullable();
            $table->string('email_1')->nullable();
            $table->string('email_2')->nullable();
            $table->string('aadhar_number', 20)->nullable();
            $table->string('aadhar_image')->nullable();
            $table->date('dob')->nullable();
            $table->date('doj')->nullable();
            $table->string('designation')->nullable();
            $table->foreignId('reporting_to')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
