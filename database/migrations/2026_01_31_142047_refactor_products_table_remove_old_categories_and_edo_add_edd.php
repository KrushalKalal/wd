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
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('edd', 12, 2)->nullable()->after('mrp');
            $table->dropForeign(['category_one_id']);
            $table->dropForeign(['category_two_id']);
            $table->dropForeign(['category_three_id']);

            $table->dropColumn([
                'category_one_id',
                'category_two_id',
                'category_three_id',
                'edo',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_one_id')->constrained('category_one');
            $table->foreignId('category_two_id')->constrained('category_two');
            $table->foreignId('category_three_id')->constrained('category_three');
            $table->date('edo')->nullable();

            $table->dropColumn('edd');
        });
    }
};
