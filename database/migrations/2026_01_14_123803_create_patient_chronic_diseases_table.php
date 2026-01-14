<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_chronic_diseases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->string('disease_name');
            $table->string('icd_code')->nullable(); // ICD-10 code
            $table->date('diagnosed_date')->nullable();
            $table->enum('severity', ['Mild', 'Moderate', 'Severe', 'Critical'])->default('Moderate');
            $table->enum('status', ['Active', 'Controlled', 'In Remission', 'Resolved'])->default('Active');
            $table->text('current_treatment')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('show_alert')->default(true); // Show alert on patient profile
            $table->foreignId('diagnosed_by')->nullable()->constrained('doctors')->onDelete('set null');
            $table->timestamps();

            $table->index(['patient_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_chronic_diseases');
    }
};
