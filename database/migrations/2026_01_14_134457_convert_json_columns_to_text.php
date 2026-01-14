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
        // Prescriptions table
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->text('vitals')->nullable()->change();
            $table->text('medicines')->nullable()->change();
            $table->text('tests_advised')->nullable()->change();
        });

        // Prescription templates table
        Schema::table('prescription_templates', function (Blueprint $table) {
            $table->text('medicines')->nullable()->change();
            $table->text('tests_advised')->nullable()->change();
        });

        // Lab reports table
        Schema::table('lab_reports', function (Blueprint $table) {
            $table->text('parameters')->nullable()->change();
        });

        // Invoices table
        Schema::table('invoices', function (Blueprint $table) {
            $table->text('items')->change();
        });

        // Patient clinical data table
        Schema::table('patient_clinical_data', function (Blueprint $table) {
            $table->text('data')->nullable()->change();
        });

        // Tests table
        Schema::table('tests', function (Blueprint $table) {
            $table->text('parameters')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Prescriptions table
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->json('vitals')->nullable()->change();
            $table->json('medicines')->nullable()->change();
            $table->json('tests_advised')->nullable()->change();
        });

        // Prescription templates table
        Schema::table('prescription_templates', function (Blueprint $table) {
            $table->json('medicines')->nullable()->change();
            $table->json('tests_advised')->nullable()->change();
        });

        // Lab reports table
        Schema::table('lab_reports', function (Blueprint $table) {
            $table->json('parameters')->nullable()->change();
        });

        // Invoices table
        Schema::table('invoices', function (Blueprint $table) {
            $table->json('items')->change();
        });

        // Patient clinical data table
        Schema::table('patient_clinical_data', function (Blueprint $table) {
            $table->json('data')->nullable()->change();
        });

        // Tests table
        Schema::table('tests', function (Blueprint $table) {
            $table->json('parameters')->nullable()->change();
        });
    }
};
