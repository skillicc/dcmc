<template>
    <div>
        <!-- Stats Cards -->
        <v-row class="mb-4">
            <v-col cols="6" md="2">
                <v-card color="grey-lighten-4" variant="flat">
                    <v-card-text class="text-center pa-3">
                        <p class="text-h5 font-weight-bold mb-0">{{ stats.pending_collection }}</p>
                        <p class="text-caption mb-0">Pending Collection</p>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="6" md="2">
                <v-card color="info" variant="flat">
                    <v-card-text class="text-center pa-3">
                        <p class="text-h5 font-weight-bold mb-0 text-white">{{ stats.sample_collected }}</p>
                        <p class="text-caption mb-0 text-white">Sample Collected</p>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="6" md="2">
                <v-card color="primary" variant="flat">
                    <v-card-text class="text-center pa-3">
                        <p class="text-h5 font-weight-bold mb-0 text-white">{{ stats.received_at_lab }}</p>
                        <p class="text-caption mb-0 text-white">At Lab</p>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="6" md="2">
                <v-card color="warning" variant="flat">
                    <v-card-text class="text-center pa-3">
                        <p class="text-h5 font-weight-bold mb-0">{{ stats.awaiting_approval }}</p>
                        <p class="text-caption mb-0">Awaiting Approval</p>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="6" md="2">
                <v-card color="error" variant="flat">
                    <v-card-text class="text-center pa-3">
                        <p class="text-h5 font-weight-bold mb-0 text-white">{{ stats.critical_reports }}</p>
                        <p class="text-caption mb-0 text-white">Critical Today</p>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="6" md="2">
                <v-card color="success" variant="flat">
                    <v-card-text class="text-center pa-3">
                        <p class="text-h5 font-weight-bold mb-0 text-white">{{ stats.completed_today }}</p>
                        <p class="text-caption mb-0 text-white">Completed Today</p>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <v-card>
            <v-card-title class="d-flex align-center justify-space-between flex-wrap ga-2">
                <div class="d-flex align-center">
                    <v-icon class="mr-2">mdi-flask</v-icon>
                    Laboratory Information System
                </div>
                <div class="d-flex ga-2">
                    <v-btn color="info" variant="tonal" size="small" @click="bulkReceiveDialog = true" v-if="selectedReports.length">
                        <v-icon start size="small">mdi-check-all</v-icon>
                        Bulk Receive ({{ selectedReports.length }})
                    </v-btn>
                    <v-btn color="primary" :to="{ name: 'lab-reports.create' }">
                        <v-icon start>mdi-plus</v-icon>
                        New Lab Report
                    </v-btn>
                </div>
            </v-card-title>

            <v-card-text>
                <v-row class="mb-4">
                    <v-col cols="12" md="3">
                        <v-text-field v-model="search" label="Search by ID, Barcode, Patient..." prepend-inner-icon="mdi-magnify" clearable hide-details @input="debouncedSearch" />
                    </v-col>
                    <v-col cols="12" md="2">
                        <v-select v-model="filters.status" label="Status" :items="statuses" clearable hide-details @update:model-value="fetchReports" />
                    </v-col>
                    <v-col cols="12" md="2">
                        <v-select v-model="filters.criticality" label="Criticality" :items="criticalities" clearable hide-details @update:model-value="fetchReports" />
                    </v-col>
                    <v-col cols="12" md="2">
                        <v-text-field v-model="filters.date" label="Date" type="date" hide-details @update:model-value="fetchReports" />
                    </v-col>
                    <v-col cols="12" md="3">
                        <v-btn-toggle v-model="quickFilter" color="primary" density="compact" class="mt-1">
                            <v-btn value="awaiting">Awaiting Approval</v-btn>
                            <v-btn value="critical">Critical</v-btn>
                        </v-btn-toggle>
                    </v-col>
                </v-row>

                <v-data-table-server
                    v-model="selectedReports"
                    v-model:items-per-page="itemsPerPage"
                    v-model:page="page"
                    :headers="headers"
                    :items="reports"
                    :items-length="totalItems"
                    :loading="loading"
                    show-select
                    item-value="id"
                    @update:options="fetchReports"
                >
                    <template v-slot:item.report_id="{ item }">
                        <div>
                            <p class="font-weight-medium mb-0">{{ item.report_id }}</p>
                            <p class="text-caption text-grey mb-0">{{ item.specimen_id }}</p>
                        </div>
                    </template>

                    <template v-slot:item.patient="{ item }">
                        <div>
                            <p class="font-weight-medium mb-0">{{ item.patient?.name }}</p>
                            <p class="text-caption text-grey mb-0">{{ item.patient?.phone }}</p>
                        </div>
                    </template>

                    <template v-slot:item.test="{ item }">
                        <div>
                            <p class="font-weight-medium mb-0">{{ item.test?.name }}</p>
                            <p class="text-caption text-grey mb-0">{{ item.specimen_type || '-' }}</p>
                        </div>
                    </template>

                    <template v-slot:item.status="{ item }">
                        <v-chip :color="getStatusColor(item.status)" size="small">{{ item.status }}</v-chip>
                    </template>

                    <template v-slot:item.criticality="{ item }">
                        <v-chip
                            v-if="item.criticality && item.criticality !== 'Normal'"
                            :color="getCriticalityColor(item.criticality)"
                            size="small"
                            variant="flat"
                        >
                            <v-icon start size="x-small">{{ item.criticality === 'Critical' ? 'mdi-alert' : 'mdi-alert-circle' }}</v-icon>
                            {{ item.criticality }}
                        </v-chip>
                        <span v-else class="text-grey">Normal</span>
                    </template>

                    <template v-slot:item.actions="{ item }">
                        <!-- Workflow Actions -->
                        <v-btn
                            v-if="item.status === 'Pending'"
                            icon
                            variant="text"
                            size="small"
                            color="info"
                            @click="openCollectDialog(item)"
                            title="Collect Specimen"
                        >
                            <v-icon>mdi-test-tube</v-icon>
                        </v-btn>
                        <v-btn
                            v-if="item.status === 'Sample Collected'"
                            icon
                            variant="text"
                            size="small"
                            color="primary"
                            @click="receiveAtLab(item)"
                            title="Receive at Lab"
                        >
                            <v-icon>mdi-flask-outline</v-icon>
                        </v-btn>
                        <v-btn
                            v-if="item.status === 'Received at Lab' || item.status === 'Processing'"
                            icon
                            variant="text"
                            size="small"
                            color="warning"
                            @click="openResultDialog(item)"
                            title="Enter Result"
                        >
                            <v-icon>mdi-clipboard-text</v-icon>
                        </v-btn>
                        <v-btn
                            v-if="item.approval_status === 'Pending' && item.result_entered_at"
                            icon
                            variant="text"
                            size="small"
                            color="success"
                            @click="openApprovalDialog(item)"
                            title="Approve/Reject"
                        >
                            <v-icon>mdi-check-circle</v-icon>
                        </v-btn>

                        <!-- View/Print Actions -->
                        <v-btn icon variant="text" size="small" color="grey" :to="{ name: 'lab-reports.show', params: { id: item.id } }">
                            <v-icon>mdi-eye</v-icon>
                        </v-btn>
                        <v-btn
                            v-if="item.approval_status === 'Approved'"
                            icon
                            variant="text"
                            size="small"
                            color="success"
                            @click="printReport(item)"
                            title="Print Report"
                        >
                            <v-icon>mdi-printer</v-icon>
                        </v-btn>
                        <v-btn
                            v-if="item.approval_status === 'Approved' && !item.sms_sent"
                            icon
                            variant="text"
                            size="small"
                            color="purple"
                            @click="sendSms(item)"
                            title="Send SMS"
                        >
                            <v-icon>mdi-message-text</v-icon>
                        </v-btn>
                        <v-btn icon variant="text" size="small" color="error" @click="confirmDelete(item)">
                            <v-icon>mdi-delete</v-icon>
                        </v-btn>
                    </template>
                </v-data-table-server>
            </v-card-text>
        </v-card>

        <!-- Collect Specimen Dialog -->
        <v-dialog v-model="collectDialog" max-width="400">
            <v-card>
                <v-card-title>Collect Specimen</v-card-title>
                <v-card-text>
                    <p class="mb-3">Report: <strong>{{ selectedReport?.report_id }}</strong></p>
                    <v-select
                        v-model="specimenType"
                        label="Specimen Type"
                        :items="specimenTypes"
                    />
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="collectDialog = false">Cancel</v-btn>
                    <v-btn color="info" @click="collectSpecimen" :loading="actionLoading">Collect</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Enter Result Dialog -->
        <v-dialog v-model="resultDialog" max-width="700">
            <v-card>
                <v-card-title>Enter Test Result</v-card-title>
                <v-card-text>
                    <p class="mb-3">
                        Report: <strong>{{ selectedReport?.report_id }}</strong> |
                        Test: <strong>{{ selectedReport?.test?.name }}</strong>
                    </p>

                    <!-- Dynamic Parameters based on Test -->
                    <div v-if="selectedReport?.test?.parameters?.length">
                        <v-row v-for="(param, index) in testParameters" :key="index">
                            <v-col cols="4">
                                <v-text-field
                                    v-model="param.name"
                                    label="Parameter"
                                    disabled
                                    density="compact"
                                />
                            </v-col>
                            <v-col cols="3">
                                <v-text-field
                                    v-model="param.value"
                                    label="Result"
                                    density="compact"
                                />
                            </v-col>
                            <v-col cols="3">
                                <v-text-field
                                    v-model="param.unit"
                                    label="Unit"
                                    disabled
                                    density="compact"
                                />
                            </v-col>
                            <v-col cols="2">
                                <v-text-field
                                    v-model="param.reference"
                                    label="Ref Range"
                                    disabled
                                    density="compact"
                                />
                            </v-col>
                        </v-row>
                    </div>
                    <div v-else>
                        <v-btn size="small" color="primary" variant="tonal" @click="addParameter" class="mb-3">
                            <v-icon start>mdi-plus</v-icon>Add Parameter
                        </v-btn>
                        <v-row v-for="(param, index) in testParameters" :key="index">
                            <v-col cols="4">
                                <v-text-field
                                    v-model="param.name"
                                    label="Parameter"
                                    density="compact"
                                />
                            </v-col>
                            <v-col cols="3">
                                <v-text-field
                                    v-model="param.value"
                                    label="Result"
                                    density="compact"
                                />
                            </v-col>
                            <v-col cols="3">
                                <v-text-field
                                    v-model="param.unit"
                                    label="Unit"
                                    density="compact"
                                />
                            </v-col>
                            <v-col cols="2">
                                <v-btn icon variant="text" color="error" size="small" @click="removeParameter(index)">
                                    <v-icon>mdi-close</v-icon>
                                </v-btn>
                            </v-col>
                        </v-row>
                    </div>

                    <v-divider class="my-4" />

                    <v-select
                        v-model="resultCriticality"
                        label="Result Criticality"
                        :items="criticalities"
                        :item-props="criticalityProps"
                    />
                    <v-textarea
                        v-model="resultRemarks"
                        label="Remarks"
                        rows="2"
                    />
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="resultDialog = false">Cancel</v-btn>
                    <v-btn color="warning" @click="enterResult" :loading="actionLoading">Save Result</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Approval Dialog -->
        <v-dialog v-model="approvalDialog" max-width="500">
            <v-card>
                <v-card-title>Approve Test Result</v-card-title>
                <v-card-text>
                    <p class="mb-2">Report: <strong>{{ selectedReport?.report_id }}</strong></p>
                    <p class="mb-3">Test: <strong>{{ selectedReport?.test?.name }}</strong></p>

                    <v-chip
                        v-if="selectedReport?.criticality && selectedReport?.criticality !== 'Normal'"
                        :color="getCriticalityColor(selectedReport?.criticality)"
                        class="mb-3"
                    >
                        {{ selectedReport?.criticality }} Result
                    </v-chip>

                    <v-textarea
                        v-model="approvalRemarks"
                        label="Remarks (Required for rejection)"
                        rows="2"
                    />
                </v-card-text>
                <v-card-actions>
                    <v-btn color="error" variant="tonal" @click="rejectResult" :loading="actionLoading">
                        <v-icon start>mdi-close</v-icon>Reject
                    </v-btn>
                    <v-spacer />
                    <v-btn variant="text" @click="approvalDialog = false">Cancel</v-btn>
                    <v-btn color="success" @click="approveResult" :loading="actionLoading">
                        <v-icon start>mdi-check</v-icon>Approve
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Bulk Receive Dialog -->
        <v-dialog v-model="bulkReceiveDialog" max-width="400">
            <v-card>
                <v-card-title>Bulk Receive at Lab</v-card-title>
                <v-card-text>
                    Receive {{ selectedReports.length }} specimens at lab?
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="bulkReceiveDialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="bulkReceive" :loading="actionLoading">Receive All</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete Dialog -->
        <v-dialog v-model="deleteDialog" max-width="400">
            <v-card>
                <v-card-title>Confirm Delete</v-card-title>
                <v-card-text>Delete this lab report?</v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="deleteDialog = false">Cancel</v-btn>
                    <v-btn color="error" @click="deleteReport" :loading="deleting">Delete</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-snackbar v-model="snackbar.show" :color="snackbar.color">{{ snackbar.message }}</v-snackbar>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue';
