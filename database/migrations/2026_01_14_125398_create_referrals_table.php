<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->enum('type', ['Doctor', 'Agent', 'Patient', 'Staff', 'Other'])->default('Agent');
            $table->foreignId('doctor_id')->nullable()->constrained()->nullOnDelete();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            $table->enum('discount_type', ['Fixed', 'Percentage'])->default('Percentage');
            $table->decimal('discount_value', 10, 2)->default(0);

            $table->enum('commission_type', ['Fixed', 'Percentage'])->default('Percentage');
            $table->decimal('commission_value', 10, 2)->default(0);

            $table->integer('total_referrals')->default(0);
            $table->decimal('total_revenue', 12, 2)->default(0);
            $table->decimal('total_commission_earned', 12, 2)->default(0);
            $table->decimal('total_commission_paid', 12, 2)->default(0);

            $table->boolean('is_active')->default(true);
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
