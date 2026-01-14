<template>
    <div>
        <v-tabs v-model="activeTab" color="primary" class="mb-4" show-arrows>
            <v-tab value="daily-sales"><v-icon start size="small" class="d-none d-sm-inline">mdi-calendar-today</v-icon>Daily Sales</v-tab>
            <v-tab value="bill-collection"><v-icon start size="small" class="d-none d-sm-inline">mdi-cash-register</v-icon>Bill Collection</v-tab>
            <v-tab value="cashier"><v-icon start size="small" class="d-none d-sm-inline">mdi-account-cash</v-icon>Cashier</v-tab>
            <v-tab value="patient-due"><v-icon start size="small" class="d-none d-sm-inline">mdi-account-alert</v-icon>Patient Due</v-tab>
            <v-tab value="performance"><v-icon start size="small" class="d-none d-sm-inline">mdi-chart-line</v-icon>Performance</v-tab>
        </v-tabs>

        <v-window v-model="activeTab">
            <!-- Daily Sales Report -->
            <v-window-item value="daily-sales">
                <v-card>
                    <v-card-title class="d-flex align-center justify-space-between">
                        <span><v-icon class="mr-2">mdi-calendar-today</v-icon>Daily Sales Report</span>
                        <v-btn color="success" variant="outlined" size="small" @click="printReport('daily')">
                            <v-icon start>mdi-printer</v-icon>Print
                        </v-btn>
                    </v-card-title>
                    <v-card-text>
                        <v-row class="mb-4">
                            <v-col cols="12" md="3">
                                <v-text-field v-model="dailySalesDate" label="Date" type="date" hide-details @update:model-value="fetchDailySales" />
                            </v-col>
                            <v-col cols="12" md="2">
                                <v-btn color="primary" @click="fetchDailySales" :loading="loadingDailySales">View Report</v-btn>
                            </v-col>
                        </v-row>

                        <!-- Summary -->
                        <v-row class="mb-4" v-if="dailySalesData.summary">
                            <v-col cols="6" md="2">
                                <v-card color="primary" variant="tonal">
                                    <v-card-text class="text-center pa-3">
                                        <div class="text-h6">{{ dailySalesData.summary.total_invoices }}</div>
                                        <div class="text-caption">Invoices</div>
                                    </v-card-text>
                                </v-card>
                            </v-col>
                            <v-col cols="6" md="2">
                                <v-card color="success" variant="tonal">
                                    <v-card-text class="text-center pa-3">
                                        <div class="text-h6">{{ formatNumber(dailySalesData.summary.total_sales) }}</div>
                                        <div class="text-caption">Total Sales</div>
                                    </v-card-text>
                                </v-card>
                            </v-col>
                            <v-col cols="6" md="2">
                                <v-card color="info" variant="tonal">
                                    <v-card-text class="text-center pa-3">
                                        <div class="text-h6">{{ formatNumber(dailySalesData.summary.total_collected) }}</div>
                                        <div class="text-caption">Collected</div>
                                    </v-card-text>
                                </v-card>
                            </v-col>
                            <v-col cols="6" md="2">
                                <v-card color="warning" variant="tonal">
                                    <v-card-text class="text-center pa-3">
                                        <div class="text-h6">{{ formatNumber(dailySalesData.summary.total_due) }}</div>
                                        <div class="text-caption">Due</div>
                                    </v-card-text>
                                </v-card>
                            </v-col>
                            <v-col cols="6" md="2">
                                <v-card color="secondary" variant="tonal">
                                    <v-card-text class="text-center pa-3">
                                        <div class="text-h6">{{ formatNumber(dailySalesData.summary.total_discount) }}</div>
                                        <div class="text-caption">Discount</div>
                                    </v-card-text>
                                </v-card>
                            </v-col>
                        </v-row>

                        <!-- Invoices Table -->
                        <v-data-table :headers="dailySalesHeaders" :items="dailySalesData.invoices || []" :loading="loadingDailySales" density="compact">
                            <template v-slot:item.total="{ item }">{{ formatNumber(item.total) }}</template>
                            <template v-slot:item.paid="{ item }"><span class="text-success">{{ formatNumber(item.paid) }}</span></template>
                            <template v-slot:item.due="{ item }"><span :class="item.due > 0 ? 'text-error' : ''">{{ formatNumber(item.due) }}</span></template>
                            <template v-slot:item.status="{ item }">
                                <v-chip :color="item.status === 'Paid' ? 'success' : item.status === 'Partial' ? 'warning' : 'error'" size="x-small">{{ item.status }}</v-chip>
                            </template>
                        </v-data-table>

                        <!-- Payment Methods & Hourly -->
                        <v-row class="mt-4">
                            <v-col cols="12" md="6">
                                <v-card variant="outlined">
                                    <v-card-title class="text-subtitle-1">By Payment Method</v-card-title>
                                    <v-card-text>
                                        <v-table density="compact">
                                            <tbody>
                                                <tr v-for="pm in dailySalesData.payment_methods" :key="pm.payment_method">
                                                    <td>{{ pm.payment_method }}</td>
                                                    <td class="text-end">{{ pm.count }}</td>
                                                    <td class="text-end font-weight-bold">{{ formatNumber(pm.total) }}</td>
                                                </tr>
                                            </tbody>
                                        </v-table>
                                    </v-card-text>
                                </v-card>
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-card variant="outlined">
                                    <v-card-title class="text-subtitle-1">Hourly Sales</v-card-title>
                                    <v-card-text>
                                        <div class="d-flex align-end justify-space-around" style="height: 100px;">
                                            <div v-for="h in dailySalesData.hourly_breakdown" :key="h.hour" class="text-center" style="width: 30px;">
                                                <div class="bg-primary rounded-t" :style="{ height: getHourlyHeight(h.total) + 'px', width: '20px', margin: '0 auto' }"></div>
                                                <div class="text-caption">{{ h.hour }}</div>
                                            </div>
                                        </div>
                                    </v-card-text>
                                </v-card>
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>
            </v-window-item>

            <!-- Bill Collection Report -->
            <v-window-item value="bill-collection">
                <v-card>
                    <v-card-title><v-icon class="mr-2">mdi-cash-register</v-icon>Bill Collection Report</v-card-title>
                    <v-card-text>
                        <v-row class="mb-4">
                            <v-col cols="12" md="3">
                                <v-text-field v-model="billCollectionFilters.from_date" label="From Date" type="date" hide-details />
                            </v-col>
                            <v-col cols="12" md="3">
                                <v-text-field v-model="billCollectionFilters.to_date" label="To Date" type="date" hide-details />
                            </v-col>
                            <v-col cols="12" md="2">
                                <v-btn color="primary" @click="fetchBillCollection" :loading="loadingBillCollection">Generate</v-btn>
                            </v-col>
                        </v-row>

                        <v-row class="mb-4">
                            <v-col cols="12" sm="4">
                                <v-card color="success" variant="tonal">
                                    <v-card-text class="text-center">
                                        <div class="text-h5">{{ formatNumber(billCollectionData.summary?.total_collected) }}</div>
                                        <div class="text-caption">Total Collected</div>
                                    </v-card-text>
                                </v-card>
                            </v-col>
                            <v-col cols="6" sm="4">
                                <v-card color="info" variant="tonal">
                                    <v-card-text class="text-center">
                                        <div class="text-h5">{{ billCollectionData.summary?.total_transactions }}</div>
                                        <div class="text-caption">Transactions</div>
                                    </v-card-text>
                                </v-card>
                            </v-col>
                            <v-col cols="6" sm="4">
                                <v-card color="primary" variant="tonal">
                                    <v-card-text class="text-center">
                                        <div class="text-h5">{{ formatNumber(billCollectionData.summary?.average_per_day) }}</div>
                                        <div class="text-caption">Daily Average</div>
                                    </v-card-text>
                                </v-card>
                            </v-col>
                        </v-row>

                        <v-row>
                            <v-col cols="12" md="6">
                                <v-card variant="outlined">
                                    <v-card-title class="text-subtitle-1">Daily Collection</v-card-title>
                                    <v-data-table :headers="billCollectionHeaders" :items="billCollectionData.daily || []" density="compact">
                                        <template v-slot:item.total="{ item }">{{ formatNumber(item.total) }}</template>
                                    </v-data-table>
                                </v-card>
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-card variant="outlined">
                                    <v-card-title class="text-subtitle-1">By Payment Method</v-card-title>
                                    <v-data-table :headers="[{title:'Method',key:'payment_method'},{title:'Transactions',key:'transactions'},{title:'Total',key:'total',align:'end'}]" :items="billCollectionData.by_method || []" density="compact">
                                        <template v-slot:item.total="{ item }">{{ formatNumber(item.total) }}</template>
                                    </v-data-table>
                                </v-card>
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>
            </v-window-item>

            <!-- Cashier Report -->
            <v-window-item value="cashier">
                <v-card>
                    <v-card-title><v-icon class="mr-2">mdi-account-cash</v-icon>Cashier Report</v-card-title>
                    <v-card-text>
                        <v-row class="mb-4">
                            <v-col cols="12" md="3">
                                <v-text-field v-model="cashierFilters.from_date" label="From Date" type="date" hide-details />
                            </v-col>
                            <v-col cols="12" md="3">
                                <v-text-field v-model="cashierFilters.to_date" label="To Date" type="date" hide-details />
                            </v-col>
                            <v-col cols="12" md="2">
                                <v-btn color="primary" @click="fetchCashierReport" :loading="loadingCashier">Generate</v-btn>
                            </v-col>
                        </v-row>

                        <v-data-table :headers="cashierHeaders" :items="cashierData.cashiers || []" :loading="loadingCashier">
                            <template v-slot:item.total_collected="{ item }">
                                <span class="text-success font-weight-bold">{{ formatNumber(item.total_collected) }}</span>
                            </template>
                            <template v-slot:item.avg_transaction="{ item }">{{ formatNumber(item.avg_transaction) }}</template>
                        </v-data-table>
                    </v-card-text>
                </v-card>
            </v-window-item>

            <!-- Patient Due Report -->
            <v-window-item value="patient-due">
                <v-card>
                    <v-card-title><v-icon class="mr-2">mdi-account-alert</v-icon>Patient Due Report</v-card-title>
                    <v-card-text>
                        <v-row class="mb-4">
                            <v-col cols="12" md="3">
                                <v-text-field v-model.number="patientDueMinAmount" label="Minimum Due Amount" type="number" hide-details />
                            </v-col>
                            <v-col cols="12" md="2">
                                <v-btn color="primary" @click="fetchPatientDue" :loading="loadingPatientDue">Generate</v-btn>
                            </v-col>
                        </v-row>

                        <!-- Summary -->
                        <v-row class="mb-4" v-if="patientDueData.summary">
                            <v-col cols="6" md="3">
                                <v-card color="primary" variant="tonal">
                                    <v-card-text class="text-center">
                                        <div class="text-h5">{{ patientDueData.summary.total_patients }}</div>
                                        <div class="text-caption">Patients with Due</div>
                                    </v-card-text>
                                </v-card>
                            </v-col>
                            <v-col cols="6" md="3">
                                <v-card color="error" variant="tonal">
                                    <v-card-text class="text-center">
                                        <div class="text-h5">{{ formatNumber(patientDueData.summary.total_due) }}</div>
                                        <div class="text-caption">Total Due</div>
                                    </v-card-text>
                                </v-card>
                            </v-col>
                            <v-col cols="6" md="3">
                                <v-card color="warning" variant="tonal">
                                    <v-card-text class="text-center">
                                        <div class="text-h5">{{ patientDueData.summary.overdue_patients }}</div>
                                        <div class="text-caption">Overdue Patients</div>
                                    </v-card-text>
                                </v-card>
                            </v-col>
                            <v-col cols="6" md="3">
                                <v-card color="error" variant="tonal">
                                    <v-card-text class="text-center">
                                        <div class="text-h5">{{ formatNumber(patientDueData.summary.overdue_amount) }}</div>
                                        <div class="text-caption">Overdue Amount</div>
                                    </v-card-text>
                                </v-card>
                            </v-col>
                        </v-row>

                        <!-- Age Breakdown -->
                        <v-row class="mb-4" v-if="patientDueData.age_breakdown">
                            <v-col cols="12">
                                <v-card variant="outlined">
                                    <v-card-title class="text-subtitle-1">Due by Age</v-card-title>
                                    <v-card-text>
                                        <v-row>
                                            <v-col v-for="(value, key) in patientDueData.age_breakdown" :key="key" cols="6" md="3">
                                                <div class="d-flex justify-space-between">
                                                    <span>{{ key }}</span>
                                                    <span class="font-weight-bold">{{ formatNumber(value) }}</span>
                                                </div>
                                            </v-col>
                                        </v-row>
                                    </v-card-text>
                                </v-card>
                            </v-col>
                        </v-row>

                        <v-data-table :headers="patientDueHeaders" :items="patientDueData.patients || []" :loading="loadingPatientDue">
                            <template v-slot:item.total_due="{ item }">
                                <span class="text-error font-weight-bold">{{ formatNumber(item.total_due) }}</span>
                            </template>
                            <template v-slot:item.total_billed="{ item }">{{ formatNumber(item.total_billed) }}</template>
                            <template v-slot:item.is_overdue="{ item }">
                                <v-chip v-if="item.is_overdue" color="error" size="x-small">{{ item.days_overdue }} days</v-chip>
                                <span v-else>-</span>
                            </template>
                        </v-data-table>
                    </v-card-text>
                </v-card>
            </v-window-item>

            <!-- Performance Report -->
            <v-window-item value="performance">
                <v-card>
                    <v-card-title><v-icon class="mr-2">mdi-chart-line</v-icon>Performance Report</v-card-title>
                    <v-card-text>
                        <v-row class="mb-4">
                            <v-col cols="12" md="3">
                                <v-text-field v-model="performanceFilters.from_date" label="From Date" type="date" hide-details />
                            </v-col>
                            <v-col cols="12" md="3">
                                <v-text-field v-model="performanceFilters.to_date" label="To Date" type="date" hide-details />
                            </v-col>
                            <v-col cols="12" md="2">
                                <v-btn color="primary" @click="fetchPerformance" :loading="loadingPerformance">Generate</v-btn>
                            </v-col>
                        </v-row>

                        <!-- Collection Efficiency -->
                        <v-row class="mb-4" v-if="performanceData.collection_efficiency">
                            <v-col cols="12">
                                <v-card variant="outlined">
                                    <v-card-title class="text-subtitle-1">Collection Efficiency</v-card-title>
                                    <v-card-text>
                                        <v-progress-linear :model-value="performanceData.collection_efficiency.collection_rate" color="success" height="25" class="mb-2">
                                            <template v-slot:default>{{ performanceData.collection_efficiency.collection_rate }}%</template>
                                        </v-progress-linear>
                                        <div class="d-flex justify-space-between">
                                            <span>Billed: {{ formatNumber(performanceData.collection_efficiency.total_billed) }}</span>
                                            <span>Collected: {{ formatNumber(performanceData.collection_efficiency.total_collected) }}</span>
                                        </div>
                                    </v-card-text>
                                </v-card>
                            </v-col>
                        </v-row>

                        <!-- Doctor Performance -->
                        <v-row>
                            <v-col cols="12" md="6">
                                <v-card variant="outlined">
                                    <v-card-title class="text-subtitle-1">Doctor Performance</v-card-title>
                                    <v-data-table :headers="doctorPerfHeaders" :items="performanceData.doctor_performance || []" density="compact">
                                        <template v-slot:item.doctor="{ item }">{{ item.doctor?.name }}</template>
                                        <template v-slot:item.total_revenue="{ item }">{{ formatNumber(item.total_revenue) }}</template>
                                        <template v-slot:item.total_commission="{ item }">{{ formatNumber(item.total_commission) }}</template>
                                    </v-data-table>
                                </v-card>
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-card variant="outlined">
                                    <v-card-title class="text-subtitle-1">Test Category Performance</v-card-title>
                                    <v-data-table :headers="testPerfHeaders" :items="performanceData.test_performance || []" density="compact">
                                        <template v-slot:item.total_revenue="{ item }">{{ formatNumber(item.total_revenue) }}</template>
                                    </v-data-table>
                                </v-card>
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>
            </v-window-item>
        </v-window>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

