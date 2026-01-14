<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create invoice_items table
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->string('item_type')->default('test'); // test, consultation, service, product
            $table->unsignedBigInteger('item_id')->nullable(); // Reference to test_id or other
            $table->string('name');
            $table->string('description')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->timestamps();
        });

        // 2. Create prescription_medicines table
        Schema::create('prescription_medicines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('dosage')->nullable();
            $table->string('frequency')->nullable();
            $table->string('duration')->nullable();
            $table->string('instructions')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 3. Create prescription_tests table
        Schema::create('prescription_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained()->onDelete('cascade');
            $table->foreignId('test_id')->nullable()->constrained()->onDelete('set null');
            $table->string('test_name'); // Store name in case test is deleted
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 4. Add vitals columns to prescriptions table
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->string('vitals_bp')->nullable()->after('date');
            $table->string('vitals_pulse')->nullable()->after('vitals_bp');
            $table->string('vitals_temp')->nullable()->after('vitals_pulse');
            $table->string('vitals_weight')->nullable()->after('vitals_temp');
            $table->string('vitals_height')->nullable()->after('vitals_weight');
            $table->string('vitals_spo2')->nullable()->after('vitals_height');
            $table->string('vitals_rbs')->nullable()->after('vitals_spo2');
            $table->string('vitals_respiratory_rate')->nullable()->after('vitals_rbs');
        });

        // 5. Create lab_report_parameters table
        Schema::create('lab_report_parameters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_report_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('value')->nullable();
            $table->string('unit')->nullable();
            $table->string('normal_range')->nullable();
            $table->enum('status', ['Normal', 'Low', 'High', 'Critical'])->default('Normal');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 6. Create test_parameters table
        Schema::create('test_parameters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('unit')->nullable();
            $table->string('normal_range_text')->nullable();
            $table->decimal('normal_range_min', 10, 2)->nullable();
            $table->decimal('normal_range_max', 10, 2)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 7. Create prescription_template_medicines table
        Schema::create('prescription_template_medicines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_template_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('dosage')->nullable();
            $table->string('frequency')->nullable();
            $table->string('duration')->nullable();
            $table->string('instructions')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 8. Create prescription_template_tests table
        Schema::create('prescription_template_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_template_id')->constrained()->onDelete('cascade');
            $table->string('test_name');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Migrate existing data
        $this->migrateExistingData();

        // Drop old JSON/TEXT columns
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('items');
        });

        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropColumn(['vitals', 'medicines', 'tests_advised']);
        });

        Schema::table('lab_reports', function (Blueprint $table) {
            $table->dropColumn('parameters');
        });

        Schema::table('tests', function (Blueprint $table) {
            $table->dropColumn('parameters');
        });

        Schema::table('prescription_templates', function (Blueprint $table) {
            $table->dropColumn(['medicines', 'tests_advised']);
        });

        Schema::table('patient_clinical_data', function (Blueprint $table) {
            $table->dropColumn('data');
        });
    }

    private function migrateExistingData(): void
    {
        // Migrate invoice items
        $invoices = DB::table('invoices')->whereNotNull('items')->get();
        foreach ($invoices as $invoice) {
            $items = json_decode($invoice->items, true);
            if (is_array($items)) {
                foreach ($items as $index => $item) {
                    DB::table('invoice_items')->insert([
                        'invoice_id' => $invoice->id,
                        'item_type' => $item['type'] ?? 'test',
                        'item_id' => $item['test_id'] ?? $item['id'] ?? null,
                        'name' => $item['name'] ?? $item['test_name'] ?? 'Unknown',
                        'description' => $item['description'] ?? null,
                        'quantity' => $item['quantity'] ?? 1,
                        'unit_price' => $item['price'] ?? $item['unit_price'] ?? 0,
                        'discount' => $item['discount'] ?? 0,
                        'total' => $item['total'] ?? ($item['price'] ?? 0) * ($item['quantity'] ?? 1),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // Migrate prescription data
        $prescriptions = DB::table('prescriptions')->get();
        foreach ($prescriptions as $prescription) {
            // Migrate vitals
            $vitals = json_decode($prescription->vitals, true);
            if (is_array($vitals)) {
                DB::table('prescriptions')->where('id', $prescription->id)->update([
                    'vitals_bp' => $vitals['bp'] ?? null,
                    'vitals_pulse' => $vitals['pulse'] ?? null,
                    'vitals_temp' => $vitals['temp'] ?? null,
                    'vitals_weight' => $vitals['weight'] ?? null,
                    'vitals_height' => $vitals['height'] ?? null,
                    'vitals_spo2' => $vitals['spo2'] ?? null,
                    'vitals_rbs' => $vitals['rbs'] ?? null,
                    'vitals_respiratory_rate' => $vitals['respiratory_rate'] ?? null,
                ]);
            }

            // Migrate medicines
            $medicines = json_decode($prescription->medicines, true);
            if (is_array($medicines)) {
                foreach ($medicines as $index => $med) {
                    DB::table('prescription_medicines')->insert([
                        'prescription_id' => $prescription->id,
                        'name' => $med['name'] ?? 'Unknown',
                        'dosage' => $med['dosage'] ?? null,
                        'frequency' => $med['frequency'] ?? null,
                        'duration' => $med['duration'] ?? null,
                        'instructions' => $med['instructions'] ?? null,
                        'sort_order' => $index,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Migrate tests advised
            $tests = json_decode($prescription->tests_advised, true);
            if (is_array($tests)) {
                foreach ($tests as $test) {
                    $testId = is_numeric($test) ? $test : ($test['id'] ?? null);
                    $testName = is_numeric($test)
                        ? (DB::table('tests')->find($testId)?->name ?? 'Unknown')
                        : ($test['name'] ?? 'Unknown');

                    DB::table('prescription_tests')->insert([
                        'prescription_id' => $prescription->id,
                        'test_id' => $testId,
                        'test_name' => $testName,
                        'notes' => is_array($test) ? ($test['notes'] ?? null) : null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // Migrate lab report parameters
        $labReports = DB::table('lab_reports')->whereNotNull('parameters')->get();
        foreach ($labReports as $report) {
            $parameters = json_decode($report->parameters, true);
            if (is_array($parameters)) {
                foreach ($parameters as $index => $param) {
                    DB::table('lab_report_parameters')->insert([
                        'lab_report_id' => $report->id,
                        'name' => $param['name'] ?? 'Unknown',
                        'value' => $param['value'] ?? null,
                        'unit' => $param['unit'] ?? null,
                        'normal_range' => $param['normal_range'] ?? $param['normal_range_text'] ?? null,
                        'status' => $param['status'] ?? 'Normal',
                        'sort_order' => $index,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // Migrate test parameters
        $tests = DB::table('tests')->whereNotNull('parameters')->get();
        foreach ($tests as $test) {
            $parameters = json_decode($test->parameters, true);
            if (is_array($parameters)) {
                foreach ($parameters as $index => $param) {
                    DB::table('test_parameters')->insert([
                        'test_id' => $test->id,
                        'name' => $param['name'] ?? 'Unknown',
                        'unit' => $param['unit'] ?? null,
                        'normal_range_text' => $param['normal_range_text'] ?? $param['normal_range'] ?? null,
                        'normal_range_min' => $param['normal_range_min'] ?? null,
                        'normal_range_max' => $param['normal_range_max'] ?? null,
                        'sort_order' => $index,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // Migrate prescription template medicines and tests
        $templates = DB::table('prescription_templates')->get();
        foreach ($templates as $template) {
            $medicines = json_decode($template->medicines, true);
            if (is_array($medicines)) {
                foreach ($medicines as $index => $med) {
                    DB::table('prescription_template_medicines')->insert([
                        'prescription_template_id' => $template->id,
                        'name' => $med['name'] ?? 'Unknown',
                        'dosage' => $med['dosage'] ?? null,
                        'frequency' => $med['frequency'] ?? null,
                        'duration' => $med['duration'] ?? null,
                        'instructions' => $med['instructions'] ?? null,
                        'sort_order' => $index,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            $tests = json_decode($template->tests_advised, true);
            if (is_array($tests)) {
                foreach ($tests as $index => $test) {
                    $testName = is_string($test) ? $test : ($test['name'] ?? 'Unknown');
                    DB::table('prescription_template_tests')->insert([
                        'prescription_template_id' => $template->id,
                        'test_name' => $testName,
                        'sort_order' => $index,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        // Add back JSON columns
        Schema::table('invoices', function (Blueprint $table) {
            $table->text('items')->nullable();
        });

        Schema::table('prescriptions', function (Blueprint $table) {
            $table->text('vitals')->nullable();
            $table->text('medicines')->nullable();
            $table->text('tests_advised')->nullable();
        });

        Schema::table('lab_reports', function (Blueprint $table) {
            $table->text('parameters')->nullable();
        });

        Schema::table('tests', function (Blueprint $table) {
            $table->text('parameters')->nullable();
        });

        Schema::table('prescription_templates', function (Blueprint $table) {
            $table->text('medicines')->nullable();
            $table->text('tests_advised')->nullable();
        });

        Schema::table('patient_clinical_data', function (Blueprint $table) {
            $table->text('data')->nullable();
        });

        // Drop vitals columns from prescriptions
        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropColumn([
                'vitals_bp', 'vitals_pulse', 'vitals_temp',
                'vitals_weight', 'vitals_height', 'vitals_spo2',
                'vitals_rbs', 'vitals_respiratory_rate'
            ]);
        });

        // Drop new tables
        Schema::dropIfExists('prescription_template_tests');
        Schema::dropIfExists('prescription_template_medicines');
        Schema::dropIfExists('test_parameters');
        Schema::dropIfExists('lab_report_parameters');
        Schema::dropIfExists('prescription_tests');
        Schema::dropIfExists('prescription_medicines');
        Schema::dropIfExists('invoice_items');
    }
};
