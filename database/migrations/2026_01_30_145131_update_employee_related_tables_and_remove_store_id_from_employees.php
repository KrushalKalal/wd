<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ── 1. Update employees table ───────────────────────────────────────
        if (Schema::hasTable('employees')) {
            Schema::table('employees', function (Blueprint $table) {
                if (!Schema::hasColumn('employees', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('id');
                }

                if (!Schema::hasColumn('employees', 'employee_image')) {
                    $table->string('employee_image')->nullable()->after('aadhar_image');
                }
            });
        }

        // ── 7. Remove store_id from employees (should run after assignments table exists) ──
        if (Schema::hasTable('employees') && Schema::hasColumn('employees', 'store_id')) {
            Schema::table('employees', function (Blueprint $table) {
                // Drop foreign key first (name may vary — adjust if your FK name is different)
                $table->dropForeign(['store_id']);           // ← may throw if name is different
                $table->dropColumn('store_id');
            });
        }

        // ── 2. Create employee_targets table ────────────────────────────────
        if (!Schema::hasTable('employee_targets')) {
            Schema::create('employee_targets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
                $table->integer('month');               // 1–12
                $table->integer('year');
                $table->integer('visit_target')->default(0);
                $table->integer('visits_completed')->default(0);
                $table->decimal('sales_target', 12, 2)->default(0);
                $table->decimal('sales_achieved', 12, 2)->default(0);
                $table->enum('status', ['pending', 'in_progress', 'achieved', 'missed'])->default('pending');
                $table->timestamps();

                $table->unique(['employee_id', 'month', 'year']);
            });
        }

        // ── 3. Create employee_store_assignments table ──────────────────────
        if (!Schema::hasTable('employee_store_assignments')) {
            Schema::create('employee_store_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
                $table->foreignId('store_id')->constrained()->cascadeOnDelete();
                $table->date('assigned_date');
                $table->date('removed_date')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // ── 4. Update store_products ────────────────────────────────────────
        if (Schema::hasTable('store_products')) {
            Schema::table('store_products', function (Blueprint $table) {
                if (!Schema::hasColumn('store_products', 'pending_stock')) {
                    $table->integer('pending_stock')->default(0)->after('current_stock');
                }

                if (!Schema::hasColumn('store_products', 'return_stock')) {
                    $table->integer('return_stock')->default(0)->after('pending_stock');
                }
            });
        }

        // ── 5. Update stock_transactions ────────────────────────────────────
        if (Schema::hasTable('stock_transactions')) {
            Schema::table('stock_transactions', function (Blueprint $table) {
                if (!Schema::hasColumn('stock_transactions', 'status')) {
                    $table->enum('status', [
                        'pending',
                        'approved',
                        'delivered',
                        'returned',
                        'rejected'
                    ])->default('pending')->after('quantity');
                }

                if (!Schema::hasColumn('stock_transactions', 'admin_remark')) {
                    $table->text('admin_remark')->nullable()->after('remark');
                }

                if (!Schema::hasColumn('stock_transactions', 'approved_by')) {
                    $table->foreignId('approved_by')->nullable()
                        ->constrained('users')
                        ->nullOnDelete()
                        ->after('admin_remark');
                }

                if (!Schema::hasColumn('stock_transactions', 'approved_at')) {
                    $table->timestamp('approved_at')->nullable()->after('approved_by');
                }
            });
        }

        // ── 6. Update store_visits ──────────────────────────────────────────
        if (Schema::hasTable('store_visits')) {
            Schema::table('store_visits', function (Blueprint $table) {
                if (!Schema::hasColumn('store_visits', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('id');
                }

                if (!Schema::hasColumn('store_visits', 'status')) {
                    $table->enum('status', ['checked_in', 'completed', 'cancelled'])
                        ->default('checked_in')
                        ->after('longitude');
                }

                if (!Schema::hasColumn('store_visits', 'visit_summary')) {
                    $table->text('visit_summary')->nullable()->after('status');
                }
            });
        }
    }

    public function down(): void
    {
        // Reverse order — most dependent first

        // 6. store_visits
        if (Schema::hasTable('store_visits')) {
            Schema::table('store_visits', function (Blueprint $table) {
                $columns = [];
                if (Schema::hasColumn('store_visits', 'visit_summary'))
                    $columns[] = 'visit_summary';
                if (Schema::hasColumn('store_visits', 'status'))
                    $columns[] = 'status';
                if (Schema::hasColumn('store_visits', 'is_active'))
                    $columns[] = 'is_active';
                if (!empty($columns))
                    $table->dropColumn($columns);
            });
        }

        // 5. stock_transactions
        if (Schema::hasTable('stock_transactions')) {
            Schema::table('stock_transactions', function (Blueprint $table) {
                $columns = [];
                if (Schema::hasColumn('stock_transactions', 'approved_at'))
                    $columns[] = 'approved_at';
                if (Schema::hasColumn('stock_transactions', 'approved_by'))
                    $columns[] = 'approved_by';
                if (Schema::hasColumn('stock_transactions', 'admin_remark'))
                    $columns[] = 'admin_remark';
                if (Schema::hasColumn('stock_transactions', 'status'))
                    $columns[] = 'status';
                if (!empty($columns))
                    $table->dropColumn($columns);
            });
        }

        // 4. store_products
        if (Schema::hasTable('store_products')) {
            Schema::table('store_products', function (Blueprint $table) {
                $columns = [];
                if (Schema::hasColumn('store_products', 'return_stock'))
                    $columns[] = 'return_stock';
                if (Schema::hasColumn('store_products', 'pending_stock'))
                    $columns[] = 'pending_stock';
                if (!empty($columns))
                    $table->dropColumn($columns);
            });
        }

        // 3. Drop employee_store_assignments
        Schema::dropIfExists('employee_store_assignments');

        // 2. Drop employee_targets
        Schema::dropIfExists('employee_targets');

        // 1 + 7. Restore employees columns (add back store_id + drop added columns)
        if (Schema::hasTable('employees')) {
            Schema::table('employees', function (Blueprint $table) {
                // Re-add store_id (down migration)
                if (!Schema::hasColumn('employees', 'store_id')) {
                    $table->foreignId('store_id')->nullable()->constrained()->nullOnDelete();
                }

                $columnsToDrop = [];
                if (Schema::hasColumn('employees', 'employee_image'))
                    $columnsToDrop[] = 'employee_image';
                if (Schema::hasColumn('employees', 'is_active'))
                    $columnsToDrop[] = 'is_active';
                if (!empty($columnsToDrop))
                    $table->dropColumn($columnsToDrop);
            });
        }
    }
};