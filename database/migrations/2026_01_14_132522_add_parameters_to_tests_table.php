<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            if (!Schema::hasColumn('tests', 'parameters')) {
                // Parameters for lab result entry (JSON array of parameter objects)
                // Each parameter: {name, unit, normal_range_min, normal_range_max, normal_range_text}
                $table->text('parameters')->nullable()->after('normal_range');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tests', function (Blueprint $table) {
            if (Schema::hasColumn('tests', 'parameters')) {
                $table->dropColumn('parameters');
            }
        });
    }
};
