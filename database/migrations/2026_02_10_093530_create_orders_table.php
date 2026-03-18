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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('visit_id')->constrained('store_visits')->onDelete('cascade');

            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('offer_discount', 10, 2)->default(0);
            $table->decimal('promocode_discount', 10, 2)->default(0);
            $table->decimal('taxable_amount', 10, 2)->default(0);
            $table->decimal('cgst', 10, 2)->default(0);
            $table->decimal('sgst', 10, 2)->default(0);
            $table->decimal('igst', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);

            $table->foreignId('offer_id')->nullable()->constrained()->onDelete('set null');
            $table->string('promocode')->nullable();
            $table->decimal('promocode_discount_percentage', 5, 2)->nullable();

            $table->string('invoice_pdf_path')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'delivered', 'cancelled'])->default('pending');

            $table->text('notes')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'employee_id', 'status']);   
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
