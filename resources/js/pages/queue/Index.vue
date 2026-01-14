<template>
    <div>
        <v-card>
            <v-card-title class="d-flex align-center flex-wrap ga-2">
                <v-icon class="mr-2">mdi-account-clock</v-icon>
                Patient Queue Management
                <v-spacer />
                <v-btn color="primary" :to="{ name: 'queue.create' }">
                    <v-icon start>mdi-plus</v-icon>
                    Add to Queue
                </v-btn>
            </v-card-title>

            <v-card-text>
                <!-- Stats Cards -->
                <v-row class="mb-4">
                    <v-col cols="6" md="3">
                        <v-card color="blue" variant="tonal">
                            <v-card-text class="text-center">
                                <div class="text-h4">{{ stats.total }}</div>
                                <div class="text-caption">Total Today</div>
                            </v-card-text>
                        </v-card>
                    </v-col>
                    <v-col cols="6" md="3">
                        <v-card color="warning" variant="tonal">
                            <v-card-text class="text-center">
                                <div class="text-h4">{{ stats.waiting }}</div>
                                <div class="text-caption">Waiting</div>
                            </v-card-text>
                        </v-card>
                    </v-col>
                    <v-col cols="6" md="3">
                        <v-card color="info" variant="tonal">
                            <v-card-text class="text-center">
                                <div class="text-h4">{{ stats.in_progress }}</div>
                                <div class="text-caption">In Progress</div>
                            </v-card-text>
                        </v-card>
                    </v-col>
                    <v-col cols="6" md="3">
                        <v-card color="success" variant="tonal">
                            <v-card-text class="text-center">
                                <div class="text-h4">{{ stats.completed }}</div>
                                <div class="text-caption">Completed</div>
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>

                <!-- Current Patient -->
                <v-card v-if="stats.current" color="primary" variant="tonal" class="mb-4">
                    <v-card-text>
                        <div class="d-flex align-center">
                            <v-avatar color="primary" size="50" class="mr-3">
                                <span class="text-white">{{ stats.current.serial_no }}</span>
                            </v-avatar>
                            <div>
                                <div class="text-h6">Now Serving: {{ stats.current.patient?.name }}</div>
                                <div class="text-caption">Token: {{ stats.current.token_no }} | Doctor: {{ stats.current.doctor?.name }}</div>
                            </div>
                        </div>
                    </v-card-text>
                </v-card>

                <!-- Filters -->
                <v-row class="mb-4">
                    <v-col cols="12" md="3">
                        <v-text-field
                            v-model="filters.date"
                            label="Date"
                            type="date"
                            density="compact"
                            variant="outlined"
                            hide-details
                            clearable
                            @update:model-value="fetchQueues"
                        />
                    </v-col>
                    <v-col cols="12" md="3">
                        <v-select
                            v-model="filters.doctor_id"
                            :items="doctors"
                            item-title="name"
                            item-value="id"
                            label="Doctor"
                            density="compact"
                            variant="outlined"
                            hide-details
                            clearable
                            @update:model-value="fetchQueues"
                        />
                    </v-col>
                    <v-col cols="12" md="3">
                        <v-select
                            v-model="filters.status"
                            :items="statuses"
                            label="Status"
                            density="compact"
                            variant="outlined"
                            hide-details
                            clearable
                            @update:model-value="fetchQueues"
                        />
                    </v-col>
                    <v-col cols="12" md="3">
                        <v-btn color="success" block @click="callNext" :loading="callingNext">
                            <v-icon start>mdi-bullhorn</v-icon>
                            Call Next Patient
                        </v-btn>
                    </v-col>
                </v-row>

                <!-- Queue Table -->
                <v-table>
                    <thead>
                        <tr>
                            <th>Serial</th>
                            <th>Token</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Check In</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="queue in queues" :key="queue.id">
                            <td>
                                <v-avatar color="primary" size="32">
                                    <span class="text-white text-caption">{{ queue.serial_no }}</span>
                                </v-avatar>
                            </td>
                            <td>
                                <v-chip size="small" color="primary" variant="outlined">
                                    {{ queue.token_no }}
                                </v-chip>
                            </td>
                            <td>
                                <div>{{ queue.patient?.name }}</div>
                                <div class="text-caption text-grey">{{ queue.patient?.phone }}</div>
                            </td>
                            <td>{{ queue.doctor?.name }}</td>
                            <td>{{ queue.check_in_time }}</td>
                            <td>
                                <v-chip :color="getStatusColor(queue.status)" size="small">
                                    {{ queue.status }}
                                </v-chip>
                            </td>
                            <td>
                                <v-btn-group density="compact" variant="text">
                                    <v-btn
                                        v-if="queue.status === 'Waiting'"
                                        color="info"
                                        size="small"
                                        @click="updateStatus(queue, 'In Progress')"
                                    >
                                        <v-icon>mdi-play</v-icon>
                                    </v-btn>
                                    <v-btn
                                        v-if="queue.status === 'In Progress'"
                                        color="success"
                                        size="small"
                                        @click="updateStatus(queue, 'Completed')"
                                    >
                                        <v-icon>mdi-check</v-icon>
                                    </v-btn>
                                    <v-btn
                                        color="primary"
                                        size="small"
                                        :to="{ name: 'vitals.create', query: { patient_id: queue.patient_id, queue_id: queue.id } }"
                                    >
                                        <v-icon>mdi-heart-pulse</v-icon>
                                    </v-btn>
                                    <v-btn color="primary" size="small" @click="printToken(queue)">
                                        <v-icon>mdi-printer</v-icon>
                                    </v-btn>
                                </v-btn-group>
                            </td>
                        </tr>
                        <tr v-if="queues.length === 0">
                            <td colspan="7" class="text-center text-grey py-4">
                                No patients in queue
                            </td>
                        </tr>
                    </tbody>
                </v-table>
            </v-card-text>
        </v-card>

        <!-- Token Print Dialog -->
        <v-dialog v-model="tokenDialog" max-width="350">
            <v-card ref="tokenCard">
                <v-card-text class="text-center pa-6">
                    <h3 class="text-h6 mb-2">{{ settings.clinic_name }}</h3>
                    <v-divider class="my-3" />
                    <div class="text-h2 font-weight-bold text-primary my-4">
                        {{ selectedQueue?.serial_no }}
                    </div>
                    <v-chip color="primary" size="large" class="mb-3">
                        {{ selectedQueue?.token_no }}
                    </v-chip>
                    <div class="text-body-1 mb-1">{{ selectedQueue?.patient?.name }}</div>
                    <div class="text-caption text-grey mb-3">Doctor: {{ selectedQueue?.doctor?.name }}</div>
                    <v-divider class="my-3" />
                    <div class="text-caption">{{ new Date().toLocaleDateString() }}</div>
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn @click="tokenDialog = false">Close</v-btn>
                    <v-btn color="primary" @click="doPrintToken">Print</v-btn>
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
const callingNext = ref(false);
const queues = ref([]);
const doctors = ref([]);
const tokenDialog = ref(false);
const selectedQueue = ref(null);

