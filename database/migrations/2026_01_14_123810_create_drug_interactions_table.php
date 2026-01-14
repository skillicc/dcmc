<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drug_interactions', function (Blueprint $table) {
            $table->id();
            $table->string('drug1_name');
            $table->string('drug2_name');
            $table->enum('severity', ['Minor', 'Moderate', 'Major', 'Contraindicated'])->default('Moderate');
            $table->text('description');
            $table->text('mechanism')->nullable();
            $table->text('management')->nullable(); // How to manage the interaction
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['drug1_name', 'drug2_name']);
            $table->index('severity');
        });

        // Disease-Drug contraindications
        Schema::create('disease_drug_interactions', function (Blueprint $table) {
            $table->id();
            $table->string('disease_name');
            $table->string('drug_name');
            $table->enum('severity', ['Caution', 'Avoid', 'Contraindicated'])->default('Caution');
            $table->text('description');
            $table->text('alternative')->nullable(); // Alternative drug suggestion
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['disease_name', 'drug_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disease_drug_interactions');
        Schema::dropIfExists('drug_interactions');
    }
};