import axios from 'axios';

const loading = ref(false);
const deleting = ref(false);
const actionLoading = ref(false);
const reports = ref([]);
const totalItems = ref(0);
const page = ref(1);
const itemsPerPage = ref(10);
const search = ref('');
const deleteDialog = ref(false);
const collectDialog = ref(false);
const resultDialog = ref(false);
const approvalDialog = ref(false);
const bulkReceiveDialog = ref(false);
const selectedReport = ref(null);
const selectedReports = ref([]);
const quickFilter = ref(null);

const specimenType = ref('Blood');
const specimenTypes = ref([]);
const testParameters = ref([]);
const resultCriticality = ref('Normal');
const resultRemarks = ref('');
const approvalRemarks = ref('');

const stats = reactive({
    pending_collection: 0,
    sample_collected: 0,
    received_at_lab: 0,
    processing: 0,
    awaiting_approval: 0,
    completed_today: 0,
    critical_reports: 0,
    abnormal_reports: 0,
    pending_sms: 0,
});

const statuses = ['Pending', 'Sample Collected', 'Received at Lab', 'Processing', 'Completed', 'Delivered'];
const criticalities = ['Normal', 'Abnormal', 'Critical'];
const filters = reactive({ status: null, criticality: null, date: '' });
const snackbar = reactive({ show: false, message: '', color: 'success' });

