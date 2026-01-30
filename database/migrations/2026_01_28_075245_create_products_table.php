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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('category_one_id')->constrained('category_one');
            $table->foreignId('category_two_id')->constrained('category_two');
            $table->foreignId('category_three_id')->constrained('category_three');
            $table->foreignId('p_category_id')->constrained('product_categories');
            $table->decimal('mrp', 12, 2);
            $table->date('edo')->nullable();
            $table->integer('total_stock')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
