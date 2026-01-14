<template>
    <div>
        <v-card>
            <v-card-title class="d-flex align-center justify-space-between flex-wrap ga-2">
                <div class="d-flex align-center">
                    <v-icon class="mr-2">mdi-doctor</v-icon>
                    Doctors
                </div>
                <v-btn color="primary" :to="{ name: 'doctors.create' }">
                    <v-icon start>mdi-plus</v-icon>
                    Add Doctor
                </v-btn>
            </v-card-title>

            <v-card-text>
                <v-row class="mb-4">
                    <v-col cols="12" md="4">
                        <v-text-field
                            v-model="search"
                            label="Search doctors..."
                            prepend-inner-icon="mdi-magnify"
                            clearable
                            hide-details
                            @input="debouncedSearch"
                        />
                    </v-col>
                    <v-col cols="12" md="3">
                        <v-select
                            v-model="filters.specialization"
                            label="Specialization"
                            :items="specializations"
                            clearable
                            hide-details
                            @update:model-value="fetchDoctors"
                        />
                    </v-col>
                </v-row>

                <v-data-table-server
                    v-model:items-per-page="itemsPerPage"
                    v-model:page="page"
                    :headers="headers"
                    :items="doctors"
                    :items-length="totalItems"
                    :loading="loading"
                    @update:options="fetchDoctors"
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
                                <p class="text-caption text-grey mb-0">{{ item.specialization }}</p>
                            </div>
                        </div>
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
                            color="info"
                            :to="{ name: 'doctors.schedule', params: { id: item.id } }"
                            title="Schedule"
                        >
                            <v-icon>mdi-calendar-clock</v-icon>
                        </v-btn>
                        <v-btn
                            icon
                            variant="text"
                            size="small"
                            color="primary"
                            :to="{ name: 'doctors.edit', params: { id: item.id } }"
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
                </v-data-table-server>
            </v-card-text>
        </v-card>

        <v-dialog v-model="deleteDialog" max-width="400">
            <v-card>
                <v-card-title>Confirm Delete</v-card-title>
                <v-card-text>
                    Are you sure you want to delete <strong>{{ selectedDoctor?.name }}</strong>?
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="deleteDialog = false">Cancel</v-btn>
                    <v-btn color="error" @click="deleteDoctor" :loading="deleting">Delete</v-btn>
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
const deleting = ref(false);
const doctors = ref([]);
const totalItems = ref(0);
const page = ref(1);
const itemsPerPage = ref(10);
const search = ref('');
const deleteDialog = ref(false);
const selectedDoctor = ref(null);
const specializations = ref([]);

const filters = reactive({ specialization: null });
const snackbar = reactive({ show: false, message: '', color: 'success' });

const headers = [
    { title: 'Doctor', key: 'name', sortable: true },
    { title: 'Phone', key: 'phone' },
    { title: 'Email', key: 'email' },
    { title: 'Commission %', key: 'commission_percentage' },
    { title: 'Status', key: 'status' },
    { title: 'Actions', key: 'actions', sortable: false, align: 'center' },
];

const getInitials = (name) => name?.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2) || 'DR';

let searchTimeout = null;
const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => { page.value = 1; fetchDoctors(); }, 500);
};

const fetchDoctors = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/doctors', {
            params: { page: page.value, per_page: itemsPerPage.value, search: search.value, specialization: filters.specialization },
        });
        doctors.value = response.data.data || [];
        totalItems.value = response.data.total || 0;
    } catch (error) {
        console.error('Failed to fetch doctors:', error);
    }
    loading.value = false;
};

const fetchSpecializations = async () => {
    try {
        const response = await axios.get('/api/specializations');
        specializations.value = response.data.map(s => s.name);
    } catch (error) {
        console.error('Failed to fetch specializations:', error);
    }
};

const confirmDelete = (doctor) => { selectedDoctor.value = doctor; deleteDialog.value = true; };

const deleteDoctor = async () => {
    deleting.value = true;
    try {
        await axios.delete(`/api/doctors/${selectedDoctor.value.id}`);
        snackbar.message = 'Doctor deleted successfully';
        snackbar.color = 'success';
        snackbar.show = true;
        deleteDialog.value = false;
        fetchDoctors();
    } catch (error) {
        snackbar.message = 'Failed to delete doctor';
        snackbar.color = 'error';
        snackbar.show = true;
    }
    deleting.value = false;
};

onMounted(() => { fetchDoctors(); fetchSpecializations(); });
</script>