const headers = [
    { title: 'Report ID', key: 'report_id' },
    { title: 'Patient', key: 'patient' },
    { title: 'Test', key: 'test' },
    { title: 'Sample Date', key: 'sample_date' },
    { title: 'Status', key: 'status' },
    { title: 'Criticality', key: 'criticality' },
    { title: 'Actions', key: 'actions', sortable: false, width: 250 },
];

const getStatusColor = (status) => ({
    Pending: 'grey',
    'Sample Collected': 'info',
    'Received at Lab': 'primary',
    Processing: 'warning',
    Completed: 'success',
    Delivered: 'success',
}[status] || 'grey');

const getCriticalityColor = (criticality) => ({
    Critical: 'error',
    Abnormal: 'warning',
    Normal: 'success',
}[criticality] || 'grey');

const criticalityProps = (item) => ({
    subtitle: item === 'Critical' ? 'Urgent attention required' : item === 'Abnormal' ? 'Outside normal range' : 'Within normal range',
});

let searchTimeout = null;
const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => { page.value = 1; fetchReports(); }, 500);
};

// Watch quick filter
watch(quickFilter, (val) => {
    if (val === 'awaiting') {
        filters.status = null;
        filters.criticality = null;
        fetchReports({ awaiting_approval: true });
    } else if (val === 'critical') {
        filters.status = null;
        filters.criticality = 'Critical';
        fetchReports();
    } else {
        fetchReports();
    }
});

