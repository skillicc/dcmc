<template>
    <div>
        <v-card>
            <v-card-title class="d-flex align-center justify-space-between">
                <div class="d-flex align-center">
                    <v-btn icon variant="text" :to="{ name: 'prescriptions.index' }" class="mr-2"><v-icon>mdi-arrow-left</v-icon></v-btn>
                    <v-icon class="mr-2">mdi-prescription</v-icon>
                    New E-Prescription
                </div>
                <div class="d-flex ga-2">
                    <v-btn
                        v-if="formData.doctor_id"
                        color="warning"
                        variant="tonal"
                        size="small"
                        @click="showFavoritesDialog = true"
                    >
                        <v-icon start size="small">mdi-star</v-icon>
                        Favorites
                    </v-btn>
                    <v-btn
                        color="info"
                        variant="tonal"
                        size="small"
                        @click="showTemplateDialog = true"
                    >
                        <v-icon start size="small">mdi-file-document</v-icon>
                        Load Template
                    </v-btn>
                </div>
            </v-card-title>

            <v-card-text>
                <v-form ref="form" @submit.prevent="submitForm">
                    <v-row>
                        <v-col cols="12" md="4">
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
                                        <template v-slot:subtitle>{{ item.raw.phone }} | Age: {{ item.raw.age }}</template>
                                    </v-list-item>
                                </template>
                            </v-autocomplete>
                        </v-col>
                        <v-col cols="12" md="4">
                            <v-select
                                v-model="formData.doctor_id"
                                label="Doctor *"
                                :items="doctors"
                                item-title="name"
                                item-value="id"
                                :rules="[rules.required]"
                                prepend-inner-icon="mdi-doctor"
                                @update:model-value="onDoctorChange"
                            />
                        </v-col>
                        <v-col cols="12" md="4">
                            <v-select
                                v-model="formData.department"
                                label="Department"
                                :items="departments"
                                prepend-inner-icon="mdi-hospital"
                                @update:model-value="fetchTemplates"
                            />
                        </v-col>
                        <v-col cols="12" md="4">
                            <v-text-field v-model="formData.date" label="Date *" type="date" :rules="[rules.required]" prepend-inner-icon="mdi-calendar" />
                        </v-col>
                    </v-row>

                    <!-- Vitals -->
                    <h3 class="text-subtitle-1 font-weight-bold mt-4 mb-3">
                        <v-icon class="mr-1" size="small">mdi-heart-pulse</v-icon>
                        Vitals
                    </h3>
                    <v-row>
                        <v-col cols="6" md="2">
                            <v-text-field v-model="formData.vitals.bp" label="BP" placeholder="120/80" dense />
                        </v-col>
                        <v-col cols="6" md="2">
                            <v-text-field v-model="formData.vitals.pulse" label="Pulse" placeholder="72" dense />
                        </v-col>
                        <v-col cols="6" md="2">
                            <v-text-field v-model="formData.vitals.temp" label="Temp" placeholder="98.6" dense />
                        </v-col>
                        <v-col cols="6" md="2">
                            <v-text-field v-model="formData.vitals.weight" label="Weight (kg)" dense />
                        </v-col>
                        <v-col cols="6" md="2">
                            <v-text-field v-model="formData.vitals.height" label="Height (cm)" dense />
                        </v-col>
                        <v-col cols="6" md="2">
                            <v-text-field v-model="formData.vitals.spo2" label="SpO2" placeholder="98%" dense />
                        </v-col>
                    </v-row>

                    <!-- Chief Complaints & Diagnosis -->
                    <v-row class="mt-2">
                        <v-col cols="12" md="6">
                            <v-textarea v-model="formData.chief_complaints" label="Chief Complaints" rows="2" />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-textarea v-model="formData.diagnosis" label="Diagnosis" rows="2" />
                        </v-col>
                    </v-row>

                    <!-- Medicines -->
                    <h3 class="text-subtitle-1 font-weight-bold mt-4 mb-3 d-flex align-center justify-space-between">
                        <span><v-icon class="mr-1" size="small">mdi-pill</v-icon> Medicines</span>
                        <v-btn size="small" color="primary" variant="tonal" @click="addMedicine">
                            <v-icon start>mdi-plus</v-icon>Add Medicine
                        </v-btn>
                    </h3>
                    <v-card variant="outlined" class="mb-4" v-for="(med, index) in formData.medicines" :key="index">
                        <v-card-text>
                            <v-row>
                                <v-col cols="12" md="4">
                                    <v-text-field v-model="med.name" label="Medicine Name *" dense />
                                </v-col>
                                <v-col cols="6" md="2">
                                    <v-combobox v-model="med.dosage" label="Dosage" :items="dosages" dense />
                                </v-col>
                                <v-col cols="6" md="2">
                                    <v-select v-model="med.frequency" label="Frequency" :items="frequencies" dense />
                                </v-col>
                                <v-col cols="6" md="2">
                                    <v-text-field v-model="med.duration" label="Duration" placeholder="7 days" dense />
                                </v-col>
                                <v-col cols="6" md="2" class="d-flex align-center">
                                    <v-btn icon variant="text" color="error" size="small" @click="removeMedicine(index)">
                                        <v-icon>mdi-delete</v-icon>
                                    </v-btn>
                                </v-col>
                            </v-row>
                            <v-text-field v-model="med.instructions" label="Instructions" placeholder="After meal" dense class="mt-2" />
                        </v-card-text>
                    </v-card>

                    <!-- Tests Advised -->
                    <h3 class="text-subtitle-1 font-weight-bold mt-4 mb-3">
                        <v-icon class="mr-1" size="small">mdi-test-tube</v-icon>
                        Tests Advised
                    </h3>
                    <v-autocomplete
                        v-model="formData.tests_advised"
                        label="Select Tests"
                        :items="tests"
                        item-title="name"
                        item-value="id"
                        multiple
                        chips
                        closable-chips
                    />

                    <!-- Advice -->
                    <v-textarea v-model="formData.advice" label="Advice / Instructions" rows="2" class="mt-4" />

                    <!-- Follow Up -->
                    <v-row class="mt-2">
                        <v-col cols="12" md="6">
                            <v-text-field v-model="formData.follow_up_date" label="Follow Up Date" type="date" prepend-inner-icon="mdi-calendar" />
                        </v-col>
                    </v-row>

                    <v-divider class="my-4" />

                    <div class="d-flex justify-end ga-2">
                        <v-btn variant="outlined" :to="{ name: 'prescriptions.index' }">Cancel</v-btn>
                        <v-btn color="primary" type="submit" :loading="loading">
                            <v-icon start>mdi-content-save</v-icon>Save Prescription
                        </v-btn>
                    </div>
                </v-form>
            </v-card-text>
        </v-card>

        <!-- Template Selection Dialog -->
        <v-dialog v-model="showTemplateDialog" max-width="700">
            <v-card>
                <v-card-title class="d-flex align-center">
                    <v-icon class="mr-2">mdi-file-document-multiple</v-icon>
                    Load Prescription Template
                </v-card-title>
                <v-card-text>
                    <v-row class="mb-4">
                        <v-col cols="12" md="6">
                            <v-text-field
                                v-model="templateSearch"
                                label="Search templates..."
                                prepend-inner-icon="mdi-magnify"
                                clearable
                                hide-details
                                @input="debouncedTemplateSearch"
                            />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-select
                                v-model="templateDepartment"
                                label="Filter by Department"
                                :items="departments"
                                clearable
                                hide-details
                                @update:model-value="fetchTemplates"
                            />
                        </v-col>
                    </v-row>
                    <v-list v-if="templates.length">
                        <v-list-item
                            v-for="template in templates"
                            :key="template.id"
                            @click="applyTemplate(template)"
                            class="mb-2"
                            rounded
                            border
                        >
                            <template v-slot:prepend>
                                <v-avatar :color="getDepartmentColor(template.department)" size="40">
                                    <v-icon color="white" size="small">{{ getDepartmentIcon(template.department) }}</v-icon>
                                </v-avatar>
                            </template>
                            <v-list-item-title>{{ template.name }}</v-list-item-title>
                            <v-list-item-subtitle>
                                {{ template.department }} |
                                {{ template.medicines?.length || 0 }} medicines |
                                {{ template.is_global ? 'Global' : template.doctor?.name }}
                            </v-list-item-subtitle>
                            <template v-slot:append>
                                <v-btn icon variant="text" color="primary">
                                    <v-icon>mdi-chevron-right</v-icon>
                                </v-btn>
                            </template>
                        </v-list-item>
                    </v-list>
                    <p v-else class="text-grey text-center py-4">No templates found</p>
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="showTemplateDialog = false">Close</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Favorites Dialog -->
        <v-dialog v-model="showFavoritesDialog" max-width="600">
            <v-card>
                <v-card-title class="d-flex align-center">
                    <v-icon class="mr-2" color="warning">mdi-star</v-icon>
                    Favorite Medicines
                </v-card-title>
                <v-card-text>
                    <v-list v-if="favorites.length">
                        <v-list-item
                            v-for="fav in favorites"
                            :key="fav.id"
                            @click="addFavoriteMedicine(fav)"
                            class="mb-2"
                            rounded
                            border
                        >
                            <template v-slot:prepend>
                                <v-avatar color="primary" size="40">
                                    <v-icon color="white" size="small">mdi-pill</v-icon>
                                </v-avatar>
                            </template>
                            <v-list-item-title>{{ fav.medicine_name }}</v-list-item-title>
                            <v-list-item-subtitle>
                                {{ fav.default_dosage }} | {{ fav.default_frequency }} | {{ fav.default_duration }}
                            </v-list-item-subtitle>
                            <template v-slot:append>
                                <v-btn icon variant="text" color="success">
                                    <v-icon>mdi-plus</v-icon>
                                </v-btn>
                            </template>
                        </v-list-item>
                    </v-list>
                    <p v-else class="text-grey text-center py-4">No favorite medicines found for this doctor</p>
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="showFavoritesDialog = false">Close</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-snackbar v-model="snackbar.show" :color="snackbar.color">{{ snackbar.message }}</v-snackbar>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue';
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
const templates = ref([]);
const favorites = ref([]);
const departments = ref([]);
const showTemplateDialog = ref(false);
const showFavoritesDialog = ref(false);
const templateSearch = ref('');
const templateDepartment = ref(null);

