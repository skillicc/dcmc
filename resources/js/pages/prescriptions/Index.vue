<template>
    <div>
        <v-card>
            <v-card-title class="d-flex align-center justify-space-between flex-wrap ga-2">
                <div class="d-flex align-center">
                    <v-icon class="mr-2">mdi-prescription</v-icon>
                    Prescriptions
                </div>
                <v-btn color="primary" :to="{ name: 'prescriptions.create' }">
                    <v-icon start>mdi-plus</v-icon>
                    New Prescription
                </v-btn>
            </v-card-title>

            <v-card-text>
                <v-row class="mb-4">
                    <v-col cols="12" md="4">
                        <v-text-field v-model="search" label="Search..." prepend-inner-icon="mdi-magnify" clearable hide-details @input="debouncedSearch" />
                    </v-col>
                    <v-col cols="12" md="3">
                        <v-text-field v-model="filters.date" label="Date" type="date" hide-details @update:model-value="fetchPrescriptions" />
                    </v-col>
                </v-row>

                <v-data-table-server
                    v-model:items-per-page="itemsPerPage"
                    v-model:page="page"
                    :headers="headers"
                    :items="prescriptions"
                    :items-length="totalItems"
                    :loading="loading"
                    @update:options="fetchPrescriptions"
                >
                    <template v-slot:item.patient="{ item }">
                        <div>
                            <p class="font-weight-medium mb-0">{{ item.patient?.name }}</p>
                            <p class="text-caption text-grey mb-0">{{ item.patient?.phone }}</p>
                        </div>
                    </template>

                    <template v-slot:item.actions="{ item }">
                        <v-btn icon variant="text" size="small" color="info" :to="{ name: 'prescriptions.show', params: { id: item.id } }">
                            <v-icon>mdi-eye</v-icon>
                        </v-btn>
                        <v-btn icon variant="text" size="small" color="success" @click="printPrescription(item)">
                            <v-icon>mdi-printer</v-icon>
                        </v-btn>
                        <v-btn icon variant="text" size="small" color="error" @click="confirmDelete(item)">
                            <v-icon>mdi-delete</v-icon>
                        </v-btn>
                    </template>
                </v-data-table-server>
            </v-card-text>
        </v-card>

        <v-dialog v-model="deleteDialog" max-width="400">
            <v-card>
                <v-card-title>Confirm Delete</v-card-title>
                <v-card-text>Delete this prescription?</v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="deleteDialog = false">Cancel</v-btn>
                    <v-btn color="error" @click="deletePrescription" :loading="deleting">Delete</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-snackbar v-model="snackbar.show" :color="snackbar.color">{{ snackbar.message }}</v-snackbar>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

const loading = ref(false);
const deleting = ref(false);
const prescriptions = ref([]);
const totalItems = ref(0);
const page = ref(1);
const itemsPerPage = ref(10);
const search = ref('');
const deleteDialog = ref(false);
const selectedPrescription = ref(null);

const filters = reactive({ date: '' });
const snackbar = reactive({ show: false, message: '', color: 'success' });

const headers = [
    { title: 'Prescription ID', key: 'prescription_id' },
    { title: 'Patient', key: 'patient' },
    { title: 'Doctor', key: 'doctor.name' },
    { title: 'Date', key: 'date' },
    { title: 'Actions', key: 'actions', sortable: false },
];

let searchTimeout = null;
const debouncedSearch = () => { clearTimeout(searchTimeout); searchTimeout = setTimeout(() => { page.value = 1; fetchPrescriptions(); }, 500); };

const fetchPrescriptions = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/prescriptions', {
            params: { page: page.value, per_page: itemsPerPage.value, search: search.value, ...filters },
        });
        prescriptions.value = response.data.data || [];
        totalItems.value = response.data.total || 0;
    } catch (error) { console.error('Failed to fetch prescriptions:', error); }
    loading.value = false;
};

const printPrescription = (prescription) => {
    window.open(`/prescriptions/${prescription.id}/print`, '_blank');
};

const confirmDelete = (prescription) => { selectedPrescription.value = prescription; deleteDialog.value = true; };

const deletePrescription = async () => {
    deleting.value = true;
    try {
        await axios.delete(`/api/prescriptions/${selectedPrescription.value.id}`);
        snackbar.message = 'Prescription deleted'; snackbar.color = 'success'; snackbar.show = true;
        deleteDialog.value = false; fetchPrescriptions();
    } catch (error) { snackbar.message = 'Failed to delete'; snackbar.color = 'error'; snackbar.show = true; }
    deleting.value = false;
};

onMounted(() => fetchPrescriptions());
</script>