const fetchStats = async () => {
    try {
        const response = await axios.get('/api/lab-reports/stats');
        Object.assign(stats, response.data.data);
    } catch (error) {
        console.error('Failed to fetch stats:', error);
    }
};

const fetchSpecimenTypes = async () => {
    try {
        const response = await axios.get('/api/lab-reports/specimen-types');
        specimenTypes.value = response.data.data || [];
    } catch (error) {
        console.error('Failed to fetch specimen types:', error);
    }
};

const fetchReports = async (extraParams = {}) => {
    loading.value = true;
    try {
        const response = await axios.get('/api/lab-reports', {
            params: {
                page: page.value,
                per_page: itemsPerPage.value,
                search: search.value,
                ...filters,
                ...extraParams,
            },
        });
        reports.value = response.data.data || [];
        totalItems.value = response.data.total || 0;
    } catch (error) {
        console.error('Failed to fetch reports:', error);
    }
    loading.value = false;
};

// Specimen Collection
const openCollectDialog = (report) => {
    selectedReport.value = report;
    specimenType.value = report.specimen_type || 'Blood';
    collectDialog.value = true;
};

const collectSpecimen = async () => {
    actionLoading.value = true;
    try {
        await axios.post(`/api/lab-reports/${selectedReport.value.id}/collect-specimen`, {
            specimen_type: specimenType.value,
        });
        snackbar.message = 'Specimen collected successfully';
        snackbar.color = 'success';
        snackbar.show = true;
        collectDialog.value = false;
        fetchReports();
        fetchStats();
    } catch (error) {
        snackbar.message = 'Failed to collect specimen';
        snackbar.color = 'error';
        snackbar.show = true;
    }
    actionLoading.value = false;
};

