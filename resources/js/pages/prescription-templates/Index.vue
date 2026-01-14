<template>
    <div>
        <v-card>
            <v-card-title class="d-flex align-center justify-space-between flex-wrap ga-2">
                <div class="d-flex align-center">
                    <v-icon class="mr-2">mdi-file-document-multiple</v-icon>
                    Prescription Templates
                </div>
                <v-btn color="primary" @click="openDialog()">
                    <v-icon start>mdi-plus</v-icon>
                    Add Template
                </v-btn>
            </v-card-title>

            <v-card-text>
                <v-row class="mb-4">
                    <v-col cols="12" md="4">
                        <v-text-field
                            v-model="search"
                            label="Search templates..."
                            prepend-inner-icon="mdi-magnify"
                            clearable
                            hide-details
                            @input="debouncedSearch"
                        />
                    </v-col>
                    <v-col cols="12" md="3">
                        <v-select
                            v-model="filters.department"
                            label="Department"
                            :items="departments"
                            clearable
                            hide-details
                            @update:model-value="fetchTemplates"
                        />
                    </v-col>
                </v-row>

                <v-data-table
                    :headers="headers"
                    :items="templates"
                    :loading="loading"
                    hover
                >
                    <template v-slot:item.name="{ item }">
                        <div class="d-flex align-center py-2">
                            <v-avatar :color="getDepartmentColor(item.department)" size="36" class="mr-3">
                                <v-icon color="white" size="small">{{ getDepartmentIcon(item.department) }}</v-icon>
                            </v-avatar>
                            <div>
                                <p class="font-weight-medium mb-0">{{ item.name }}</p>
                                <p class="text-caption text-grey mb-0">{{ item.department }}</p>
                            </div>
                        </div>
                    </template>

                    <template v-slot:item.doctor="{ item }">
                        {{ item.is_global ? 'Global' : (item.doctor?.name || '-') }}
                    </template>

                    <template v-slot:item.medicines="{ item }">
                        <v-chip size="x-small" color="primary">
                            {{ item.medicines?.length || 0 }} medicines
                        </v-chip>
                    </template>

                    <template v-slot:item.status="{ item }">
                        <v-chip :color="item.is_active ? 'success' : 'error'" size="small">
                            {{ item.is_active ? 'Active' : 'Inactive' }}
                        </v-chip>
                    </template>

                    <template v-slot:item.actions="{ item }">
                        <v-btn
                            icon
                            variant="text"
                            size="small"
                            color="primary"
                            @click="openDialog(item)"
                        >
                            <v-icon>mdi-pencil</v-icon>
                        </v-btn>
                        <v-btn
                            icon
                            variant="text"
                            size="small"
                            color="error"
                            @click="confirmDelete(item)"
                        >
                            <v-icon>mdi-delete</v-icon>
                        </v-btn>
                    </template>
                </v-data-table>
            </v-card-text>
        </v-card>

        <!-- Create/Edit Dialog -->
        <v-dialog v-model="dialog" max-width="800" persistent>
            <v-card>
                <v-card-title>
                    {{ editMode ? 'Edit Template' : 'Create Template' }}
                </v-card-title>
                <v-card-text>
                    <v-form ref="formRef" @submit.prevent="saveTemplate">
                        <v-row>
                            <v-col cols="12" md="6">
                                <v-text-field
                                    v-model="form.name"
                                    label="Template Name"
                                    :rules="[v => !!v || 'Name is required']"
                                    required
                                />
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-select
                                    v-model="form.department"
                                    label="Department"
                                    :items="departments"
                                    :rules="[v => !!v || 'Department is required']"
                                    required
                                />
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-autocomplete
                                    v-model="form.doctor_id"
                                    label="Doctor (Optional)"
                                    :items="doctors"
                                    item-title="name"
                                    item-value="id"
                                    clearable
                                    :disabled="form.is_global"
                                />
                            </v-col>
                            <v-col cols="12" md="6" class="d-flex align-center">
                                <v-switch
                                    v-model="form.is_global"
                                    label="Global Template"
                                    color="primary"
                                    hide-details
                                />
                                <v-switch
                                    v-model="form.is_active"
                                    label="Active"
                                    color="success"
                                    hide-details
                                    class="ml-4"
                                />
                            </v-col>
                            <v-col cols="12">
                                <v-textarea
                                    v-model="form.chief_complaints"
                                    label="Chief Complaints"
                                    rows="2"
                                />
                            </v-col>
                            <v-col cols="12">
                                <v-textarea
                                    v-model="form.diagnosis"
                                    label="Diagnosis"
                                    rows="2"
                                />
                            </v-col>
                            <v-col cols="12">
                                <v-textarea
                                    v-model="form.advice"
                                    label="Advice"
                                    rows="2"
                                />
                            </v-col>

                            <!-- Medicines Section -->
                            <v-col cols="12">
                                <div class="d-flex align-center justify-space-between mb-2">
                                    <span class="text-subtitle-2">Medicines</span>
                                    <v-btn size="small" color="primary" variant="tonal" @click="addMedicine">
                                        <v-icon start size="small">mdi-plus</v-icon>
                                        Add Medicine
                                    </v-btn>
                                </div>
                                <v-table density="compact" v-if="form.medicines.length">
                                    <thead>
                                        <tr>
                                            <th>Medicine Name</th>
                                            <th>Dosage</th>
                                            <th>Frequency</th>
                                            <th>Duration</th>
                                            <th>Instructions</th>
                                            <th width="50"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(med, index) in form.medicines" :key="index">
                                            <td>
                                                <v-text-field
                                                    v-model="med.name"
                                                    density="compact"
                                                    hide-details
                                                    variant="outlined"
                                                />
                                            </td>
                                            <td>
                                                <v-text-field
                                                    v-model="med.dosage"
                                                    density="compact"
                                                    hide-details
                                                    variant="outlined"
                                                    placeholder="e.g., 500mg"
                                                />
                                            </td>
                                            <td>
                                                <v-select
                                                    v-model="med.frequency"
                                                    :items="frequencies"
                                                    density="compact"
                                                    hide-details
                                                    variant="outlined"
                                                />
                                            </td>
                                            <td>
                                                <v-text-field
                                                    v-model="med.duration"
                                                    density="compact"
                                                    hide-details
                                                    variant="outlined"
                                                    placeholder="e.g., 7 days"
                                                />
                                            </td>
                                            <td>
                                                <v-text-field
                                                    v-model="med.instructions"
                                                    density="compact"
                                                    hide-details
                                                    variant="outlined"
                                                    placeholder="e.g., After meals"
                                                />
                                            </td>
                                            <td>
                                                <v-btn
                                                    icon
                                                    variant="text"
                                                    size="x-small"
                                                    color="error"
                                                    @click="removeMedicine(index)"
                                                >
                                                    <v-icon size="small">mdi-close</v-icon>
                                                </v-btn>
                                            </td>
                                        </tr>
                                    </tbody>
                                </v-table>
                                <p v-else class="text-grey text-center py-4">No medicines added</p>
                            </v-col>

                            <!-- Tests Section -->
                            <v-col cols="12">
                                <div class="d-flex align-center justify-space-between mb-2">
                                    <span class="text-subtitle-2">Tests Advised</span>
                                    <v-btn size="small" color="info" variant="tonal" @click="addTest">
                                        <v-icon start size="small">mdi-plus</v-icon>
                                        Add Test
                                    </v-btn>
                                </div>
                                <v-chip-group v-if="form.tests_advised.length">
                                    <v-chip
                                        v-for="(test, index) in form.tests_advised"
                                        :key="index"
                                        closable
                                        @click:close="removeTest(index)"
                                    >
                                        {{ test }}
                                    </v-chip>
                                </v-chip-group>
                                <p v-else class="text-grey text-center py-2">No tests added</p>
                            </v-col>
                        </v-row>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="closeDialog">Cancel</v-btn>
                    <v-btn color="primary" @click="saveTemplate" :loading="saving">
                        {{ editMode ? 'Update' : 'Create' }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Add Test Dialog -->
        <v-dialog v-model="testDialog" max-width="400">
            <v-card>
                <v-card-title>Add Test</v-card-title>
                <v-card-text>
                    <v-text-field
                        v-model="newTest"
                        label="Test Name"
                        autofocus
                        @keyup.enter="confirmAddTest"
                    />
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="testDialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="confirmAddTest">Add</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete Dialog -->
        <v-dialog v-model="deleteDialog" max-width="400">
            <v-card>
                <v-card-title>Confirm Delete</v-card-title>
                <v-card-text>
                    Are you sure you want to delete <strong>{{ selectedTemplate?.name }}</strong>?
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="deleteDialog = false">Cancel</v-btn>
                    <v-btn color="error" @click="deleteTemplate" :loading="deleting">Delete</v-btn>
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
import axios from 'axios';

const loading = ref(false);
const saving = ref(false);
const deleting = ref(false);
const templates = ref([]);
const doctors = ref([]);
const departments = ref([]);
const search = ref('');
const dialog = ref(false);
const testDialog = ref(false);
const deleteDialog = ref(false);
const editMode = ref(false);
const selectedTemplate = ref(null);
const newTest = ref('');
const formRef = ref(null);

const filters = reactive({ department: null });
const snackbar = reactive({ show: false, message: '', color: 'success' });

const frequencies = [
    'Once daily',
    'Twice daily',
    'Three times daily',
    'Four times daily',
    'Every 4 hours',
    'Every 6 hours',
    'Every 8 hours',
    'Every 12 hours',
    'As needed',
    'At bedtime',
    'Before meals',
    'After meals',
];

const defaultForm = {
    name: '',
    department: '',
    doctor_id: null,
    chief_complaints: '',
    diagnosis: '',
    medicines: [],
    tests_advised: [],
    advice: '',
    is_global: false,
    is_active: true,
};

const form = reactive({ ...defaultForm });

const headers = [
    { title: 'Template', key: 'name', sortable: true },
    { title: 'Doctor', key: 'doctor' },
    { title: 'Medicines', key: 'medicines' },
    { title: 'Status', key: 'status' },
    { title: 'Actions', key: 'actions', sortable: false, align: 'center' },
];

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

let searchTimeout = null;
const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(fetchTemplates, 500);
};

