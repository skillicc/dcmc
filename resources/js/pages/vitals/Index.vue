<template>
    <div>
        <v-card :loading="loading">
            <v-card-title class="d-flex align-center flex-wrap ga-2">
                <v-icon class="mr-2">mdi-heart-pulse</v-icon>
                OPD Nurse Station - Vital Entry
                <v-spacer />
                <v-btn color="primary" :to="{ name: 'vitals.create' }">
                    <v-icon start>mdi-plus</v-icon>
                    Record Vitals
                </v-btn>
            </v-card-title>

            <v-card-text>
                <!-- Filters -->
                <v-row class="mb-4">
                    <v-col cols="12" md="4">
                        <v-text-field
                            v-model="filters.date"
                            label="Date"
                            type="date"
                            density="compact"
                            variant="outlined"
                            hide-details
                            @update:model-value="fetchVitals"
                        />
                    </v-col>
                    <v-col cols="12" md="4">
                        <v-autocomplete
                            v-model="filters.patient_id"
                            :items="patients"
                            item-title="name"
                            item-value="id"
                            label="Filter by Patient"
                            density="compact"
                            variant="outlined"
                            hide-details
                            clearable
                            :loading="searchingPatients"
                            @update:search="searchPatients"
                            @update:model-value="fetchVitals"
                        />
                    </v-col>
                </v-row>

                <!-- Vitals Table -->
                <v-table>
                    <thead>
                        <tr>
                            <th>Date/Time</th>
                            <th>Patient</th>
                            <th>BP</th>
                            <th>Pulse</th>
                            <th>Temp</th>
                            <th>SpO2</th>
                            <th>Weight</th>
                            <th>BMI</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="vital in vitals" :key="vital.id">
                            <td>
                                <div>{{ formatDate(vital.date) }}</div>
                                <div class="text-caption text-grey">{{ formatTime(vital.created_at) }}</div>
                            </td>
                            <td>
                                <div>{{ vital.patient?.name }}</div>
                                <div class="text-caption text-grey">{{ vital.patient?.patient_id }}</div>
                            </td>
                            <td>
                                <v-chip v-if="vital.blood_pressure" size="small" :color="getBpColor(vital.blood_pressure)">
                                    {{ vital.blood_pressure }}
                                </v-chip>
                                <span v-else class="text-grey">-</span>
                            </td>
                            <td>
                                <span v-if="vital.pulse">{{ vital.pulse }} bpm</span>
                                <span v-else class="text-grey">-</span>
                            </td>
                            <td>
                                <v-chip v-if="vital.temperature" size="small" :color="getTempColor(vital.temperature)">
                                    {{ vital.temperature }}°F
                                </v-chip>
                                <span v-else class="text-grey">-</span>
                            </td>
                            <td>
                                <span v-if="vital.oxygen_saturation">{{ vital.oxygen_saturation }}%</span>
                                <span v-else class="text-grey">-</span>
                            </td>
                            <td>
                                <span v-if="vital.weight">{{ vital.weight }} kg</span>
                                <span v-else class="text-grey">-</span>
                            </td>
                            <td>
                                <v-chip v-if="vital.bmi" size="small" :color="getBmiColor(vital.bmi)">
                                    {{ vital.bmi }}
                                </v-chip>
                                <span v-else class="text-grey">-</span>
                            </td>
                            <td>
                                <v-btn-group density="compact" variant="text">
                                    <v-btn color="primary" size="small" @click="viewVital(vital)">
                                        <v-icon>mdi-eye</v-icon>
                                    </v-btn>
                                    <v-btn color="primary" size="small" :to="{ name: 'vitals.edit', params: { id: vital.id } }">
                                        <v-icon>mdi-pencil</v-icon>
                                    </v-btn>
                                </v-btn-group>
                            </td>
                        </tr>
                        <tr v-if="vitals.length === 0">
                            <td colspan="9" class="text-center text-grey py-4">
                                No vital records found
                            </td>
                        </tr>
                    </tbody>
                </v-table>

                <!-- Pagination -->
                <div class="d-flex justify-center mt-4" v-if="pagination.lastPage > 1">
                    <v-pagination
                        v-model="pagination.currentPage"
                        :length="pagination.lastPage"
                        @update:model-value="fetchVitals"
                    />
                </div>
            </v-card-text>
        </v-card>

        <!-- View Dialog -->
        <v-dialog v-model="viewDialog" max-width="500">
            <v-card v-if="selectedVital">
                <v-card-title>
                    <v-icon class="mr-2">mdi-heart-pulse</v-icon>
                    Vital Signs
                </v-card-title>
                <v-card-text>
                    <v-list>
                        <v-list-item>
                            <template v-slot:prepend>
                                <v-icon>mdi-account</v-icon>
                            </template>
                            <v-list-item-title>{{ selectedVital.patient?.name }}</v-list-item-title>
                            <v-list-item-subtitle>{{ formatDate(selectedVital.date) }}</v-list-item-subtitle>
                        </v-list-item>
                    </v-list>
                    <v-divider />
                    <v-row class="mt-2">
                        <v-col cols="6">
                            <div class="text-caption text-grey">Blood Pressure</div>
                            <div class="text-h6">{{ selectedVital.blood_pressure || '-' }}</div>
                        </v-col>
                        <v-col cols="6">
                            <div class="text-caption text-grey">Pulse</div>
                            <div class="text-h6">{{ selectedVital.pulse ? selectedVital.pulse + ' bpm' : '-' }}</div>
                        </v-col>
                        <v-col cols="6">
                            <div class="text-caption text-grey">Temperature</div>
                            <div class="text-h6">{{ selectedVital.temperature ? selectedVital.temperature + '°F' : '-' }}</div>
                        </v-col>
                        <v-col cols="6">
                            <div class="text-caption text-grey">SpO2</div>
                            <div class="text-h6">{{ selectedVital.oxygen_saturation ? selectedVital.oxygen_saturation + '%' : '-' }}</div>
                        </v-col>
                        <v-col cols="6">
                            <div class="text-caption text-grey">Weight</div>
                            <div class="text-h6">{{ selectedVital.weight ? selectedVital.weight + ' kg' : '-' }}</div>
                        </v-col>
                        <v-col cols="6">
                            <div class="text-caption text-grey">Height</div>
                            <div class="text-h6">{{ selectedVital.height ? selectedVital.height + ' cm' : '-' }}</div>
                        </v-col>
                        <v-col cols="6">
                            <div class="text-caption text-grey">BMI</div>
                            <div class="text-h6">{{ selectedVital.bmi || '-' }}</div>
                        </v-col>
                        <v-col cols="6">
                            <div class="text-caption text-grey">Respiratory Rate</div>
                            <div class="text-h6">{{ selectedVital.respiratory_rate ? selectedVital.respiratory_rate + '/min' : '-' }}</div>
                        </v-col>
                    </v-row>
                    <div v-if="selectedVital.notes" class="mt-4">
                        <div class="text-caption text-grey">Notes</div>
                        <div>{{ selectedVital.notes }}</div>
                    </div>
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn @click="viewDialog = false">Close</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