const dosages = ['500mg', '250mg', '100mg', '50mg', '1 tab', '2 tab', '5ml', '10ml', '1+0+0', '0+1+0', '0+0+1', '1+1+0', '1+0+1', '0+1+1', '1+1+1', 'As needed'];
const frequencies = ['Once daily', 'Twice daily', 'Three times daily', 'Four times daily', 'Every 4 hours', 'Every 6 hours', 'Every 8 hours', 'Every 12 hours', 'Once weekly', 'As needed', 'Before meals', 'After meals', 'At bedtime'];

const formData = reactive({
    patient_id: route.query.patient_id ? parseInt(route.query.patient_id) : null,
    doctor_id: null,
    department: 'General',
    date: new Date().toISOString().split('T')[0],
    vitals: { bp: '', pulse: '', temp: '', weight: '', height: '', spo2: '' },
    chief_complaints: '',
    diagnosis: '',
    medicines: [{ name: '', dosage: '', frequency: '', duration: '', instructions: '' }],
    tests_advised: [],
    advice: '',
    follow_up_date: '',
});

const snackbar = reactive({ show: false, message: '', color: 'success' });
const rules = { required: (v) => !!v || 'This field is required' };

const getDepartmentColor = (dept) => {
    const colors = {
        General: 'blue',
        Eye: 'teal',
        Dental: 'cyan',
        ENT: 'purple',
        Cardiology: 'red',
        Orthopedics: 'orange',
        Gynecology: 'pink',
        Pediatrics: 'green',
        Dermatology: 'brown',
        Neurology: 'indigo',
    };
    return colors[dept] || 'grey';
};