const fetchTemplates = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/prescription-templates', {
            params: { search: search.value, department: filters.department },
        });
        templates.value = response.data.data || [];
    } catch (error) {
        console.error('Failed to fetch templates:', error);
    }
    loading.value = false;
};

const fetchDepartments = async () => {
    try {
        const response = await axios.get('/api/prescription-templates/departments');
        departments.value = response.data.data || [];
    } catch (error) {
        console.error('Failed to fetch departments:', error);
    }
};

const fetchDoctors = async () => {
    try {
        const response = await axios.get('/api/doctors', { params: { per_page: 100 } });
        doctors.value = response.data.data || [];
    } catch (error) {
        console.error('Failed to fetch doctors:', error);
    }
};

const openDialog = (template = null) => {
    if (template) {
        editMode.value = true;
        selectedTemplate.value = template;
        Object.assign(form, {
            ...template,
            medicines: template.medicines || [],
            tests_advised: template.tests_advised || [],
        });
    } else {
        editMode.value = false;
        selectedTemplate.value = null;
        Object.assign(form, { ...defaultForm, medicines: [], tests_advised: [] });
    }
    dialog.value = true;
};

const closeDialog = () => {
    dialog.value = false;
    Object.assign(form, { ...defaultForm, medicines: [], tests_advised: [] });
};