const statuses = ['Waiting', 'In Progress', 'Completed', 'Cancelled'];

const filters = reactive({
    date: new Date().toISOString().split('T')[0],
    doctor_id: null,
    status: null,
});

const stats = reactive({
    total: 0,
    waiting: 0,
    in_progress: 0,
    completed: 0,
    cancelled: 0,
    current: null,
});

const settings = reactive({
    clinic_name: 'DCMS Diagnostic Centre',
});

const snackbar = reactive({
    show: false,
    message: '',
    color: 'success',
});

const getStatusColor = (status) => {
    const colors = {
        'Waiting': 'warning',
        'In Progress': 'info',
        'Completed': 'success',
        'Cancelled': 'error',
    };
    return colors[status] || 'grey';
};

const fetchDoctors = async () => {
    try {
        const response = await axios.get('/api/doctors', { params: { per_page: 100 } });
        doctors.value = response.data.data;
    } catch (error) {
        console.error('Failed to load doctors:', error);
    }
};

const fetchQueues = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/queue', { params: filters });
        queues.value = response.data.data;
    } catch (error) {
        console.error('Failed to load queues:', error);
    }
    loading.value = false;
};

const fetchStats = async () => {
    try {
        const response = await axios.get('/api/queue/stats', {
            params: { doctor_id: filters.doctor_id, date: filters.date }
        });
        Object.assign(stats, response.data.data);
    } catch (error) {
        console.error('Failed to load stats:', error);
    }
};

const fetchSettings = async () => {
    try {
        const response = await axios.get('/api/settings');
        if (response.data.clinic_name) {
            settings.clinic_name = response.data.clinic_name;
        }
    } catch (error) {
        console.error('Failed to load settings:', error);
    }
};

const updateStatus = async (queue, status) => {
    try {
        await axios.patch(`/api/queue/${queue.id}/status`, { status });
        snackbar.message = 'Status updated successfully';
        snackbar.color = 'success';
        snackbar.show = true;
        fetchQueues();
        fetchStats();
    } catch (error) {
        snackbar.message = error.response?.data?.message || 'Failed to update status';
        snackbar.color = 'error';
        snackbar.show = true;
    }
};

const callNext = async () => {
    if (!filters.doctor_id) {
        snackbar.message = 'Please select a doctor first';
        snackbar.color = 'warning';
        snackbar.show = true;
        return;
    }
    callingNext.value = true;
    try {
        const response = await axios.post('/api/queue/call-next', {
            doctor_id: filters.doctor_id,
        });
        snackbar.message = `Calling: ${response.data.data.patient?.name}`;
        snackbar.color = 'success';
        snackbar.show = true;
        fetchQueues();
        fetchStats();
    } catch (error) {
        snackbar.message = error.response?.data?.message || 'No patients waiting';
        snackbar.color = 'warning';
        snackbar.show = true;
    }
    callingNext.value = false;
};

const printToken = (queue) => {
    selectedQueue.value = queue;
    tokenDialog.value = true;
};

const doPrintToken = () => {
    window.print();
};

onMounted(() => {
    fetchDoctors();
    fetchQueues();
    fetchStats();
    fetchSettings();
});
</script>
