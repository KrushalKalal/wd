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
        Schema::table('employees', function (Blueprint $table) {
            $table->string('promocode')->unique()->nullable()->after('designation');
            $table->decimal('promocode_discount_percentage', 5, 2)->default(0)->after('promocode');
            $table->boolean('promocode_active')->default(true)->after('promocode_discount_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['promocode', 'promocode_discount_percentage', 'promocode_active']);
        });
    }
};
