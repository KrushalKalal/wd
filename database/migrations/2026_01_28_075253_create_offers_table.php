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
        Schema::create('offers', function (Blueprint $table) {

            $table->id();

            $table->enum('offer_type', ['category', 'Group', 'sales_volume']);

            // optional category mapping
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

            // sales volume based
            $table->integer('min_quantity')->nullable();
            $table->integer('max_quantity')->nullable();

            $table->string('offer_title');
            $table->text('description')->nullable();

            $table->date('start_date');
            $table->date('end_date');

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
