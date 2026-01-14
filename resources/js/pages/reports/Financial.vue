<template>
    <div>
        <v-card>
            <v-card-title class="d-flex align-center">
                <v-icon class="mr-2">mdi-chart-bar</v-icon>
                Financial Reports
            </v-card-title>

            <v-card-text>
                <!-- Filters -->
                <v-row class="mb-4">
                    <v-col cols="12" md="3">
                        <v-select v-model="reportType" label="Report Type" :items="reportTypes" @update:model-value="fetchReport" />
                    </v-col>
                    <v-col cols="12" md="2">
                        <v-text-field v-model="filters.from_date" label="From Date" type="date" @update:model-value="fetchReport" />
                    </v-col>
                    <v-col cols="12" md="2">
                        <v-text-field v-model="filters.to_date" label="To Date" type="date" @update:model-value="fetchReport" />
                    </v-col>
                    <v-col cols="12" md="2">
                        <v-btn color="primary" @click="fetchReport" :loading="loading">
                            <v-icon start>mdi-magnify</v-icon>Generate
                        </v-btn>
                    </v-col>
                    <v-col cols="12" md="3" class="text-end">
                        <v-btn color="success" variant="outlined" @click="exportReport">
                            <v-icon start>mdi-file-excel</v-icon>Export
                        </v-btn>
                    </v-col>
                </v-row>

                <!-- Summary Cards -->
                <v-row class="mb-4">
                    <v-col cols="12" sm="6" md="3">
                        <v-card color="primary" variant="tonal">
                            <v-card-text class="text-center">
                                <v-icon size="32" class="mb-2">mdi-cash-multiple</v-icon>
                                <p class="text-body-2 mb-1">Total Revenue</p>
                                <p class="text-h5 font-weight-bold">{{ summary.totalRevenue }}</p>
                            </v-card-text>
                        </v-card>
                    </v-col>
                    <v-col cols="12" sm="6" md="3">
                        <v-card color="success" variant="tonal">
                            <v-card-text class="text-center">
                                <v-icon size="32" class="mb-2">mdi-cash-check</v-icon>
                                <p class="text-body-2 mb-1">Total Collected</p>
                                <p class="text-h5 font-weight-bold">{{ summary.totalCollected }}</p>
                            </v-card-text>
                        </v-card>
                    </v-col>
                    <v-col cols="12" sm="6" md="3">
                        <v-card color="warning" variant="tonal">
                            <v-card-text class="text-center">
                                <v-icon size="32" class="mb-2">mdi-cash-minus</v-icon>
                                <p class="text-body-2 mb-1">Total Due</p>
                                <p class="text-h5 font-weight-bold">{{ summary.totalDue }}</p>
                            </v-card-text>
                        </v-card>
                    </v-col>
                    <v-col cols="12" sm="6" md="3">
                        <v-card color="info" variant="tonal">
                            <v-card-text class="text-center">
                                <v-icon size="32" class="mb-2">mdi-receipt</v-icon>
                                <p class="text-body-2 mb-1">Total Invoices</p>
                                <p class="text-h5 font-weight-bold">{{ summary.totalInvoices }}</p>
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>

                <!-- Data Table -->
                <v-card variant="outlined">
                    <v-data-table :headers="headers" :items="reportData" :loading="loading">
                        <template v-slot:item.total="{ item }">
                            <span class="font-weight-medium">{{ item.total }}</span>
                        </template>
                        <template v-slot:item.paid="{ item }">
                            <span class="text-success">{{ item.paid }}</span>
                        </template>
                        <template v-slot:item.due="{ item }">
                            <span :class="item.due > 0 ? 'text-error' : 'text-success'">{{ item.due }}</span>
                        </template>
                    </v-data-table>
                </v-card>
            </v-card-text>
        </v-card>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

const loading = ref(false);
const reportType = ref('daily');
const reportData = ref([]);

const reportTypes = [
    { title: 'Daily Report', value: 'daily' },
    { title: 'Monthly Report', value: 'monthly' },
    { title: 'Doctor-wise Report', value: 'doctor' },
    { title: 'Test-wise Report', value: 'test' },
];

const filters = reactive({
    from_date: new Date().toISOString().split('T')[0],
    to_date: new Date().toISOString().split('T')[0],
});

const summary = reactive({
    totalRevenue: 0,
    totalCollected: 0,
    totalDue: 0,
    totalInvoices: 0,
});

const headers = ref([
    { title: 'Date', key: 'date' },
    { title: 'Invoices', key: 'invoices' },
    { title: 'Total', key: 'total', align: 'end' },
    { title: 'Paid', key: 'paid', align: 'end' },
    { title: 'Due', key: 'due', align: 'end' },
]);

const updateHeaders = () => {
    if (reportType.value === 'doctor') {
        headers.value = [
            { title: 'Doctor', key: 'doctor' },
            { title: 'Patients', key: 'patients' },
            { title: 'Total', key: 'total', align: 'end' },
            { title: 'Commission', key: 'commission', align: 'end' },
        ];
    } else if (reportType.value === 'test') {
        headers.value = [
            { title: 'Test', key: 'test' },
            { title: 'Count', key: 'count' },
            { title: 'Revenue', key: 'total', align: 'end' },
        ];
    } else {
        headers.value = [
            { title: 'Date', key: 'date' },
            { title: 'Invoices', key: 'invoices' },
            { title: 'Total', key: 'total', align: 'end' },
            { title: 'Paid', key: 'paid', align: 'end' },
            { title: 'Due', key: 'due', align: 'end' },
        ];
    }
};

const fetchReport = async () => {
    updateHeaders();
    loading.value = true;
    try {
        const response = await axios.get('/api/reports/financial', {
            params: { type: reportType.value, ...filters },
        });
        reportData.value = response.data.data || [];
        summary.totalRevenue = response.data.summary?.totalRevenue || 0;
        summary.totalCollected = response.data.summary?.totalCollected || 0;
        summary.totalDue = response.data.summary?.totalDue || 0;
        summary.totalInvoices = response.data.summary?.totalInvoices || 0;
    } catch (error) { console.error('Failed to fetch report:', error); }
    loading.value = false;
};

const exportReport = () => {
    window.open(`/api/reports/financial/export?type=${reportType.value}&from_date=${filters.from_date}&to_date=${filters.to_date}`, '_blank');
};

onMounted(() => fetchReport());
</script>
