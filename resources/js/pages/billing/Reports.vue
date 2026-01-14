<template>
    <div>
        <v-tabs v-model="activeTab" color="primary" class="mb-4">
            <v-tab value="billing">Billing Report</v-tab>
            <v-tab value="commission">Commission Report</v-tab>
        </v-tabs>

        <!-- Date Range Filter -->
        <v-card class="mb-4">
            <v-card-text>
                <v-row align="center">
                    <v-col cols="12" md="3">
                        <v-text-field v-model="dateFrom" label="From Date" type="date" hide-details />
                    </v-col>
                    <v-col cols="12" md="3">
                        <v-text-field v-model="dateTo" label="To Date" type="date" hide-details />
                    </v-col>
                    <v-col cols="12" md="3">
                        <v-btn-group variant="outlined">
                            <v-btn size="small" @click="setDateRange('today')">Today</v-btn>
                            <v-btn size="small" @click="setDateRange('week')">This Week</v-btn>
                            <v-btn size="small" @click="setDateRange('month')">This Month</v-btn>
                        </v-btn-group>
                    </v-col>
                    <v-col cols="12" md="3">
                        <v-btn color="primary" block @click="fetchReports">
                            <v-icon start>mdi-file-chart</v-icon>
                            View Report
                        </v-btn>
                    </v-col>
                </v-row>
            </v-card-text>
        </v-card>

        <v-window v-model="activeTab">
            <!-- Billing Report Tab -->
            <v-window-item value="billing">
                <v-row v-if="billingReport">
                    <!-- Summary Cards -->
                    <v-col cols="12" md="3">
                        <v-card color="primary" variant="tonal">
                            <v-card-text class="text-center">
                                <div class="text-h5">{{ billingReport.summary?.total_invoices || 0 }}</div>
                                <div class="text-caption">Total Invoices</div>
                            </v-card-text>
                        </v-card>
                    </v-col>
                    <v-col cols="12" md="3">
                        <v-card color="success" variant="tonal">
                            <v-card-text class="text-center">
                                <div class="text-h5">{{ formatNumber(billingReport.summary?.total_amount) }}</div>
                                <div class="text-caption">Total Billed</div>
                            </v-card-text>
                        </v-card>
                    </v-col>
                    <v-col cols="12" md="3">
                        <v-card color="info" variant="tonal">
                            <v-card-text class="text-center">
                                <div class="text-h5">{{ formatNumber(billingReport.summary?.total_paid) }}</div>
                                <div class="text-caption">Collected</div>
                            </v-card-text>
                        </v-card>
                    </v-col>
                    <v-col cols="12" md="3">
                        <v-card color="error" variant="tonal">
                            <v-card-text class="text-center">
                                <div class="text-h5">{{ formatNumber(billingReport.summary?.total_due) }}</div>
                                <div class="text-caption">Due</div>
                            </v-card-text>
                        </v-card>
                    </v-col>

                    <!-- Detailed Summary -->
                    <v-col cols="12" md="6">
                        <v-card>
                            <v-card-title>Billing Summary</v-card-title>
                            <v-card-text>
                                <v-table density="compact">
                                    <tbody>
                                        <tr>
                                            <td>Subtotal (Tests):</td>
                                            <td class="text-end">{{ formatNumber(billingReport.summary?.total_subtotal) }}</td>
                                        </tr>
                                        <tr>
                                            <td>Consultation Fee:</td>
                                            <td class="text-end">{{ formatNumber(billingReport.summary?.total_consultation) }}</td>
                                        </tr>
                                        <tr>
                                            <td>Discount:</td>
                                            <td class="text-end text-error">-{{ formatNumber(billingReport.summary?.total_discount) }}</td>
                                        </tr>
                                        <tr>
                                            <td>Referral Discount:</td>
                                            <td class="text-end text-error">-{{ formatNumber(billingReport.summary?.total_referral_discount) }}</td>
                                        </tr>
                                        <tr>
                                            <td>Tax:</td>
                                            <td class="text-end">{{ formatNumber(billingReport.summary?.total_tax) }}</td>
                                        </tr>
                                        <tr class="font-weight-bold">
                                            <td>Total:</td>
                                            <td class="text-end">{{ formatNumber(billingReport.summary?.total_amount) }}</td>
                                        </tr>
                                        <tr>
                                            <td>Doctor Commission:</td>
                                            <td class="text-end text-warning">{{ formatNumber(billingReport.summary?.total_commission) }}</td>
                                        </tr>
                                    </tbody>
                                </v-table>
                            </v-card-text>
                        </v-card>
                    </v-col>

                    <!-- Status Breakdown -->
                    <v-col cols="12" md="6">
                        <v-card>
                            <v-card-title>By Status</v-card-title>
                            <v-card-text>
                                <v-table density="compact">
                                    <thead>
                                        <tr>
                                            <th>Status</th>
                                            <th class="text-end">Count</th>
                                            <th class="text-end">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="status in billingReport.status_breakdown" :key="status.status">
                                            <td>
                                                <v-chip :color="getStatusColor(status.status)" size="small">{{ status.status }}</v-chip>
                                            </td>
                                            <td class="text-end">{{ status.count }}</td>
                                            <td class="text-end">{{ formatNumber(status.total) }}</td>
                                        </tr>
                                    </tbody>
                                </v-table>
                            </v-card-text>
                        </v-card>
                    </v-col>

                    <!-- Top Doctors -->
                    <v-col cols="12" md="6">
                        <v-card>
                            <v-card-title>Top Doctors (By Revenue)</v-card-title>
                            <v-card-text>
                                <v-table density="compact">
                                    <thead>
                                        <tr>
                                            <th>Doctor</th>
                                            <th class="text-end">Invoices</th>
                                            <th class="text-end">Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in billingReport.top_doctors" :key="item.doctor_id">
                                            <td>{{ item.doctor?.name }}</td>
                                            <td class="text-end">{{ item.invoice_count }}</td>
                                            <td class="text-end font-weight-bold">{{ formatNumber(item.total) }}</td>
                                        </tr>
                                    </tbody>
                                </v-table>
                            </v-card-text>
                        </v-card>
                    </v-col>

                    <!-- Referral Performance -->
                    <v-col cols="12" md="6">
                        <v-card>
                            <v-card-title>Referral Performance</v-card-title>
                            <v-card-text>
                                <v-table density="compact">
                                    <thead>
                                        <tr>
                                            <th>Referral</th>
                                            <th class="text-end">Invoices</th>
                                            <th class="text-end">Revenue</th>
                                            <th class="text-end">Discount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in billingReport.referral_performance" :key="item.referral_id">
                                            <td>{{ item.referral?.name }}</td>
                                            <td class="text-end">{{ item.invoice_count }}</td>
                                            <td class="text-end">{{ formatNumber(item.total) }}</td>
                                            <td class="text-end text-error">-{{ formatNumber(item.discount_given) }}</td>
                                        </tr>
                                    </tbody>
                                </v-table>
                            </v-card-text>
                        </v-card>
                    </v-col>

                    <!-- Daily Revenue Chart -->
                    <v-col cols="12">
                        <v-card>
                            <v-card-title>Daily Revenue</v-card-title>
                            <v-card-text>
                                <div class="d-flex align-end justify-space-around" style="height: 200px; overflow-x: auto;">
                                    <div v-for="day in billingReport.daily_revenue" :key="day.date" class="text-center px-1" style="min-width: 60px;">
                                        <div class="d-flex flex-column align-center" style="height: 180px;">
                                            <div class="bg-success rounded-t" :style="{ height: getBarHeight(day.total, 'total') + 'px', width: '25px' }"></div>
                                        </div>
                                        <div class="text-caption mt-1">{{ formatShortDate(day.date) }}</div>
                                        <div class="text-caption font-weight-bold">{{ formatCompact(day.total) }}</div>
                                    </div>
                                </div>
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>
            </v-window-item>

            <!-- Commission Report Tab -->
            <v-window-item value="commission">
                <v-row v-if="commissionReport">
                    <!-- Totals -->
                    <v-col cols="12">
                        <v-card>
                            <v-card-title>Commission Summary</v-card-title>
                            <v-card-text>
                                <v-row>
                                    <v-col v-for="item in commissionReport.totals" :key="item.entity_type" cols="6" md="3">
                                        <v-card :color="item.entity_type === 'Doctor' ? 'primary' : 'success'" variant="tonal">
                                            <v-card-text class="text-center">
                                                <div class="text-subtitle-1">{{ item.entity_type === 'Doctor' ? 'Doctor' : 'Referral' }}</div>
                                                <div class="d-flex justify-space-around mt-2">
                                                    <div>
                                                        <div class="text-h6">{{ formatNumber(item.earned) }}</div>
                                                        <div class="text-caption">Earned</div>
                                                    </div>
                                                    <div>
                                                        <div class="text-h6">{{ formatNumber(item.paid) }}</div>
                                                        <div class="text-caption">Paid</div>
                                                    </div>
                                                </div>
                                            </v-card-text>
                                        </v-card>
                                    </v-col>
                                </v-row>
                            </v-card-text>
                        </v-card>
                    </v-col>

                    <!-- Doctor Commissions -->
                    <v-col cols="12" md="6">
                        <v-card>
                            <v-card-title>Doctor Commission</v-card-title>
                            <v-card-text>
                                <v-table density="compact">
                                    <thead>
                                        <tr>
                                            <th>Doctor</th>
                                            <th class="text-end">Earned</th>
                                            <th class="text-end">Paid</th>
                                            <th class="text-end">Pending</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in commissionReport.doctor_commissions" :key="item.entity_id">
                                            <td>{{ item.doctor?.name }}</td>
                                            <td class="text-end text-success">{{ formatNumber(item.earned) }}</td>
                                            <td class="text-end text-info">{{ formatNumber(item.paid) }}</td>
                                            <td class="text-end text-warning font-weight-bold">{{ formatNumber(item.earned - item.paid) }}</td>
                                        </tr>
                                    </tbody>
                                </v-table>
                            </v-card-text>
                        </v-card>
                    </v-col>

                    <!-- Referral Commissions -->
                    <v-col cols="12" md="6">
                        <v-card>
                            <v-card-title>Referral Commission</v-card-title>
                            <v-card-text>
                                <v-table density="compact">
                                    <thead>
                                        <tr>
                                            <th>Referral</th>
                                            <th class="text-end">Earned</th>
                                            <th class="text-end">Paid</th>
                                            <th class="text-end">Pending</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in commissionReport.referral_commissions" :key="item.entity_id">
                                            <td>{{ item.referral?.name }}</td>
                                            <td class="text-end text-success">{{ formatNumber(item.earned) }}</td>
                                            <td class="text-end text-info">{{ formatNumber(item.paid) }}</td>
                                            <td class="text-end text-warning font-weight-bold">{{ formatNumber(item.earned - item.paid) }}</td>
                                        </tr>
                                    </tbody>
                                </v-table>
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>
            </v-window-item>
        </v-window>

        <v-overlay :model-value="loading" class="align-center justify-center">
            <v-progress-circular indeterminate size="64"></v-progress-circular>
        </v-overlay>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const loading = ref(false);