// Receive at Lab
const receiveAtLab = async (report) => {
    actionLoading.value = true;
    try {
        await axios.post(`/api/lab-reports/${report.id}/receive-at-lab`);
        snackbar.message = 'Specimen received at lab';
        snackbar.color = 'success';
        snackbar.show = true;
        fetchReports();
        fetchStats();
    } catch (error) {
        snackbar.message = 'Failed to receive specimen';
        snackbar.color = 'error';
        snackbar.show = true;
    }
    actionLoading.value = false;
};

// Enter Result
const openResultDialog = (report) => {
    selectedReport.value = report;
    resultCriticality.value = report.criticality || 'Normal';
    resultRemarks.value = report.remarks || '';

    // Load test parameters
    if (report.test?.parameters?.length) {
        testParameters.value = report.test.parameters.map(p => ({
            name: p.name,
            value: report.parameters?.find(rp => rp.name === p.name)?.value || '',
            unit: p.unit,
            reference: p.reference_range,
        }));
    } else if (report.parameters?.length) {
        testParameters.value = report.parameters;
    } else {
        testParameters.value = [{ name: '', value: '', unit: '', reference: '' }];
    }

    resultDialog.value = true;
};

const addParameter = () => {
    testParameters.value.push({ name: '', value: '', unit: '', reference: '' });
};

const removeParameter = (index) => {
    testParameters.value.splice(index, 1);
};

