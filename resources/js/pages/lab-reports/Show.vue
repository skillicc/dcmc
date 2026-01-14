<template>
    <div>
        <v-card :loading="loading">
            <v-card-title class="d-flex align-center justify-space-between">
                <div class="d-flex align-center">
                    <v-btn icon variant="text" :to="{ name: 'lab-reports.index' }" class="mr-2"><v-icon>mdi-arrow-left</v-icon></v-btn>
                    <v-icon class="mr-2">mdi-file-document</v-icon>
                    Lab Report Details
                </div>
                <div>
                    <v-btn color="primary" variant="outlined" @click="enterResults" v-if="report.status !== 'Completed'" class="mr-2">
                        <v-icon start>mdi-pencil</v-icon>Enter Results
                    </v-btn>
                    <v-btn color="success" @click="printReport" v-if="report.status === 'Completed'">
                        <v-icon start>mdi-printer</v-icon>Print
                    </v-btn>
                </div>
            </v-card-title>

            <v-card-text>
                <v-row>
                    <v-col cols="12" md="4">
                        <v-card variant="outlined">
                            <v-card-title class="text-subtitle-1 bg-grey-lighten-4">Patient Info</v-card-title>
                            <v-card-text>
                                <p><strong>Name:</strong> {{ report.patient?.name }}</p>
                                <p><strong>Age/Gender:</strong> {{ report.patient?.age }} / {{ report.patient?.gender }}</p>
                                <p><strong>Phone:</strong> {{ report.patient?.phone }}</p>
                            </v-card-text>
                        </v-card>
                    </v-col>
                    <v-col cols="12" md="4">
                        <v-card variant="outlined">
                            <v-card-title class="text-subtitle-1 bg-grey-lighten-4">Report Info</v-card-title>
                            <v-card-text>
                                <p><strong>Report ID:</strong> {{ report.report_id }}</p>
                                <p><strong>Test:</strong> {{ report.test?.name }}</p>
                                <p><strong>Sample Date:</strong> {{ report.sample_date }}</p>
                            </v-card-text>
                        </v-card>
                    </v-col>
                    <v-col cols="12" md="4">
                        <v-card variant="outlined">
                            <v-card-title class="text-subtitle-1 bg-grey-lighten-4">Status</v-card-title>
                            <v-card-text>
                                <v-chip :color="getStatusColor(report.status)" class="mb-2">{{ report.status }}</v-chip>
                                <p v-if="report.doctor"><strong>Referred By:</strong> {{ report.doctor?.name }}</p>
                                <p v-if="report.delivery_date"><strong>Delivery:</strong> {{ report.delivery_date }}</p>
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>

                <!-- Results -->
                <v-card variant="outlined" class="mt-4">
                    <v-card-title class="text-subtitle-1 bg-grey-lighten-4">
                        <v-icon class="mr-2" size="small">mdi-clipboard-list</v-icon>Test Results
                    </v-card-title>
                    <v-card-text class="pa-0">
                        <v-table>
                            <thead>
                                <tr>
                                    <th>Parameter</th>
                                    <th>Result</th>
                                    <th>Unit</th>
                                    <th>Normal Range</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(param, index) in report.parameters" :key="index">
                                    <td class="font-weight-medium">{{ param.name }}</td>
                                    <td :class="getResultClass(param)">{{ param.value || '-' }}</td>
                                    <td>{{ param.unit }}</td>
                                    <td>{{ param.normal_range }}</td>
                                    <td>
                                        <v-chip :color="getParamStatusColor(param.status)" size="x-small">
                                            {{ param.status || 'Normal' }}
                                        </v-chip>
                                    </td>
                                </tr>
                            </tbody>
                        </v-table>
                    </v-card-text>
                </v-card>

                <!-- Remarks -->
                <v-card variant="outlined" class="mt-4" v-if="report.remarks">
                    <v-card-title class="text-subtitle-1 bg-grey-lighten-4">Remarks</v-card-title>
                    <v-card-text>{{ report.remarks }}</v-card-text>
                </v-card>
            </v-card-text>
        </v-card>

        <!-- Enter Results Dialog -->
        <v-dialog v-model="resultsDialog" max-width="800">
            <v-card>
                <v-card-title>Enter Test Results</v-card-title>
                <v-card-text>
                    <v-row v-for="(param, index) in editParameters" :key="index" class="mb-2">
                        <v-col cols="4">
                            <v-text-field v-model="param.name" label="Parameter" dense />
                        </v-col>
                        <v-col cols="3">
                            <v-text-field v-model="param.value" label="Result" dense />
                        </v-col>
                        <v-col cols="2">
                            <v-text-field v-model="param.unit" label="Unit" dense />
                        </v-col>
                        <v-col cols="3">
                            <v-text-field v-model="param.normal_range" label="Normal Range" dense />
                        </v-col>
                    </v-row>
                    <v-select v-model="editStatus" label="Status" :items="statuses" class="mt-4" />
                    <v-textarea v-model="editRemarks" label="Remarks" rows="2" />
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="resultsDialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="saveResults" :loading="saving">Save Results</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-snackbar v-model="snackbar.show" :color="snackbar.color">{{ snackbar.message }}</v-snackbar>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const loading = ref(true);
const saving = ref(false);
const resultsDialog = ref(false);
const editParameters = ref([]);
const editStatus = ref('');
const editRemarks = ref('');

const statuses = ['Pending', 'Sample Collected', 'Processing', 'Completed'];
const snackbar = reactive({ show: false, message: '', color: 'success' });

const report = reactive({
    id: '', report_id: '', patient: {}, doctor: {}, test: {},
    sample_date: '', delivery_date: '', status: '', parameters: [], remarks: '',
});

const getStatusColor = (status) => ({ Pending: 'warning', 'Sample Collected': 'info', Processing: 'primary', Completed: 'success' }[status] || 'grey');

const getResultClass = (param) => {
    if (!param.value || !param.normal_range) return '';
    return '';
};

const getParamStatusColor = (status) => {
    const colors = { Critical: 'error', High: 'warning', Low: 'info', Normal: 'success' };
    return colors[status] || 'success';
};

const fetchReport = async () => {
    loading.value = true;
    try {
        const response = await axios.get(`/api/lab-reports/${route.params.id}`);
        Object.assign(report, response.data.data);
    } catch (error) { console.error('Failed to load report:', error); }
    loading.value = false;
};

const enterResults = () => {
    editParameters.value = JSON.parse(JSON.stringify(report.parameters));
    editStatus.value = report.status;
    editRemarks.value = report.remarks;
    resultsDialog.value = true;
};

const saveResults = async () => {
    saving.value = true;
    try {
        await axios.put(`/api/lab-reports/${report.id}`, {
            parameters: editParameters.value,
            status: editStatus.value,
            remarks: editRemarks.value,
        });
        snackbar.message = 'Results saved successfully'; snackbar.color = 'success'; snackbar.show = true;
        resultsDialog.value = false;
        fetchReport();
    } catch (error) {
        snackbar.message = 'Failed to save results'; snackbar.color = 'error'; snackbar.show = true;
    }
    saving.value = false;
};

const printReport = () => { window.open(`/lab-reports/${report.id}/print`, '_blank'); };

onMounted(() => fetchReport());
</script>
