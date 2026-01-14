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
        Schema::create('prescription_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('department', ['General', 'Eye', 'Dental', 'ENT', 'Cardiology', 'Orthopedics', 'Gynecology', 'Pediatrics', 'Dermatology', 'Neurology'])->default('General');
            $table->foreignId('doctor_id')->nullable()->constrained()->onDelete('cascade');
            $table->text('chief_complaints')->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('medicines')->nullable();
            $table->text('tests_advised')->nullable();
            $table->text('advice')->nullable();
            $table->boolean('is_global')->default(false); // Global templates for all doctors
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescription_templates');
    }
};
