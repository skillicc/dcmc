<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\Specialization;
use App\Models\TestCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Admin User
        User::create([
            'name' => 'Admin',
            'email' => 'admin@dcms.com',
            'phone' => '01700000000',
            'role' => 'Admin',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        // Create Specializations
        $specializations = [
            'General Medicine',
            'Cardiology',
            'Neurology',
            'Orthopedics',
            'Pediatrics',
            'Gynecology',
            'Dermatology',
            'ENT',
            'Ophthalmology',
            'Radiology',
        ];

        foreach ($specializations as $name) {
            Specialization::create(['name' => $name]);
        }

        // Create Test Categories
        $categories = [
            ['name' => 'Hematology', 'description' => 'Blood related tests'],
            ['name' => 'Biochemistry', 'description' => 'Chemical analysis of body fluids'],
            ['name' => 'Microbiology', 'description' => 'Detection of microorganisms'],
            ['name' => 'Serology', 'description' => 'Immunological tests'],
            ['name' => 'Urine Analysis', 'description' => 'Urine examination tests'],
            ['name' => 'Radiology', 'description' => 'X-Ray and imaging tests'],
            ['name' => 'Pathology', 'description' => 'Tissue examination'],
            ['name' => 'Cardiology', 'description' => 'Heart related tests'],
        ];

        foreach ($categories as $category) {
            TestCategory::create($category);
        }

        // Create Default Settings
        $settings = [
            ['key' => 'clinic_name', 'value' => 'DCMS Diagnostic Centre'],
            ['key' => 'clinic_address', 'value' => 'Dhaka, Bangladesh'],
            ['key' => 'clinic_phone', 'value' => '+880 1700-000000'],
            ['key' => 'clinic_email', 'value' => 'info@dcms.com'],
            ['key' => 'currency', 'value' => 'BDT'],
            ['key' => 'currency_symbol', 'value' => '৳'],
        ];

        foreach ($settings as $setting) {
            Setting::create($setting);
        }
    }
}
