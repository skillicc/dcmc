<template>
    <div>
        <v-card :loading="loading">
            <v-card-title class="d-flex align-center">
                <v-btn icon variant="text" :to="{ name: 'vitals.index' }" class="mr-2">
                    <v-icon>mdi-arrow-left</v-icon>
                </v-btn>
                <v-icon class="mr-2">mdi-heart-pulse</v-icon>
                Record Patient Vitals
            </v-card-title>

            <v-card-text>
                <v-form @submit.prevent="submit">
                    <v-row>
                        <!-- Patient Selection -->
                        <v-col cols="12" md="6">
                            <v-autocomplete
                                v-model="form.patient_id"
                                :items="patients"
                                item-title="name"
                                item-value="id"
                                label="Patient *"
                                :rules="[v => !!v || 'Patient is required']"
                                variant="outlined"
                                :loading="searchingPatients"
                                @update:search="searchPatients"
                            >
                                <template v-slot:item="{ props, item }">
                                    <v-list-item v-bind="props">
                                        <template v-slot:subtitle>
                                            {{ item.raw.patient_id }} | {{ item.raw.phone }}
                                        </template>
                                    </v-list-item>
                                </template>
                            </v-autocomplete>
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field
                                v-model="form.date"
                                label="Date"
                                type="date"
                                variant="outlined"
                            />
                        </v-col>
                    </v-row>

                    <v-divider class="my-4" />
                    <h4 class="text-subtitle-1 font-weight-bold mb-4">
                        <v-icon class="mr-2">mdi-stethoscope</v-icon>
                        Vital Signs
                    </h4>

                    <v-row>
                        <v-col cols="12" md="4">
                            <v-text-field
                                v-model="form.blood_pressure"
                                label="Blood Pressure"
                                placeholder="120/80"
                                variant="outlined"
                                prepend-inner-icon="mdi-heart"
                                hint="Systolic/Diastolic (e.g., 120/80)"
                            />
                        </v-col>
                        <v-col cols="12" md="4">
                            <v-text-field
                                v-model.number="form.pulse"
                                label="Pulse Rate"
                                type="number"
                                suffix="bpm"
                                variant="outlined"
                                prepend-inner-icon="mdi-pulse"
                            />
                        </v-col>
                        <v-col cols="12" md="4">
                            <v-text-field
                                v-model.number="form.temperature"
                                label="Temperature"
                                type="number"
                                step="0.1"
                                suffix="°F"
                                variant="outlined"
                                prepend-inner-icon="mdi-thermometer"
                            />
                        </v-col>
                        <v-col cols="12" md="4">
                            <v-text-field
                                v-model.number="form.respiratory_rate"
                                label="Respiratory Rate"
                                type="number"
                                suffix="/min"
                                variant="outlined"
                                prepend-inner-icon="mdi-lungs"
                            />
                        </v-col>
                        <v-col cols="12" md="4">
                            <v-text-field
                                v-model.number="form.oxygen_saturation"
                                label="Oxygen Saturation (SpO2)"
                                type="number"
                                step="0.1"
                                suffix="%"
                                variant="outlined"
                                prepend-inner-icon="mdi-percent"
                            />
                        </v-col>
                    </v-row>

                    <v-divider class="my-4" />
                    <h4 class="text-subtitle-1 font-weight-bold mb-4">
                        <v-icon class="mr-2">mdi-scale-bathroom</v-icon>
                        Body Measurements
                    </h4>

                    <v-row>
                        <v-col cols="12" md="4">
                            <v-text-field
                                v-model.number="form.weight"
                                label="Weight"
                                type="number"
                                step="0.1"
                                suffix="kg"
                                variant="outlined"
                                prepend-inner-icon="mdi-weight-kilogram"
                                @update:model-value="calculateBmi"
                            />
                        </v-col>
                        <v-col cols="12" md="4">
                            <v-text-field
                                v-model.number="form.height"
                                label="Height"
                                type="number"
                                step="0.1"
                                suffix="cm"
                                variant="outlined"
                                prepend-inner-icon="mdi-human-male-height"
                                @update:model-value="calculateBmi"
                            />
                        </v-col>
                        <v-col cols="12" md="4">
                            <v-text-field
                                v-model="calculatedBmi"
                                label="BMI (Auto-calculated)"
                                readonly
                                variant="outlined"
                                prepend-inner-icon="mdi-calculator"
                                :hint="bmiCategory"
                                persistent-hint
                            />
                        </v-col>
                    </v-row>

                    <v-row>
                        <v-col cols="12">
                            <v-textarea
                                v-model="form.notes"
                                label="Notes"
                                rows="2"
                                variant="outlined"
                            />
                        </v-col>
                    </v-row>

                    <div class="d-flex justify-end ga-2 mt-4">
                        <v-btn :to="{ name: 'vitals.index' }" variant="outlined">Cancel</v-btn>
                        <v-btn type="submit" color="primary" :loading="submitting">
                            <v-icon start>mdi-content-save</v-icon>
                            Save Vitals
                        </v-btn>
                    </div>
                </v-form>
            </v-card-text>
        </v-card>

        <v-snackbar v-model="snackbar.show" :color="snackbar.color">
            {{ snackbar.message }}
        </v-snackbar>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const route = useRoute();
