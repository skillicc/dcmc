<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->foreignId('category_id')->constrained('test_categories')->onDelete('cascade');
            $table->decimal('price', 10, 2);
            $table->string('duration')->nullable();
            $table->string('sample_type')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->text('normal_range')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tests');
    }
};
