<template>
    <div>
        <v-card>
            <v-card-title class="d-flex align-center">
                <v-btn icon variant="text" :to="{ name: 'lab-reports.index' }" class="mr-2"><v-icon>mdi-arrow-left</v-icon></v-btn>
                <v-icon class="mr-2">mdi-file-plus</v-icon>
                New Lab Report
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
                            <v-select v-model="formData.test_id" label="Test *" :items="tests" item-title="name" item-value="id" :rules="[rules.required]" prepend-inner-icon="mdi-test-tube" @update:model-value="onTestSelect" />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-select v-model="formData.doctor_id" label="Referred By (Doctor)" :items="doctors" item-title="name" item-value="id" clearable prepend-inner-icon="mdi-doctor" />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field v-model="formData.sample_date" label="Sample Collection Date *" type="date" :rules="[rules.required]" prepend-inner-icon="mdi-calendar" />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-select v-model="formData.status" label="Status" :items="statuses" prepend-inner-icon="mdi-flag" />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field v-model="formData.delivery_date" label="Expected Delivery Date" type="date" prepend-inner-icon="mdi-calendar-check" />
                        </v-col>
                    </v-row>

                    <!-- Test Parameters -->
                    <h3 class="text-subtitle-1 font-weight-bold mt-4 mb-3">
                        <v-icon class="mr-1" size="small">mdi-clipboard-list</v-icon>
                        Test Results
                    </h3>

                    <v-card variant="outlined" class="mb-4" v-for="(param, index) in formData.parameters" :key="index">
                        <v-card-text>
                            <v-row>
                                <v-col cols="12" md="3">
                                    <v-text-field v-model="param.name" label="Parameter Name" dense />
                                </v-col>
                                <v-col cols="12" md="3">
                                    <v-text-field v-model="param.result" label="Result" dense />
                                </v-col>
                                <v-col cols="12" md="3">
                                    <v-text-field v-model="param.unit" label="Unit" dense />
                                </v-col>
                                <v-col cols="12" md="3">
                                    <v-text-field v-model="param.normal_range" label="Normal Range" dense />
                                </v-col>
                            </v-row>
                        </v-card-text>
                    </v-card>

                    <v-btn variant="tonal" color="primary" size="small" @click="addParameter" class="mb-4">
                        <v-icon start>mdi-plus</v-icon>Add Parameter
                    </v-btn>

                    <v-textarea v-model="formData.remarks" label="Remarks / Comments" rows="2" />

                    <v-divider class="my-4" />

                    <div class="d-flex justify-end ga-2">
                        <v-btn variant="outlined" :to="{ name: 'lab-reports.index' }">Cancel</v-btn>
                        <v-btn color="primary" type="submit" :loading="loading">
                            <v-icon start>mdi-content-save</v-icon>Save Report
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
const tests = ref([]);

const statuses = ['Pending', 'Sample Collected', 'Processing', 'Completed'];

const formData = reactive({
    patient_id: route.query.patient_id ? parseInt(route.query.patient_id) : null,
    test_id: null, doctor_id: null,
    sample_date: new Date().toISOString().split('T')[0],
    delivery_date: '', status: 'Pending',
    parameters: [{ name: '', result: '', unit: '', normal_range: '' }],
    remarks: '',
});

const snackbar = reactive({ show: false, message: '', color: 'success' });
const rules = { required: (v) => !!v || 'This field is required' };

const addParameter = () => {
    formData.parameters.push({ name: '', result: '', unit: '', normal_range: '' });
};

const onTestSelect = (testId) => {
    const test = tests.value.find(t => t.id === testId);
    if (test?.normal_range) {
        formData.parameters = [{ name: test.name, result: '', unit: '', normal_range: test.normal_range }];
    }
};

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

const fetchTests = async () => {
    try { const response = await axios.get('/api/tests'); tests.value = response.data.data || []; }
    catch (error) { console.error('Failed to fetch tests:', error); }
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
        await axios.post('/api/lab-reports', formData);
        snackbar.message = 'Lab report created successfully'; snackbar.color = 'success'; snackbar.show = true;
        setTimeout(() => router.push({ name: 'lab-reports.index' }), 1000);
    } catch (error) {
        snackbar.message = error.response?.data?.message || 'Failed to create report';
        snackbar.color = 'error'; snackbar.show = true;
    }
    loading.value = false;
};

onMounted(() => { fetchDoctors(); fetchTests(); fetchPatientIfNeeded(); });
</script>
