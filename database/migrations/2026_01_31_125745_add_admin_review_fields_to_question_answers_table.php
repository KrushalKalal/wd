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
        Schema::table('question_answers', function (Blueprint $table) {
            $table->enum('admin_status', ['pending', 'approved', 'rejected', 'needs_review'])
                ->default('pending')
                ->after('remark');

            $table->text('admin_remark')
                ->nullable()
                ->after('admin_status');

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->after('admin_remark');

            $table->timestamp('reviewed_at')
                ->nullable()
                ->after('reviewed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('question_answers', function (Blueprint $table) {
            $table->dropColumn([
                'admin_status',
                'admin_remark',
                'reviewed_by',
                'reviewed_at',
            ]);
        });
    }
};