const getDepartmentIcon = (dept) => {
    const icons = {
        General: 'mdi-stethoscope',
        Eye: 'mdi-eye',
        Dental: 'mdi-tooth',
        ENT: 'mdi-ear-hearing',
        Cardiology: 'mdi-heart-pulse',
        Orthopedics: 'mdi-bone',
        Gynecology: 'mdi-human-pregnant',
        Pediatrics: 'mdi-baby-face',
        Dermatology: 'mdi-hand-back-right',
        Neurology: 'mdi-brain',
    };
    return icons[dept] || 'mdi-file-document';
};

const addMedicine = () => {
    formData.medicines.push({ name: '', dosage: '', frequency: '', duration: '', instructions: '' });
};

const removeMedicine = (index) => {
    if (formData.medicines.length > 1) formData.medicines.splice(index, 1);
};

const addFavoriteMedicine = (fav) => {
    // Check if first medicine is empty, replace it
    if (formData.medicines.length === 1 && !formData.medicines[0].name) {
        formData.medicines[0] = {
            name: fav.medicine_name,
            dosage: fav.default_dosage,
            frequency: fav.default_frequency,
            duration: fav.default_duration,
            instructions: fav.default_instructions,
        };
    } else {
        formData.medicines.push({
            name: fav.medicine_name,
            dosage: fav.default_dosage,
            frequency: fav.default_frequency,
            duration: fav.default_duration,
            instructions: fav.default_instructions,
        });
    }
    snackbar.message = `${fav.medicine_name} added`;
    snackbar.color = 'success';
    snackbar.show = true;
};

