<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_ledgers', function (Blueprint $table) {
            $table->id();
            $table->enum('entity_type', ['Doctor', 'Referral']);
            $table->unsignedBigInteger('entity_id');
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('type', ['Earned', 'Paid', 'Adjustment'])->default('Earned');
            $table->decimal('amount', 10, 2);
            $table->decimal('balance_after', 12, 2)->default(0);

            $table->string('description')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();

            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->index(['type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_ledgers');
    }
};
