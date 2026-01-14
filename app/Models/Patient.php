<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'name',
        'phone',
        'email',
        'date_of_birth',
        'age',
        'gender',
        'blood_group',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'allergies',
        'medical_history',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($patient) {
            $patient->patient_id = 'P' . str_pad((static::max('id') ?? 0) + 1, 6, '0', STR_PAD_LEFT);
        });
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function labReports(): HasMany
    {
        return $this->hasMany(LabReport::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function queues(): HasMany
    {
        return $this->hasMany(PatientQueue::class);
    }

    public function vitals(): HasMany
    {
        return $this->hasMany(Vital::class);
    }

    // EMR Relationships
    public function clinicalData(): HasMany
    {
        return $this->hasMany(PatientClinicalData::class);
    }

    public function chronicDiseases(): HasMany
    {
        return $this->hasMany(PatientChronicDisease::class);
    }

    public function activeChronicDiseases(): HasMany
    {
        return $this->hasMany(PatientChronicDisease::class)->where('status', 'Active');
    }

    public function chronicDiseaseAlerts(): HasMany
    {
        return $this->hasMany(PatientChronicDisease::class)
            ->where('status', 'Active')
            ->where('show_alert', true);
    }

    public function immunizations(): HasMany
    {
        return $this->hasMany(PatientImmunization::class);
    }

    public function familyDiseases(): HasMany
    {
        return $this->hasMany(PatientFamilyDisease::class);
    }

    public function currentMedications(): HasMany
    {
        return $this->hasMany(PatientCurrentMedication::class);
    }

    public function activeMedications(): HasMany
    {
        return $this->hasMany(PatientCurrentMedication::class)->where('status', 'Active');
    }

    // Helper methods for EMR
    public function hasChronicDisease(string $diseaseName): bool
    {
        return $this->chronicDiseases()
            ->where('disease_name', 'like', "%{$diseaseName}%")
            ->where('status', 'Active')
            ->exists();
    }

    public function getChronicDiseaseNames(): array
    {
        return $this->activeChronicDiseases()
            ->pluck('disease_name')
            ->toArray();
    }

    public function checkDrugInteractions(string $drugName): array
    {
        $interactions = [];

        // Check disease-drug interactions
        $chronicDiseases = $this->getChronicDiseaseNames();
        if (! empty($chronicDiseases)) {
            $diseaseInteractions = DiseaseDrugInteraction::checkDiseases($chronicDiseases, $drugName);
            foreach ($diseaseInteractions as $interaction) {
                $interactions[] = [
                    'type' => 'disease',
                    'interaction' => $interaction,
                ];
            }
        }

        // Check drug-drug interactions with current medications
        $currentMeds = $this->activeMedications()->pluck('medication_name')->toArray();
        foreach ($currentMeds as $med) {
            $drugInteraction = DrugInteraction::findInteraction($med, $drugName);
            if ($drugInteraction) {
                $interactions[] = [
                    'type' => 'drug',
                    'interaction' => $drugInteraction,
                ];
            }
        }

        return $interactions;
    }
}