const activeTab = ref('daily-sales');

// Daily Sales
const dailySalesDate = ref(new Date().toISOString().substr(0, 10));
const loadingDailySales = ref(false);
const dailySalesData = ref({});
const dailySalesHeaders = [
    { title: 'Invoice', key: 'invoice_no' },
    { title: 'Time', key: 'time' },
    { title: 'Patient', key: 'patient_name' },
    { title: 'Doctor', key: 'doctor_name' },
    { title: 'Total', key: 'total', align: 'end' },
    { title: 'Paid', key: 'paid', align: 'end' },
    { title: 'Due', key: 'due', align: 'end' },
    { title: 'Status', key: 'status' },
];

// Bill Collection
const billCollectionFilters = reactive({
    from_date: new Date().toISOString().substr(0, 10).replace(/-\d{2}$/, '-01'),
    to_date: new Date().toISOString().substr(0, 10),
});
const loadingBillCollection = ref(false);
const billCollectionData = ref({});
const billCollectionHeaders = [
    { title: 'Date', key: 'date' },
    { title: 'Transactions', key: 'transactions' },
    { title: 'Total', key: 'total', align: 'end' },
];

// Cashier
const cashierFilters = reactive({
    from_date: new Date().toISOString().substr(0, 10),
    to_date: new Date().toISOString().substr(0, 10),
});
const loadingCashier = ref(false);
const cashierData = ref({});
const cashierHeaders = [
    { title: 'Cashier', key: 'cashier_name' },
    { title: 'Transactions', key: 'transactions' },
    { title: 'Total Collected', key: 'total_collected', align: 'end' },
    { title: 'Avg Transaction', key: 'avg_transaction', align: 'end' },
];