const addMedicine = () => {
    form.medicines.push({
        name: '',
        dosage: '',
        frequency: 'Twice daily',
        duration: '',
        instructions: '',
    });
};

const removeMedicine = (index) => {
    form.medicines.splice(index, 1);
};

const addTest = () => {
    newTest.value = '';
    testDialog.value = true;
};

const confirmAddTest = () => {
    if (newTest.value.trim()) {
        form.tests_advised.push(newTest.value.trim());
        testDialog.value = false;
    }
};

const removeTest = (index) => {
    form.tests_advised.splice(index, 1);
};

const saveTemplate = async () => {
    const { valid } = await formRef.value.validate();
    if (!valid) return;

    saving.value = true;
    try {
        if (editMode.value) {
            await axios.put(`/api/prescription-templates/${selectedTemplate.value.id}`, form);
            snackbar.message = 'Template updated successfully';
        } else {
            await axios.post('/api/prescription-templates', form);
            snackbar.message = 'Template created successfully';
        }
        snackbar.color = 'success';
        snackbar.show = true;
        closeDialog();
        fetchTemplates();
    } catch (error) {
        snackbar.message = error.response?.data?.message || 'Failed to save template';
        snackbar.color = 'error';
        snackbar.show = true;
    }
    saving.value = false;
};

const confirmDelete = (template) => {
    selectedTemplate.value = template;
    deleteDialog.value = true;
};

const deleteTemplate = async () => {
    deleting.value = true;
    try {
        await axios.delete(`/api/prescription-templates/${selectedTemplate.value.id}`);
        snackbar.message = 'Template deleted successfully';
        snackbar.color = 'success';
        snackbar.show = true;
        deleteDialog.value = false;
        fetchTemplates();
    } catch (error) {
        snackbar.message = 'Failed to delete template';
        snackbar.color = 'error';
        snackbar.show = true;
    }
    deleting.value = false;
};

onMounted(() => {
    fetchTemplates();
    fetchDepartments();
    fetchDoctors();
});
</script>
