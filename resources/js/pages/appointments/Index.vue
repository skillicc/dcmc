<template>
    <div>
        <v-card>
            <v-card-title class="d-flex align-center justify-space-between flex-wrap ga-2">
                <div class="d-flex align-center">
                    <v-icon class="mr-2">mdi-calendar-clock</v-icon>
                    Appointments
                </div>
                <v-btn color="primary" :to="{ name: 'appointments.create' }">
                    <v-icon start>mdi-plus</v-icon>
                    New Appointment
                </v-btn>
            </v-card-title>

            <v-card-text>
                <v-row class="mb-4">
                    <v-col cols="12" md="3">
                        <v-text-field v-model="search" label="Search..." prepend-inner-icon="mdi-magnify" clearable hide-details @input="debouncedSearch" />
                    </v-col>
                    <v-col cols="12" md="2">
                        <v-text-field v-model="filters.date" label="Date" type="date" hide-details @update:model-value="fetchAppointments" />
                    </v-col>
                    <v-col cols="12" md="2">
                        <v-select v-model="filters.status" label="Status" :items="statuses" clearable hide-details @update:model-value="fetchAppointments" />
                    </v-col>
                    <v-col cols="12" md="3">
                        <v-select v-model="filters.doctor_id" label="Doctor" :items="doctors" item-title="name" item-value="id" clearable hide-details @update:model-value="fetchAppointments" />
                    </v-col>
                </v-row>

                <v-data-table-server
                    v-model:items-per-page="itemsPerPage"
                    v-model:page="page"
                    :headers="headers"
                    :items="appointments"
                    :items-length="totalItems"
                    :loading="loading"
                    @update:options="fetchAppointments"
                >
                    <template v-slot:item.patient="{ item }">
                        <div class="d-flex align-center py-2">
                            <v-avatar color="primary" size="32" class="mr-2">
                                <span class="text-white text-caption">{{ getInitials(item.patient?.name) }}</span>
                            </v-avatar>
                            <div>
                                <p class="font-weight-medium mb-0">{{ item.patient?.name }}</p>
                                <p class="text-caption text-grey mb-0">{{ item.patient?.phone }}</p>
                            </div>
                        </div>
                    </template>

                    <template v-slot:item.status="{ item }">
                        <v-chip :color="getStatusColor(item.status)" size="small">{{ item.status }}</v-chip>
                    </template>

                    <template v-slot:item.actions="{ item }">
                        <v-menu>
                            <template v-slot:activator="{ props }">
                                <v-btn icon variant="text" size="small" v-bind="props"><v-icon>mdi-dots-vertical</v-icon></v-btn>
                            </template>
                            <v-list density="compact">
                                <v-list-item @click="updateStatus(item, 'Confirmed')"><v-list-item-title>Confirm</v-list-item-title></v-list-item>
                                <v-list-item @click="updateStatus(item, 'Completed')"><v-list-item-title>Complete</v-list-item-title></v-list-item>
                                <v-list-item @click="updateStatus(item, 'Cancelled')"><v-list-item-title>Cancel</v-list-item-title></v-list-item>
                                <v-divider />
                                <v-list-item @click="confirmDelete(item)"><v-list-item-title class="text-error">Delete</v-list-item-title></v-list-item>
                            </v-list>
                        </v-menu>
                    </template>
                </v-data-table-server>
            </v-card-text>
        </v-card>

        <v-dialog v-model="deleteDialog" max-width="400">
            <v-card>
                <v-card-title>Confirm Delete</v-card-title>
                <v-card-text>Delete this appointment?</v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="deleteDialog = false">Cancel</v-btn>
                    <v-btn color="error" @click="deleteAppointment" :loading="deleting">Delete</v-btn>
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
const appointments = ref([]);
const doctors = ref([]);
const totalItems = ref(0);
const page = ref(1);
const itemsPerPage = ref(10);
const search = ref('');
const deleteDialog = ref(false);
const selectedAppointment = ref(null);

const statuses = ['Pending', 'Confirmed', 'Completed', 'Cancelled'];
const filters = reactive({ date: '', status: null, doctor_id: null });
const snackbar = reactive({ show: false, message: '', color: 'success' });

const headers = [
    { title: 'Patient', key: 'patient' },
    { title: 'Doctor', key: 'doctor.name' },
    { title: 'Date', key: 'date' },
    { title: 'Time', key: 'time' },
    { title: 'Status', key: 'status' },
    { title: 'Actions', key: 'actions', sortable: false },
];

const getInitials = (name) => name?.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2) || 'PA';
const getStatusColor = (status) => ({ Pending: 'warning', Confirmed: 'info', Completed: 'success', Cancelled: 'error' }[status] || 'grey');

let searchTimeout = null;
const debouncedSearch = () => { clearTimeout(searchTimeout); searchTimeout = setTimeout(() => { page.value = 1; fetchAppointments(); }, 500); };

const fetchAppointments = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/appointments', {
            params: { page: page.value, per_page: itemsPerPage.value, search: search.value, ...filters },
        });
        appointments.value = response.data.data || [];
        totalItems.value = response.data.total || 0;
    } catch (error) { console.error('Failed to fetch appointments:', error); }
    loading.value = false;
};

const fetchDoctors = async () => {
    try { const response = await axios.get('/api/doctors'); doctors.value = response.data.data || []; }
    catch (error) { console.error('Failed to fetch doctors:', error); }
};

const updateStatus = async (appointment, status) => {
    try {
        await axios.patch(`/api/appointments/${appointment.id}/status`, { status });
        snackbar.message = 'Status updated'; snackbar.color = 'success'; snackbar.show = true;
        fetchAppointments();
    } catch (error) { snackbar.message = 'Failed to update status'; snackbar.color = 'error'; snackbar.show = true; }
};

const confirmDelete = (appointment) => { selectedAppointment.value = appointment; deleteDialog.value = true; };

const deleteAppointment = async () => {
    deleting.value = true;
    try {
        await axios.delete(`/api/appointments/${selectedAppointment.value.id}`);
        snackbar.message = 'Appointment deleted'; snackbar.color = 'success'; snackbar.show = true;
        deleteDialog.value = false; fetchAppointments();
    } catch (error) { snackbar.message = 'Failed to delete'; snackbar.color = 'error'; snackbar.show = true; }
    deleting.value = false;
};

onMounted(() => { fetchAppointments(); fetchDoctors(); });
</script>