// Patient Due
const patientDueMinAmount = ref(0);
const loadingPatientDue = ref(false);
const patientDueData = ref({});
const patientDueHeaders = [
    { title: 'Patient ID', key: 'patient_code' },
    { title: 'Name', key: 'patient_name' },
    { title: 'Phone', key: 'patient_phone' },
    { title: 'Invoices', key: 'invoice_count' },
    { title: 'Total Billed', key: 'total_billed', align: 'end' },
    { title: 'Total Due', key: 'total_due', align: 'end' },
    { title: 'Overdue', key: 'is_overdue' },
];

// Performance
const performanceFilters = reactive({
    from_date: new Date().toISOString().substr(0, 10).replace(/-\d{2}$/, '-01'),
    to_date: new Date().toISOString().substr(0, 10),
});
const loadingPerformance = ref(false);
const performanceData = ref({});
const doctorPerfHeaders = [
    { title: 'Doctor', key: 'doctor' },
    { title: 'Invoices', key: 'total_invoices' },
    { title: 'Patients', key: 'unique_patients' },
    { title: 'Revenue', key: 'total_revenue', align: 'end' },
    { title: 'Commission', key: 'total_commission', align: 'end' },
];
const testPerfHeaders = [
    { title: 'Category', key: 'category' },
    { title: 'Count', key: 'test_count' },
    { title: 'Revenue', key: 'total_revenue', align: 'end' },
];

