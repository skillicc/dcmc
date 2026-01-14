<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->decimal('consultation_fee', 10, 2)->default(0)->after('commission_percentage');
            $table->decimal('follow_up_fee', 10, 2)->default(0)->after('consultation_fee');
            $table->integer('follow_up_days')->default(7)->after('follow_up_fee');
        });
    }

    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->dropColumn(['consultation_fee', 'follow_up_fee', 'follow_up_days']);
        });
    }
};
