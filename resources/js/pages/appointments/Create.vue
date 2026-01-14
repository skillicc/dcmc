<template>
    <div>
        <v-card>
            <v-card-title class="d-flex align-center">
                <v-btn icon variant="text" :to="{ name: 'appointments.index' }" class="mr-2"><v-icon>mdi-arrow-left</v-icon></v-btn>
                <v-icon class="mr-2">mdi-calendar-plus</v-icon>
                New Appointment
            </v-card-title>

            <v-card-text>
                <v-form ref="form" @submit.prevent="submitForm">
                    <v-row>
                        <v-col cols="12" md="6">
                            <v-autocomplete
                                v-model="formData.patient_id"
                                label="Patient *"
                                :items="patients"
                                item-title="name"
                                item-value="id"
                                :rules="[rules.required]"
                                prepend-inner-icon="mdi-account"
                                :loading="loadingPatients"
                                @update:search="searchPatients"
                            >
                                <template v-slot:item="{ props, item }">
                                    <v-list-item v-bind="props">
                                        <template v-slot:subtitle>{{ item.raw.phone }}</template>
                                    </v-list-item>
                                </template>
                            </v-autocomplete>
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-select
                                v-model="formData.doctor_id"
                                label="Doctor *"
                                :items="doctors"
                                item-title="name"
                                item-value="id"
                                :rules="[rules.required]"
                                prepend-inner-icon="mdi-doctor"
                            >
                                <template v-slot:item="{ props, item }">
                                    <v-list-item v-bind="props">
                                        <template v-slot:subtitle>{{ item.raw.specialization }}</template>
                                    </v-list-item>
                                </template>
                            </v-select>
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field v-model="formData.date" label="Date *" type="date" :rules="[rules.required]" prepend-inner-icon="mdi-calendar" />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field v-model="formData.time" label="Time *" type="time" :rules="[rules.required]" prepend-inner-icon="mdi-clock" />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-select v-model="formData.status" label="Status" :items="statuses" prepend-inner-icon="mdi-flag" />
                        </v-col>
                        <v-col cols="12">
                            <v-textarea v-model="formData.reason" label="Reason for Visit" rows="2" prepend-inner-icon="mdi-text" />
                        </v-col>
                        <v-col cols="12">
                            <v-textarea v-model="formData.notes" label="Notes" rows="2" prepend-inner-icon="mdi-note" />
                        </v-col>
                    </v-row>

                    <v-divider class="my-4" />

                    <div class="d-flex justify-end ga-2">
                        <v-btn variant="outlined" :to="{ name: 'appointments.index' }">Cancel</v-btn>
                        <v-btn color="primary" type="submit" :loading="loading">
                            <v-icon start>mdi-content-save</v-icon>Save Appointment
                        </v-btn>
                    </div>
                </v-form>
            </v-card-text>
        </v-card>

        <v-snackbar v-model="snackbar.show" :color="snackbar.color">{{ snackbar.message }}</v-snackbar>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const route = useRoute();
const form = ref(null);
const loading = ref(false);
const loadingPatients = ref(false);
const patients = ref([]);
const doctors = ref([]);

const statuses = ['Pending', 'Confirmed', 'Completed', 'Cancelled'];

const formData = reactive({
    patient_id: route.query.patient_id ? parseInt(route.query.patient_id) : null,
    doctor_id: null, date: '', time: '', status: 'Pending', reason: '', notes: '',
});

const snackbar = reactive({ show: false, message: '', color: 'success' });
const rules = { required: (v) => !!v || 'This field is required' };

let searchTimeout = null;
const searchPatients = (search) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(async () => {
        if (!search || search.length < 2) return;
        loadingPatients.value = true;
        try {
            const response = await axios.get('/api/patients', { params: { search, per_page: 10 } });
            patients.value = response.data.data || [];
        } catch (error) { console.error('Failed to search patients:', error); }
        loadingPatients.value = false;
    }, 500);
};

const fetchDoctors = async () => {
    try { const response = await axios.get('/api/doctors'); doctors.value = response.data.data || []; }
    catch (error) { console.error('Failed to fetch doctors:', error); }
};

const fetchPatientIfNeeded = async () => {
    if (formData.patient_id) {
        try {
            const response = await axios.get(`/api/patients/${formData.patient_id}`);
            patients.value = [response.data.data];
        } catch (error) { console.error('Failed to fetch patient:', error); }
    }
};

const submitForm = async () => {
    const { valid } = await form.value.validate();
    if (!valid) return;

    loading.value = true;
    try {
        await axios.post('/api/appointments', formData);
        snackbar.message = 'Appointment created successfully'; snackbar.color = 'success'; snackbar.show = true;
        setTimeout(() => router.push({ name: 'appointments.index' }), 1000);
    } catch (error) {
        snackbar.message = error.response?.data?.message || 'Failed to create appointment';
        snackbar.color = 'error'; snackbar.show = true;
    }
    loading.value = false;
};

onMounted(() => { fetchDoctors(); fetchPatientIfNeeded(); });
</script>
