<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * List of tables that should get is_active column
     */
    private array $tablesWithIsActive = [
        'states',
        'cities',
        'areas',
        'departments',
        'category_one',
        'category_two',
        'category_three',
        'product_categories',
        'companies',
        'branches',
        'stores',
        'products',
    ];

    public function up(): void
    {
        // 1. Add is_active to all listed tables (only if column doesn't exist)
        foreach ($this->tablesWithIsActive as $tableName) {
            if (!Schema::hasColumn($tableName, 'is_active')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->boolean('is_active')
                        ->default(true)
                        ->after('id');
                });
            }
        }

        // 2. Add address to stores
        if (Schema::hasTable('stores') && !Schema::hasColumn('stores', 'address')) {
            Schema::table('stores', function (Blueprint $table) {
                $table->text('address')
                    ->nullable()
                    ->after('name');
            });
        }

        // 3. Add catalogue_pdf to products
        if (Schema::hasTable('products') && !Schema::hasColumn('products', 'catalogue_pdf')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('catalogue_pdf')
                    ->nullable()
                    ->after('total_stock');
            });
        }
    }

    public function down(): void
    {
        // Reverse in opposite order (though order usually doesn't matter for drops)

        if (Schema::hasColumn('products', 'catalogue_pdf')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('catalogue_pdf');
            });
        }

        if (Schema::hasColumn('stores', 'address')) {
            Schema::table('stores', function (Blueprint $table) {
                $table->dropColumn('address');
            });
        }

        foreach ($this->tablesWithIsActive as $tableName) {
            if (Schema::hasColumn($tableName, 'is_active')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('is_active');
                });
            }
        }
    }
};