const activeTab = ref('billing');
const dateFrom = ref(new Date().toISOString().substr(0, 10).replace(/-\d{2}$/, '-01'));
const dateTo = ref(new Date().toISOString().substr(0, 10));
const billingReport = ref(null);
const commissionReport = ref(null);

const formatNumber = (num) => Number(num || 0).toLocaleString('en-BD');
const formatCompact = (num) => {
    num = Number(num || 0);
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
    if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
    return num.toString();
};
const formatShortDate = (date) => {
    const d = new Date(date);
    return `${d.getDate()}/${d.getMonth() + 1}`;
};

const getStatusColor = (status) => ({
    'Paid': 'success',
    'Partial': 'warning',
    'Unpaid': 'error',
}[status] || 'grey');

const getBarHeight = (value, type) => {
    const maxValue = Math.max(...(billingReport.value?.daily_revenue || []).map(d => parseFloat(d[type]) || 0));
    if (maxValue === 0) return 0;
    return Math.max(10, (parseFloat(value) / maxValue) * 160);
};

const setDateRange = (range) => {
    const today = new Date();
    if (range === 'today') {
        dateFrom.value = today.toISOString().substr(0, 10);
        dateTo.value = today.toISOString().substr(0, 10);
    } else if (range === 'week') {
        const weekStart = new Date(today);
        weekStart.setDate(today.getDate() - today.getDay());
        dateFrom.value = weekStart.toISOString().substr(0, 10);
        dateTo.value = today.toISOString().substr(0, 10);
    } else if (range === 'month') {
        dateFrom.value = today.toISOString().substr(0, 10).replace(/-\d{2}$/, '-01');
        dateTo.value = today.toISOString().substr(0, 10);
    }
    fetchReports();
};

const fetchReports = async () => {
    loading.value = true;
    try {
        const [billingRes, commissionRes] = await Promise.all([
            axios.get('/api/billing/reports/billing', { params: { date_from: dateFrom.value, date_to: dateTo.value } }),
            axios.get('/api/billing/reports/commission', { params: { date_from: dateFrom.value, date_to: dateTo.value } }),
        ]);
        billingReport.value = billingRes.data;
        commissionReport.value = commissionRes.data;
    } catch (error) {
        console.error('Failed to fetch reports:', error);
    }
    loading.value = false;
};

onMounted(() => fetchReports());
</script>
