<template>
    <div>
        <v-card>
            <v-card-title class="d-flex align-center justify-space-between flex-wrap ga-2">
                <div class="d-flex align-center">
                    <v-icon class="mr-2">mdi-account-group</v-icon>
                    Patients
                </div>
                <v-btn color="primary" :to="{ name: 'patients.create' }">
                    <v-icon start>mdi-plus</v-icon>
                    Add Patient
                </v-btn>
            </v-card-title>

            <v-card-text>
                <!-- Search & Filters -->
                <v-row class="mb-4">
                    <v-col cols="12" md="4">
                        <v-text-field
                            v-model="search"
                            label="Search patients..."
                            prepend-inner-icon="mdi-magnify"
                            clearable
                            hide-details
                            @input="debouncedSearch"
                        />
                    </v-col>
                    <v-col cols="12" md="3">
                        <v-select
                            v-model="filters.gender"
                            label="Gender"
                            :items="genderOptions"
                            clearable
                            hide-details
                            @update:model-value="fetchPatients"
                        />
                    </v-col>
                    <v-col cols="12" md="3">
                        <v-text-field
                            v-model="filters.date"
                            label="Registration Date"
                            type="date"
                            hide-details
                            @update:model-value="fetchPatients"
                        />
                    </v-col>
                </v-row>

                <!-- Data Table -->
                <v-data-table-server
                    v-model:items-per-page="itemsPerPage"
                    v-model:page="page"
                    :headers="headers"
                    :items="patients"
                    :items-length="totalItems"
                    :loading="loading"
                    @update:options="fetchPatients"
                >
                    <template v-slot:item.name="{ item }">
                        <div class="d-flex align-center py-2">
                            <v-avatar color="primary" size="36" class="mr-3">
                                <span class="text-white text-caption">
                                    {{ getInitials(item.name) }}
                                </span>
                            </v-avatar>
                            <div>
                                <p class="font-weight-medium mb-0">{{ item.name }}</p>
                                <p class="text-caption text-grey mb-0">ID: {{ item.patient_id }}</p>
                            </div>
                        </div>
                    </template>

                    <template v-slot:item.gender="{ item }">
                        <v-chip :color="item.gender === 'Male' ? 'blue' : 'pink'" size="small">
                            {{ item.gender }}
                        </v-chip>
                    </template>

                    <template v-slot:item.actions="{ item }">
                        <v-btn
                            icon
                            variant="text"
                            size="small"
                            color="info"
                            :to="{ name: 'patients.show', params: { id: item.id } }"
                        >
                            <v-icon>mdi-eye</v-icon>
                            <v-tooltip activator="parent">View</v-tooltip>
                        </v-btn>
                        <v-btn
                            icon
                            variant="text"
                            size="small"
                            color="primary"
                            :to="{ name: 'patients.edit', params: { id: item.id } }"
                        >
                            <v-icon>mdi-pencil</v-icon>
                            <v-tooltip activator="parent">Edit</v-tooltip>
                        </v-btn>
                        <v-btn
                            icon
                            variant="text"
                            size="small"
                            color="error"
                            @click="confirmDelete(item)"
                        >
                            <v-icon>mdi-delete</v-icon>
                            <v-tooltip activator="parent">Delete</v-tooltip>
                        </v-btn>
                    </template>
                </v-data-table-server>
            </v-card-text>
        </v-card>

        <!-- Delete Confirmation Dialog -->
        <v-dialog v-model="deleteDialog" max-width="400">
            <v-card>
                <v-card-title class="text-h6">Confirm Delete</v-card-title>
                <v-card-text>
                    Are you sure you want to delete patient <strong>{{ selectedPatient?.name }}</strong>?
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="deleteDialog = false">Cancel</v-btn>
                    <v-btn color="error" @click="deletePatient" :loading="deleting">Delete</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Snackbar -->
        <v-snackbar v-model="snackbar.show" :color="snackbar.color" :timeout="3000">
            {{ snackbar.message }}
        </v-snackbar>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

const loading = ref(false);
const deleting = ref(false);
const patients = ref([]);
const totalItems = ref(0);
const page = ref(1);
const itemsPerPage = ref(10);
const search = ref('');
const deleteDialog = ref(false);
const selectedPatient = ref(null);

const filters = reactive({
    gender: null,
    date: null,
});

const snackbar = reactive({
    show: false,
    message: '',
    color: 'success',
});

const genderOptions = ['Male', 'Female', 'Other'];

const headers = [
    { title: 'Patient', key: 'name', sortable: true },
    { title: 'Phone', key: 'phone', sortable: false },
    { title: 'Age', key: 'age', sortable: true },
    { title: 'Gender', key: 'gender', sortable: false },
    { title: 'Blood Group', key: 'blood_group', sortable: false },
    { title: 'Registered', key: 'created_at', sortable: true },
    { title: 'Actions', key: 'actions', sortable: false, align: 'center' },
];

const getInitials = (name) => {
    return name?.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2) || 'PA';
};

let searchTimeout = null;
const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        page.value = 1;
        fetchPatients();
    }, 500);
};

const fetchPatients = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/patients', {
            params: {
                page: page.value,
                per_page: itemsPerPage.value,
                search: search.value,
                gender: filters.gender,
                date: filters.date,
            },
        });
        patients.value = response.data.data || [];
        totalItems.value = response.data.total || 0;
    } catch (error) {
        console.error('Failed to fetch patients:', error);
        showSnackbar('Failed to load patients', 'error');
    }
    loading.value = false;
};

const confirmDelete = (patient) => {
    selectedPatient.value = patient;
    deleteDialog.value = true;
};

const deletePatient = async () => {
    deleting.value = true;
    try {
        await axios.delete(`/api/patients/${selectedPatient.value.id}`);
        showSnackbar('Patient deleted successfully', 'success');
        deleteDialog.value = false;
        fetchPatients();
    } catch (error) {
        showSnackbar('Failed to delete patient', 'error');
    }
    deleting.value = false;
};

const showSnackbar = (message, color = 'success') => {
    snackbar.message = message;
    snackbar.color = color;
    snackbar.show = true;
};

onMounted(() => {
    fetchPatients();
});
</script>