const applyTemplate = (template) => {
    formData.chief_complaints = template.chief_complaints || '';
    formData.diagnosis = template.diagnosis || '';
    formData.advice = template.advice || '';
    formData.department = template.department;

    if (template.medicines?.length) {
        formData.medicines = template.medicines.map(med => ({
            name: med.name || '',
            dosage: med.dosage || '',
            frequency: med.frequency || '',
            duration: med.duration || '',
            instructions: med.instructions || '',
        }));
    }

    if (template.tests_advised?.length) {
        // tests_advised is now an array of objects with test_name
        const testNames = template.tests_advised.map(t => t.test_name);
        formData.tests_advised = tests.value
            .filter(t => testNames.includes(t.name))
            .map(t => t.id);
    }

    showTemplateDialog.value = false;
    snackbar.message = `Template "${template.name}" applied`;
    snackbar.color = 'success';
    snackbar.show = true;
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

let templateSearchTimeout = null;
const debouncedTemplateSearch = () => {
    clearTimeout(templateSearchTimeout);
    templateSearchTimeout = setTimeout(fetchTemplates, 500);
};

const fetchDoctors = async () => {
    try { const response = await axios.get('/api/doctors'); doctors.value = response.data.data || []; }
    catch (error) { console.error('Failed to fetch doctors:', error); }
};

const fetchTests = async () => {
    try { const response = await axios.get('/api/tests'); tests.value = response.data.data || []; }
    catch (error) { console.error('Failed to fetch tests:', error); }
};

const fetchDepartments = async () => {
    try {
        const response = await axios.get('/api/prescription-templates/departments');
        departments.value = response.data.data || [];
    } catch (error) { console.error('Failed to fetch departments:', error); }
};

const fetchTemplates = async () => {
    try {
        const response = await axios.get('/api/prescription-templates', {
            params: {
                search: templateSearch.value,
                department: templateDepartment.value || formData.department,
                doctor_id: formData.doctor_id,
            },
        });
        templates.value = response.data.data || [];
    } catch (error) { console.error('Failed to fetch templates:', error); }
};

const fetchFavorites = async () => {
    if (!formData.doctor_id) {
        favorites.value = [];
        return;
    }
    try {
        const response = await axios.get('/api/medicine-favorites', {
            params: { doctor_id: formData.doctor_id },
        });
        favorites.value = response.data.data || [];
    } catch (error) { console.error('Failed to fetch favorites:', error); }
};

const onDoctorChange = () => {
    fetchFavorites();
    fetchTemplates();
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
        // Format tests_advised as array of objects with test_id and test_name
        const payload = {
            ...formData,
            tests_advised: formData.tests_advised.map(testId => {
                const test = tests.value.find(t => t.id === testId);
                return {
                    test_id: testId,
                    test_name: test ? test.name : `Test ${testId}`,
                };
            }),
        };
        await axios.post('/api/prescriptions', payload);
        snackbar.message = 'Prescription created successfully'; snackbar.color = 'success'; snackbar.show = true;
        setTimeout(() => router.push({ name: 'prescriptions.index' }), 1000);
    } catch (error) {
        snackbar.message = error.response?.data?.message || 'Failed to create prescription';
        snackbar.color = 'error'; snackbar.show = true;
    }
    loading.value = false;
};

onMounted(() => {
    fetchDoctors();
    fetchTests();
    fetchDepartments();
    fetchPatientIfNeeded();
});
</script>
