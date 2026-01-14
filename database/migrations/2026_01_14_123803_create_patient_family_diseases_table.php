<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_family_diseases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->string('disease_name');
            $table->string('relationship'); // Father, Mother, Sibling, Grandparent, etc.
            $table->string('relative_name')->nullable();
            $table->integer('age_at_diagnosis')->nullable();
            $table->enum('status', ['Living', 'Deceased'])->nullable();
            $table->integer('age_at_death')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'disease_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_family_diseases');
    }
};
