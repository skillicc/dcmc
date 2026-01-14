<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_current_medications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->string('medicine_name');
            $table->string('generic_name')->nullable();
            $table->string('dosage');
            $table->string('frequency');
            $table->string('route')->nullable(); // Oral, IV, IM, etc.
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('prescribed_for')->nullable(); // Condition/reason
            $table->foreignId('prescribed_by')->nullable()->constrained('doctors')->onDelete('set null');
            $table->enum('status', ['Active', 'Discontinued', 'Completed'])->default('Active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_current_medications');
    }
};