const formatNumber = (num) => Number(num || 0).toLocaleString('en-BD');

const getHourlyHeight = (total) => {
    const maxTotal = Math.max(...(dailySalesData.value.hourly_breakdown || []).map(h => parseFloat(h.total) || 0));
    if (maxTotal === 0) return 0;
    return Math.max(5, (parseFloat(total) / maxTotal) * 80);
};

const fetchDailySales = async () => {
    loadingDailySales.value = true;
    try {
        const response = await axios.get('/api/reports/daily-sales', { params: { date: dailySalesDate.value } });
        dailySalesData.value = response.data;
    } catch (error) { console.error('Failed:', error); }
    loadingDailySales.value = false;
};

const fetchBillCollection = async () => {
    loadingBillCollection.value = true;
    try {
        const response = await axios.get('/api/reports/bill-collection', { params: billCollectionFilters });
        billCollectionData.value = response.data;
    } catch (error) { console.error('Failed:', error); }
    loadingBillCollection.value = false;
};

const fetchCashierReport = async () => {
    loadingCashier.value = true;
    try {
        const response = await axios.get('/api/reports/cashier-wise', { params: cashierFilters });
        cashierData.value = response.data;
    } catch (error) { console.error('Failed:', error); }
    loadingCashier.value = false;
};

const fetchPatientDue = async () => {
    loadingPatientDue.value = true;
    try {
        const response = await axios.get('/api/reports/patient-due', { params: { min_due: patientDueMinAmount.value } });
        patientDueData.value = response.data;
    } catch (error) { console.error('Failed:', error); }
    loadingPatientDue.value = false;
};

const fetchPerformance = async () => {
    loadingPerformance.value = true;
    try {
        const response = await axios.get('/api/reports/performance', { params: performanceFilters });
        performanceData.value = response.data;
    } catch (error) { console.error('Failed:', error); }
    loadingPerformance.value = false;
};

const printReport = (type) => {
    window.print();
};

onMounted(() => {
    fetchDailySales();
});
</script>
