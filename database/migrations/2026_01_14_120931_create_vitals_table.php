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
        Schema::create('vitals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('patient_queue_id')->nullable()->constrained()->onDelete('set null');
            $table->date('date');
            $table->string('blood_pressure')->nullable(); // e.g., "120/80"
            $table->integer('pulse')->nullable(); // bpm
            $table->decimal('temperature', 4, 1)->nullable(); // °F
            $table->integer('respiratory_rate')->nullable(); // per min
            $table->decimal('oxygen_saturation', 4, 1)->nullable(); // SpO2 %
            $table->decimal('weight', 5, 2)->nullable(); // kg
            $table->decimal('height', 5, 2)->nullable(); // cm
            $table->decimal('bmi', 4, 1)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vitals');
    }
};