const loading = ref(false);
const submitting = ref(false);
const searchingPatients = ref(false);
const patients = ref([]);

const form = reactive({
    patient_id: null,
    patient_queue_id: null,
    date: new Date().toISOString().split('T')[0],
    blood_pressure: '',
    pulse: null,
    temperature: null,
    respiratory_rate: null,
    oxygen_saturation: null,
    weight: null,
    height: null,
    notes: '',
});

const snackbar = reactive({
    show: false,
    message: '',
    color: 'success',
});

const calculatedBmi = computed(() => {
    if (form.weight && form.height) {
        const heightInMeters = form.height / 100;
        return (form.weight / (heightInMeters * heightInMeters)).toFixed(1);
    }
    return '';
});

const bmiCategory = computed(() => {
    const bmi = parseFloat(calculatedBmi.value);
    if (!bmi) return '';
    if (bmi < 18.5) return 'Underweight';
    if (bmi < 25) return 'Normal weight';
    if (bmi < 30) return 'Overweight';
    return 'Obese';
});

const calculateBmi = () => {
    // BMI is auto-calculated via computed property
};

let searchTimeout = null;

const searchPatients = async (search) => {
    if (!search || search.length < 2) return;

    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(async () => {
        searchingPatients.value = true;
        try {
            const response = await axios.get('/api/patients', {
                params: { search, per_page: 20 }
            });
            patients.value = response.data.data;
        } catch (error) {
            console.error('Failed to search patients:', error);
        }
        searchingPatients.value = false;
    }, 300);
};

const submit = async () => {
    if (!form.patient_id) {
        snackbar.message = 'Please select a patient';
        snackbar.color = 'error';
        snackbar.show = true;
        return;
    }

    submitting.value = true;
    try {
        await axios.post('/api/vitals', form);
        snackbar.message = 'Vitals recorded successfully';
        snackbar.color = 'success';
        snackbar.show = true;
        setTimeout(() => {
            router.push({ name: 'vitals.index' });
        }, 1000);
    } catch (error) {
        snackbar.message = error.response?.data?.message || 'Failed to save vitals';
        snackbar.color = 'error';
        snackbar.show = true;
    }
    submitting.value = false;
};

onMounted(() => {
    if (route.query.patient_id) {
        form.patient_id = parseInt(route.query.patient_id);
        axios.get(`/api/patients/${route.query.patient_id}`).then(response => {
            patients.value = [response.data.data];
        });
    }

    if (route.query.queue_id) {
        form.patient_queue_id = parseInt(route.query.queue_id);
    }
});
</script>
