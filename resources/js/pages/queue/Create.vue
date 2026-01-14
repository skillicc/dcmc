<template>
    <div>
        <v-card :loading="loading">
            <v-card-title class="d-flex align-center">
                <v-btn icon variant="text" :to="{ name: 'queue.index' }" class="mr-2">
                    <v-icon>mdi-arrow-left</v-icon>
                </v-btn>
                <v-icon class="mr-2">mdi-account-plus</v-icon>
                Add Patient to Queue
            </v-card-title>

            <v-card-text>
                <v-form @submit.prevent="submit">
                    <v-row>
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
                            <v-select
                                v-model="form.doctor_id"
                                :items="doctors"
                                item-title="name"
                                item-value="id"
                                label="Doctor *"
                                :rules="[v => !!v || 'Doctor is required']"
                                variant="outlined"
                            >
                                <template v-slot:item="{ props, item }">
                                    <v-list-item v-bind="props">
                                        <template v-slot:subtitle>
                                            {{ item.raw.specialization }}
                                        </template>
                                    </v-list-item>
                                </template>
                            </v-select>
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field
                                v-model="form.date"
                                label="Date"
                                type="date"
                                variant="outlined"
                            />
                        </v-col>
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
                        <v-btn :to="{ name: 'queue.index' }" variant="outlined">Cancel</v-btn>
                        <v-btn type="submit" color="primary" :loading="submitting">
                            <v-icon start>mdi-content-save</v-icon>
                            Add to Queue
                        </v-btn>
                    </div>
                </v-form>
            </v-card-text>
        </v-card>

        <!-- Success Dialog with Token -->
        <v-dialog v-model="successDialog" max-width="400" persistent>
            <v-card>
                <v-card-text class="text-center pa-6">
                    <v-icon color="success" size="64" class="mb-4">mdi-check-circle</v-icon>
                    <h3 class="text-h5 mb-4">Patient Added to Queue</h3>
                    <v-card color="primary" variant="tonal" class="mb-4">
                        <v-card-text>
                            <div class="text-h2 font-weight-bold">{{ createdQueue?.serial_no }}</div>
                            <div class="text-h6">{{ createdQueue?.token_no }}</div>
                        </v-card-text>
                    </v-card>
                    <p class="text-body-2 text-grey">{{ createdQueue?.patient?.name }}</p>
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn @click="goToQueue">Done</v-btn>
                    <v-btn color="primary" @click="printAndClose">
                        <v-icon start>mdi-printer</v-icon>
                        Print Token
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-snackbar v-model="snackbar.show" :color="snackbar.color">
            {{ snackbar.message }}
        </v-snackbar>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const route = useRoute();
const loading = ref(false);
const submitting = ref(false);
const searchingPatients = ref(false);
const patients = ref([]);
const doctors = ref([]);
const successDialog = ref(false);
const createdQueue = ref(null);

const form = reactive({
    patient_id: null,
    doctor_id: null,
    date: new Date().toISOString().split('T')[0],
    notes: '',
});

const snackbar = reactive({
    show: false,
    message: '',
    color: 'success',
});

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

const fetchDoctors = async () => {
    try {
        const response = await axios.get('/api/doctors', { params: { per_page: 100 } });
        doctors.value = response.data.data;
    } catch (error) {
        console.error('Failed to load doctors:', error);
    }
};

const submit = async () => {
    if (!form.patient_id || !form.doctor_id) {
        snackbar.message = 'Please select patient and doctor';
        snackbar.color = 'error';
        snackbar.show = true;
        return;
    }

    submitting.value = true;
    try {
        const response = await axios.post('/api/queue', form);
        createdQueue.value = response.data.data;
        successDialog.value = true;
    } catch (error) {
        snackbar.message = error.response?.data?.message || 'Failed to add to queue';
        snackbar.color = 'error';
        snackbar.show = true;
    }
    submitting.value = false;
};

const goToQueue = () => {
    router.push({ name: 'queue.index' });
};

const printAndClose = () => {
    window.print();
    goToQueue();
};

onMounted(() => {
    fetchDoctors();

    if (route.query.patient_id) {
        form.patient_id = parseInt(route.query.patient_id);
        axios.get(`/api/patients/${route.query.patient_id}`).then(response => {
            patients.value = [response.data.data];
        });
    }
});
</script>