const enterResult = async () => {
    actionLoading.value = true;
    try {
        await axios.post(`/api/lab-reports/${selectedReport.value.id}/enter-result`, {
            parameters: testParameters.value,
            criticality: resultCriticality.value,
            remarks: resultRemarks.value,
        });
        snackbar.message = 'Result entered successfully';
        snackbar.color = 'success';
        snackbar.show = true;
        resultDialog.value = false;
        fetchReports();
        fetchStats();
    } catch (error) {
        snackbar.message = 'Failed to enter result';
        snackbar.color = 'error';
        snackbar.show = true;
    }
    actionLoading.value = false;
};

// Approval
const openApprovalDialog = (report) => {
    selectedReport.value = report;
    approvalRemarks.value = '';
    approvalDialog.value = true;
};

const approveResult = async () => {
    actionLoading.value = true;
    try {
        await axios.post(`/api/lab-reports/${selectedReport.value.id}/approve`, {
            remarks: approvalRemarks.value,
        });
        snackbar.message = 'Result approved successfully';
        snackbar.color = 'success';
        snackbar.show = true;
        approvalDialog.value = false;
        fetchReports();
        fetchStats();
    } catch (error) {
        snackbar.message = 'Failed to approve result';
        snackbar.color = 'error';
        snackbar.show = true;
    }
    actionLoading.value = false;
};

const rejectResult = async () => {
    if (!approvalRemarks.value) {
        snackbar.message = 'Please provide remarks for rejection';
        snackbar.color = 'warning';
        snackbar.show = true;
        return;
    }

    actionLoading.value = true;
    try {
        await axios.post(`/api/lab-reports/${selectedReport.value.id}/reject`, {
            remarks: approvalRemarks.value,
        });
        snackbar.message = 'Result rejected';
        snackbar.color = 'info';
        snackbar.show = true;
        approvalDialog.value = false;
        fetchReports();
        fetchStats();
    } catch (error) {
        snackbar.message = 'Failed to reject result';
        snackbar.color = 'error';
        snackbar.show = true;
    }
    actionLoading.value = false;
};

// Bulk Receive
const bulkReceive = async () => {
    actionLoading.value = true;
    try {
        await axios.post('/api/lab-reports/bulk-receive', {
            report_ids: selectedReports.value,
        });
        snackbar.message = 'Specimens received at lab';
        snackbar.color = 'success';
        snackbar.show = true;
        bulkReceiveDialog.value = false;
        selectedReports.value = [];
        fetchReports();
        fetchStats();
    } catch (error) {
        snackbar.message = 'Failed to receive specimens';
        snackbar.color = 'error';
        snackbar.show = true;
    }
    actionLoading.value = false;
};

// Send SMS
const sendSms = async (report) => {
    try {
        await axios.post(`/api/lab-reports/${report.id}/send-sms`);
        snackbar.message = 'SMS sent successfully';
        snackbar.color = 'success';
        snackbar.show = true;
        fetchReports();
    } catch (error) {
        snackbar.message = error.response?.data?.message || 'Failed to send SMS';
        snackbar.color = 'error';
        snackbar.show = true;
    }
};

const printReport = (report) => {
    window.open(`/lab-reports/${report.id}/print`, '_blank');
};

const confirmDelete = (report) => {
    selectedReport.value = report;
    deleteDialog.value = true;
};

const deleteReport = async () => {
    deleting.value = true;
    try {
        await axios.delete(`/api/lab-reports/${selectedReport.value.id}`);
        snackbar.message = 'Report deleted';
        snackbar.color = 'success';
        snackbar.show = true;
        deleteDialog.value = false;
        fetchReports();
        fetchStats();
    } catch (error) {
        snackbar.message = 'Failed to delete';
        snackbar.color = 'error';
        snackbar.show = true;
    }
    deleting.value = false;
};

onMounted(() => {
    fetchReports();
    fetchStats();
    fetchSpecimenTypes();
});
</script>
