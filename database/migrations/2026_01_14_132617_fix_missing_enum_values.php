<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix lab_reports.status - add 'Received at Lab' and 'Delivered'
        DB::statement("ALTER TABLE lab_reports MODIFY COLUMN status ENUM('Pending', 'Sample Collected', 'Received at Lab', 'Processing', 'Completed', 'Delivered') NOT NULL DEFAULT 'Pending'");

        // Fix patient_clinical_data.severity - add 'Critical'
        DB::statement("ALTER TABLE patient_clinical_data MODIFY COLUMN severity ENUM('Mild', 'Moderate', 'Severe', 'Critical') NULL");

        // Fix patient_clinical_data.status - add 'Monitoring'
        DB::statement("ALTER TABLE patient_clinical_data MODIFY COLUMN status ENUM('Active', 'Resolved', 'Chronic', 'Under Treatment', 'Monitoring') NOT NULL DEFAULT 'Active'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert lab_reports.status
        DB::statement("ALTER TABLE lab_reports MODIFY COLUMN status ENUM('Pending', 'Sample Collected', 'Processing', 'Completed') NOT NULL DEFAULT 'Pending'");

        // Revert patient_clinical_data.severity
        DB::statement("ALTER TABLE patient_clinical_data MODIFY COLUMN severity ENUM('Mild', 'Moderate', 'Severe') NULL");

        // Revert patient_clinical_data.status
        DB::statement("ALTER TABLE patient_clinical_data MODIFY COLUMN status ENUM('Active', 'Resolved', 'Chronic', 'Under Treatment') NOT NULL DEFAULT 'Active'");
    }
};
