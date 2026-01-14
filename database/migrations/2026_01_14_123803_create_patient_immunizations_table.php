<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_immunizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->string('vaccine_name');
            $table->string('vaccine_type')->nullable(); // Live, Inactivated, mRNA, etc.
            $table->date('administered_date');
            $table->date('next_dose_date')->nullable();
            $table->integer('dose_number')->default(1);
            $table->string('batch_number')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('site_of_injection')->nullable(); // Left arm, Right arm, etc.
            $table->text('adverse_reactions')->nullable();
            $table->foreignId('administered_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'vaccine_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_immunizations');
    }
};
