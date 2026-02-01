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
        Schema::table('offers', function (Blueprint $table) {
            \DB::statement("ALTER TABLE offers MODIFY COLUMN offer_type ENUM('product_category', 'store_category', 'sales_volume', 'location') NOT NULL");

            $table->decimal('offer_percentage', 5, 2)
                ->nullable()
                ->after('description');

            $table->foreignId('p_category_id')
                ->nullable()
                ->constrained('product_categories')
                ->nullOnDelete()
                ->after('offer_percentage');

            $table->json('store_ids')
                ->nullable()
                ->after('category_three_id');   

            $table->decimal('min_sales_amount', 12, 2)
                ->nullable()
                ->after('store_ids');

            $table->decimal('max_sales_amount', 12, 2)
                ->nullable()
                ->after('min_sales_amount');

            $table->foreignId('state_id')
                ->nullable()
                ->constrained('states')
                ->nullOnDelete()
                ->after('max_sales_amount');

            $table->foreignId('city_id')
                ->nullable()
                ->constrained('cities')
                ->nullOnDelete()
                ->after('state_id');

            $table->foreignId('area_id')
                ->nullable()
                ->constrained('areas')
                ->nullOnDelete()
                ->after('city_id');

      
     
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropForeign(['area_id']);
            $table->dropForeign(['city_id']);
            $table->dropForeign(['state_id']);
            $table->dropColumn(['area_id', 'city_id', 'state_id']);

            $table->dropColumn(['max_sales_amount', 'min_sales_amount']);

            $table->dropColumn('store_ids');

            $table->dropForeign(['p_category_id']);
            $table->dropColumn('p_category_id');

            $table->dropColumn('offer_percentage');

            \DB::statement("ALTER TABLE offers MODIFY COLUMN offer_type ENUM('category', 'Group', 'sales_volume') NOT NULL");
        });
    }
};
