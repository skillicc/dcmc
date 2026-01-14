<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\DoctorScheduleController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\LabReportController;
use App\Http\Controllers\Api\MedicineFavoriteController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\PatientEmrController;
use App\Http\Controllers\Api\PatientQueueController;
use App\Http\Controllers\Api\PrescriptionController;
use App\Http\Controllers\Api\PrescriptionTemplateController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\SpecializationController;
use App\Http\Controllers\Api\TestCategoryController;
use App\Http\Controllers\Api\TestController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\VitalController;
use Illuminate\Support\Facades\Route;

// Auth routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Patients
    Route::apiResource('patients', PatientController::class);

    // Doctors
    Route::apiResource('doctors', DoctorController::class);

    // Specializations
    Route::apiResource('specializations', SpecializationController::class)->except(['show']);

    // Test Categories
    Route::apiResource('test-categories', TestCategoryController::class)->except(['show']);

    // Tests
    Route::apiResource('tests', TestController::class);

    // Appointments
    Route::patch('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus']);
    Route::apiResource('appointments', AppointmentController::class);

    // Prescriptions
    Route::apiResource('prescriptions', PrescriptionController::class)->except(['update']);

    // Lab Reports - LIS (Laboratory Information System)
    Route::get('/lab-reports/stats', [LabReportController::class, 'stats']);
    Route::get('/lab-reports/specimen-types', [LabReportController::class, 'specimenTypes']);
    Route::post('/lab-reports/bulk-receive', [LabReportController::class, 'bulkReceive']);
    Route::post('/lab-reports/{labReport}/collect-specimen', [LabReportController::class, 'collectSpecimen']);
    Route::post('/lab-reports/{labReport}/receive-at-lab', [LabReportController::class, 'receiveAtLab']);
    Route::post('/lab-reports/{labReport}/enter-result', [LabReportController::class, 'enterResult']);
    Route::post('/lab-reports/{labReport}/approve', [LabReportController::class, 'approve']);
    Route::post('/lab-reports/{labReport}/reject', [LabReportController::class, 'reject']);
    Route::post('/lab-reports/{labReport}/deliver', [LabReportController::class, 'markDelivered']);
    Route::post('/lab-reports/{labReport}/send-sms', [LabReportController::class, 'sendSms']);
    Route::apiResource('lab-reports', LabReportController::class);

    // Invoices
    Route::post('/invoices/{invoice}/payments', [InvoiceController::class, 'addPayment']);
    Route::apiResource('invoices', InvoiceController::class)->except(['update']);

    // Users
    Route::apiResource('users', UserController::class);

    // Settings
    Route::get('/settings', [SettingController::class, 'index']);
    Route::put('/settings', [SettingController::class, 'update']);

    // Reports
    Route::get('/reports/financial', [ReportController::class, 'financial']);
    Route::get('/reports/bill-collection', [ReportController::class, 'billCollection']);
    Route::get('/reports/cashier-wise', [ReportController::class, 'cashierWise']);
    Route::get('/reports/date-wise-cashier', [ReportController::class, 'dateWiseCashier']);
    Route::get('/reports/performance', [ReportController::class, 'performance']);
    Route::get('/reports/patient-due', [ReportController::class, 'patientDue']);
    Route::get('/reports/daily-sales', [ReportController::class, 'dailySales']);

    // Doctor Schedules
    Route::get('/doctors/{doctor}/schedules', [DoctorScheduleController::class, 'doctorSchedules']);
    Route::post('/doctors/{doctor}/schedules/bulk', [DoctorScheduleController::class, 'bulkStore']);
    Route::apiResource('doctor-schedules', DoctorScheduleController::class);

    // Patient Queue
    Route::get('/queue/stats', [PatientQueueController::class, 'todayStats']);
    Route::post('/queue/call-next', [PatientQueueController::class, 'callNext']);
    Route::patch('/queue/{patientQueue}/status', [PatientQueueController::class, 'updateStatus']);
    Route::apiResource('queue', PatientQueueController::class)->parameters(['queue' => 'patientQueue']);

    // Vitals
    Route::get('/patients/{patient}/vitals', [VitalController::class, 'patientHistory']);
    Route::apiResource('vitals', VitalController::class);

    // Patient EMR (Electronic Medical Records)
    Route::prefix('patients/{patient}/emr')->group(function () {
        Route::get('/', [PatientEmrController::class, 'history']);
        Route::get('/appointments', [PatientEmrController::class, 'appointments']);
        Route::get('/prescriptions', [PatientEmrController::class, 'prescriptions']);
        Route::get('/lab-reports', [PatientEmrController::class, 'labReports']);
        Route::get('/invoices', [PatientEmrController::class, 'invoices']);
        Route::get('/vitals', [PatientEmrController::class, 'vitals']);
        Route::get('/timeline', [PatientEmrController::class, 'timeline']);

        // Clinical Profile
        Route::get('/clinical-profile', [PatientEmrController::class, 'clinicalProfile']);

        // Clinical Data
        Route::get('/clinical-data', [PatientEmrController::class, 'clinicalData']);
        Route::post('/clinical-data', [PatientEmrController::class, 'storeClinicalData']);
        Route::put('/clinical-data/{clinicalData}', [PatientEmrController::class, 'updateClinicalData']);
        Route::delete('/clinical-data/{clinicalData}', [PatientEmrController::class, 'destroyClinicalData']);

        // Chronic Diseases
        Route::get('/chronic-diseases', [PatientEmrController::class, 'chronicDiseases']);
        Route::post('/chronic-diseases', [PatientEmrController::class, 'storeChronicDisease']);
        Route::put('/chronic-diseases/{chronicDisease}', [PatientEmrController::class, 'updateChronicDisease']);
        Route::delete('/chronic-diseases/{chronicDisease}', [PatientEmrController::class, 'destroyChronicDisease']);

        // Immunizations
        Route::get('/immunizations', [PatientEmrController::class, 'immunizations']);
        Route::post('/immunizations', [PatientEmrController::class, 'storeImmunization']);
        Route::put('/immunizations/{immunization}', [PatientEmrController::class, 'updateImmunization']);
        Route::delete('/immunizations/{immunization}', [PatientEmrController::class, 'destroyImmunization']);

        // Family Diseases
        Route::get('/family-diseases', [PatientEmrController::class, 'familyDiseases']);
        Route::post('/family-diseases', [PatientEmrController::class, 'storeFamilyDisease']);
        Route::put('/family-diseases/{familyDisease}', [PatientEmrController::class, 'updateFamilyDisease']);
        Route::delete('/family-diseases/{familyDisease}', [PatientEmrController::class, 'destroyFamilyDisease']);

        // Current Medications
        Route::get('/medications', [PatientEmrController::class, 'currentMedications']);
        Route::post('/medications', [PatientEmrController::class, 'storeCurrentMedication']);
        Route::put('/medications/{medication}', [PatientEmrController::class, 'updateCurrentMedication']);
        Route::delete('/medications/{medication}', [PatientEmrController::class, 'destroyCurrentMedication']);

        // Drug Interaction Check
        Route::post('/check-drug-interactions', [PatientEmrController::class, 'checkDrugInteractions']);

        // Graphical Data / Trends
        Route::get('/vital-trends', [PatientEmrController::class, 'vitalTrends']);
        Route::get('/lab-trends', [PatientEmrController::class, 'labTrends']);
    });

    // EMR Master Data
    Route::get('/emr/clinical-data-categories', [PatientEmrController::class, 'clinicalDataCategories']);
    Route::get('/emr/common-chronic-diseases', [PatientEmrController::class, 'commonChronicDiseases']);
    Route::get('/emr/common-vaccines', [PatientEmrController::class, 'commonVaccines']);
    Route::get('/emr/family-disease-options', [PatientEmrController::class, 'familyDiseaseOptions']);
    Route::get('/emr/medication-options', [PatientEmrController::class, 'medicationOptions']);

    // Drug Interactions Master Data
    Route::get('/drug-interactions', [PatientEmrController::class, 'drugInteractions']);
    Route::post('/drug-interactions', [PatientEmrController::class, 'storeDrugInteraction']);
    Route::put('/drug-interactions/{interaction}', [PatientEmrController::class, 'updateDrugInteraction']);
    Route::delete('/drug-interactions/{interaction}', [PatientEmrController::class, 'destroyDrugInteraction']);

    // Disease-Drug Interactions Master Data
    Route::get('/disease-drug-interactions', [PatientEmrController::class, 'diseaseDrugInteractions']);
    Route::post('/disease-drug-interactions', [PatientEmrController::class, 'storeDiseaseDrugInteraction']);
    Route::put('/disease-drug-interactions/{interaction}', [PatientEmrController::class, 'updateDiseaseDrugInteraction']);
    Route::delete('/disease-drug-interactions/{interaction}', [PatientEmrController::class, 'destroyDiseaseDrugInteraction']);

    // Prescription Templates
    Route::get('/prescription-templates/departments', [PrescriptionTemplateController::class, 'departments']);
    Route::apiResource('prescription-templates', PrescriptionTemplateController::class);

    // Medicine Favorites
    Route::post('/medicine-favorites/bulk', [MedicineFavoriteController::class, 'bulkStore']);
    Route::apiResource('medicine-favorites', MedicineFavoriteController::class);

    // Billing Module
    Route::prefix('billing')->group(function () {
        // Referrals
        Route::get('/referrals', [BillingController::class, 'referrals']);
        Route::post('/referrals', [BillingController::class, 'storeReferral']);
        Route::get('/referrals/types', [BillingController::class, 'referralTypes']);
        Route::post('/referrals/validate-code', [BillingController::class, 'validateReferralCode']);
        Route::get('/referrals/{referral}', [BillingController::class, 'showReferral']);
        Route::put('/referrals/{referral}', [BillingController::class, 'updateReferral']);
        Route::delete('/referrals/{referral}', [BillingController::class, 'destroyReferral']);
        Route::get('/referrals/{referral}/stats', [BillingController::class, 'referralStats']);

        // Doctor Commissions
        Route::get('/doctor-commissions', [BillingController::class, 'doctorCommissions']);
        Route::get('/doctor-commissions/{doctor}', [BillingController::class, 'doctorCommissionDetails']);

        // Commission Payments
        Route::post('/commission-payment', [BillingController::class, 'payCommission']);
        Route::get('/commission-ledger', [BillingController::class, 'commissionLedger']);
        Route::get('/payment-methods', [BillingController::class, 'paymentMethods']);

        // Due Collection
        Route::get('/due-invoices', [BillingController::class, 'dueInvoices']);
        Route::post('/due-invoices/{invoice}/collect', [BillingController::class, 'collectDue']);
        Route::get('/due-stats-by-patient', [BillingController::class, 'dueStatsByPatient']);

        // Transactions
        Route::get('/transactions', [BillingController::class, 'transactions']);
        Route::get('/transactions/summary', [BillingController::class, 'transactionsSummary']);

        // Consultation Fee
        Route::get('/consultation-fee', [BillingController::class, 'getConsultationFee']);
        Route::put('/doctors/{doctor}/fees', [BillingController::class, 'updateDoctorFees']);

        // Reports
        Route::get('/reports/billing', [BillingController::class, 'billingReport']);
        Route::get('/reports/commission', [BillingController::class, 'commissionReport']);
    });
});
