<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_clinical_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('doctor_id')->nullable()->constrained()->onDelete('set null');
            $table->date('record_date');
            $table->string('category'); // Diagnosis, Symptom, Observation, Lab Finding, etc.
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('data')->nullable(); // Structured clinical data
            $table->enum('severity', ['Mild', 'Moderate', 'Severe'])->nullable();
            $table->enum('status', ['Active', 'Resolved', 'Chronic', 'Under Treatment'])->default('Active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['patient_id', 'category']);
            $table->index(['patient_id', 'record_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_clinical_data');
    }
};