const loading = ref(false);
const searchingPatients = ref(false);
const vitals = ref([]);
const patients = ref([]);
const viewDialog = ref(false);
const selectedVital = ref(null);

const filters = reactive({
    date: new Date().toISOString().split('T')[0],
    patient_id: null,
});

const pagination = reactive({
    currentPage: 1,
    lastPage: 1,
});

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('en-GB');
};

const formatTime = (datetime) => {
    if (!datetime) return '';
    return new Date(datetime).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
};

const getBpColor = (bp) => {
    if (!bp) return 'grey';
    const systolic = parseInt(bp.split('/')[0]);
    if (systolic < 90) return 'info';
    if (systolic < 120) return 'success';
    if (systolic < 140) return 'warning';
    return 'error';
};

const getTempColor = (temp) => {
    if (temp < 97) return 'info';
    if (temp <= 99) return 'success';
    if (temp <= 100.4) return 'warning';
    return 'error';
};

const getBmiColor = (bmi) => {
    if (bmi < 18.5) return 'info';
    if (bmi < 25) return 'success';
    if (bmi < 30) return 'warning';
    return 'error';
};

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

const fetchVitals = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/vitals', {
            params: {
                ...filters,
                page: pagination.currentPage,
            }
        });
        vitals.value = response.data.data;
        pagination.lastPage = response.data.last_page;
    } catch (error) {
        console.error('Failed to load vitals:', error);
    }
    loading.value = false;
};

const viewVital = (vital) => {
    selectedVital.value = vital;
    viewDialog.value = true;
};

onMounted(() => {
    fetchVitals();
});
</script>
