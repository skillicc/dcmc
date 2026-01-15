<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Add referral_id first if not exists
            if (! Schema::hasColumn('invoices', 'referral_id')) {
                $table->foreignId('referral_id')->nullable()->after('doctor_id');
            }

            // Only add columns that don't exist
            if (! Schema::hasColumn('invoices', 'referral_discount')) {
                $table->decimal('referral_discount', 10, 2)->default(0)->after('referral_id');
            }

            if (! Schema::hasColumn('invoices', 'doctor_commission')) {
                $table->decimal('doctor_commission', 10, 2)->default(0)->after('referral_discount');
            }

            if (! Schema::hasColumn('invoices', 'commission_paid')) {
                $table->boolean('commission_paid')->default(false)->after('doctor_commission');
            }

            if (! Schema::hasColumn('invoices', 'commission_paid_at')) {
                $table->timestamp('commission_paid_at')->nullable()->after('commission_paid');
            }

            if (! Schema::hasColumn('invoices', 'tax_amount')) {
                $table->decimal('tax_amount', 10, 2)->default(0)->after('commission_paid_at');
            }

            if (! Schema::hasColumn('invoices', 'tax_percentage')) {
                $table->decimal('tax_percentage', 5, 2)->default(0)->after('tax_amount');
            }

            if (! Schema::hasColumn('invoices', 'last_payment_date')) {
                $table->timestamp('last_payment_date')->nullable()->after('due');
            }

            if (! Schema::hasColumn('invoices', 'due_date')) {
                $table->date('due_date')->nullable()->after('last_payment_date');
            }
        });

        // Add foreign key if not exists
        $foreignKeyExists = DB::select("
            SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'invoices'
            AND CONSTRAINT_NAME = 'invoices_referral_id_foreign'
        ");

        if (empty($foreignKeyExists)) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->foreign('referral_id')->references('id')->on('referrals')->nullOnDelete();
            });
        }

        // Add indexes if not exist
        $indexes = collect(DB::select("SHOW INDEX FROM invoices"))->pluck('Key_name')->unique()->toArray();

        if (! in_array('invoices_status_due_date_index', $indexes)) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->index(['status', 'due_date']);
            });
        }

        if (! in_array('invoices_commission_paid_doctor_id_index', $indexes)) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->index(['commission_paid', 'doctor_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['status', 'due_date']);
            $table->dropIndex(['commission_paid', 'doctor_id']);

            $table->dropForeign(['referral_id']);
            $table->dropColumn([
                'referral_discount',
                'doctor_commission',
                'commission_paid',
                'commission_paid_at',
                'tax_amount',
                'tax_percentage',
                'last_payment_date',
                'due_date',
            ]);
        });
    }
};
