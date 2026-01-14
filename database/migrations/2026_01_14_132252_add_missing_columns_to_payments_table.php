<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // First rename method to payment_method
        if (Schema::hasColumn('payments', 'method') && !Schema::hasColumn('payments', 'payment_method')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->renameColumn('method', 'payment_method');
            });
        }

        Schema::table('payments', function (Blueprint $table) {
            // Add payment_method if doesn't exist
            if (!Schema::hasColumn('payments', 'payment_method')) {
                $table->string('payment_method')->default('Cash')->after('amount');
            }

            // Add payment_reference
            if (!Schema::hasColumn('payments', 'payment_reference')) {
                $table->string('payment_reference')->nullable()->after('payment_method');
            }

            // Add received_by (cashier)
            if (!Schema::hasColumn('payments', 'received_by')) {
                $table->foreignId('received_by')->nullable()->after('payment_reference')->constrained('users')->nullOnDelete();
            }
        });

        // Change date column to datetime using raw SQL
        DB::statement('ALTER TABLE payments MODIFY COLUMN date DATETIME');
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'payment_reference')) {
                $table->dropColumn('payment_reference');
            }
            if (Schema::hasColumn('payments', 'received_by')) {
                $table->dropForeign(['received_by']);
                $table->dropColumn('received_by');
            }
        });

        // Change back to date
        DB::statement('ALTER TABLE payments MODIFY COLUMN date DATE');
    }
};
