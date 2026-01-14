<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lab_reports', function (Blueprint $table) {
            // Specimen Collection
            $table->string('specimen_type')->nullable()->after('test_id');
            $table->string('specimen_id')->nullable()->after('specimen_type');
            $table->timestamp('specimen_collected_at')->nullable()->after('sample_date');
            $table->foreignId('collected_by')->nullable()->constrained('users')->onDelete('set null');

            // Lab Reception
            $table->timestamp('received_at_lab')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->onDelete('set null');

            // Result Entry
            $table->timestamp('result_entered_at')->nullable();
            $table->foreignId('result_entered_by')->nullable()->constrained('users')->onDelete('set null');

            // Result Approval
            $table->enum('approval_status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('approval_remarks')->nullable();

            // Criticality
            $table->enum('criticality', ['Normal', 'Abnormal', 'Critical'])->default('Normal');
            $table->boolean('is_critical_notified')->default(false);

            // SMS Notification
            $table->boolean('sms_sent')->default(false);
            $table->timestamp('sms_sent_at')->nullable();

            // Barcode
            $table->string('barcode')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('lab_reports', function (Blueprint $table) {
            $table->dropForeign(['collected_by']);
            $table->dropForeign(['received_by']);
            $table->dropForeign(['result_entered_by']);
            $table->dropForeign(['approved_by']);

            $table->dropColumn([
                'specimen_type',
                'specimen_id',
                'specimen_collected_at',
                'collected_by',
                'received_at_lab',
                'received_by',
                'result_entered_at',
                'result_entered_by',
                'approval_status',
                'approved_at',
                'approved_by',
                'approval_remarks',
                'criticality',
                'is_critical_notified',
                'sms_sent',
                'sms_sent_at',
                'barcode',
            ]);
        });
    }
};
