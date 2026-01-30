<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('state_id')->constrained();
            $table->foreignId('city_id')->constrained();
            $table->foreignId('area_id')->constrained();
            $table->string('pin_code', 10)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->foreignId('category_one_id')
                ->nullable()
                ->constrained('category_one')
                ->nullOnDelete();

            $table->foreignId('category_two_id')
                ->nullable()
                ->constrained('category_two')
                ->nullOnDelete();

            $table->foreignId('category_three_id')
                ->nullable()
                ->constrained('category_three')
                ->nullOnDelete();
            $table->string('contact_number_1', 20)->nullable();
            $table->string('contact_number_2', 20)->nullable();
            $table->string('email')->nullable();
            $table->json('billing_details')->nullable();
            $table->json('shipping_details')->nullable();
            $table->boolean('manual_stock_entry')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
