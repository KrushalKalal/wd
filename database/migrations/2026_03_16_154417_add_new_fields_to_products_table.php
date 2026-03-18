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
        Schema::table('products', function (Blueprint $table) {
            $table->string('sku')->nullable()->unique()->after('name');
            $table->integer('pack_size')->nullable()->after('sku');
            $table->integer('volume')->nullable()->after('pack_size');
            $table->unsignedBigInteger('state_id')->nullable()->after('volume');
            $table->string('image')->nullable()->after('state_id');

            $table->foreign('state_id')->references('id')->on('states')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['state_id']);
            $table->dropColumn(['sku', 'pack_size', 'volume', 'state_id', 'image']);
        });
    }
};
