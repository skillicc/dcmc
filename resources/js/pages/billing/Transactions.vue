<template>
    <div>
        <!-- Summary Cards -->
        <v-row class="mb-4">
            <v-col cols="12" md="3">
                <v-card color="success" variant="tonal">
                    <v-card-text class="text-center">
                        <v-icon size="40" class="mb-2">mdi-cash-multiple</v-icon>
                        <div class="text-h5">{{ formatNumber(summary.total) }}</div>
                        <div class="text-caption">Total Income</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="12" md="9">
                <v-card>
                    <v-card-text>
                        <div class="text-subtitle-2 mb-2">By Payment Method</div>
                        <v-row>
                            <v-col v-for="item in summary.byMethod" :key="item.payment_method" cols="6" md="3">
                                <div class="d-flex justify-space-between">
                                    <span class="text-caption">{{ item.payment_method }}:</span>
                                    <span class="font-weight-bold">{{ formatNumber(item.total) }}</span>
                                </div>
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <v-card>
            <v-card-title class="d-flex align-center">
                <v-icon class="mr-2">mdi-swap-horizontal</v-icon>
                Transaction History
            </v-card-title>
            <v-card-text>
                <v-row class="mb-4">
                    <v-col cols="12" md="3">
                        <v-text-field v-model="filters.date_from" label="From Date" type="date" hide-details @update:model-value="fetchTransactions" />
                    </v-col>
                    <v-col cols="12" md="3">
                        <v-text-field v-model="filters.date_to" label="To Date" type="date" hide-details @update:model-value="fetchTransactions" />
                    </v-col>
                    <v-col cols="12" md="3">
                        <v-select v-model="filters.payment_method" label="Payment Method" :items="paymentMethods" clearable hide-details @update:model-value="fetchTransactions" />
                    </v-col>
                    <v-col cols="12" md="3">
                        <v-btn color="primary" block @click="fetchTransactions">
                            <v-icon start>mdi-magnify</v-icon>
                            Search
                        </v-btn>
                    </v-col>
                </v-row>

                <v-data-table-server
                    v-model:items-per-page="itemsPerPage"
                    v-model:page="page"
                    :headers="headers"
                    :items="transactions"
                    :items-length="totalItems"
                    :loading="loading"
                    @update:options="fetchTransactions"
                >
                    <template v-slot:item.invoice="{ item }">
                        <div v-if="item.invoice">
                            <router-link :to="{ name: 'invoices.show', params: { id: item.invoice.id } }" class="text-decoration-none">
                                {{ item.invoice.invoice_no }}
                            </router-link>
                            <p class="text-caption text-grey mb-0">{{ item.invoice.patient?.name }}</p>
                        </div>
                    </template>

                    <template v-slot:item.amount="{ item }">
                        <span class="text-success font-weight-bold">{{ formatNumber(item.amount) }}</span>
                    </template>

                    <template v-slot:item.payment_method="{ item }">
                        <v-chip :color="getMethodColor(item.payment_method)" size="small">{{ item.payment_method }}</v-chip>
                    </template>

                    <template v-slot:item.date="{ item }">
                        {{ formatDateTime(item.date) }}
                    </template>

                    <template v-slot:item.received_by="{ item }">
                        {{ item.received_by?.name || '-' }}
                    </template>
                </v-data-table-server>
            </v-card-text>
        </v-card>

        <!-- Daily Chart -->
        <v-card class="mt-4" v-if="summary.dailyTotals.length > 0">
            <v-card-title class="d-flex align-center">
                <v-icon class="mr-2">mdi-chart-line</v-icon>
                Daily Income
            </v-card-title>
            <v-card-text>
                <div class="chart-container">
                    <div class="d-flex align-end justify-space-around" style="height: 200px;">
                        <div v-for="day in summary.dailyTotals" :key="day.date" class="text-center" style="flex: 1;">
                            <div class="bg-success rounded-t" :style="{ height: getBarHeight(day.total) + 'px', width: '30px', margin: '0 auto' }"></div>
                            <div class="text-caption mt-1">{{ formatShortDate(day.date) }}</div>
                            <div class="text-caption font-weight-bold">{{ formatNumber(day.total) }}</div>
                        </div>
                    </div>
                </div>
            </v-card-text>
        </v-card>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import axios from 'axios';

const loading = ref(false);
const transactions = ref([]);
const totalItems = ref(0);
const page = ref(1);
const itemsPerPage = ref(20);

const paymentMethods = ['Cash', 'Card', 'Mobile Banking', 'Bank Transfer', 'Cheque', 'Other'];

const filters = reactive({
    date_from: new Date().toISOString().substr(0, 10).replace(/-\d{2}$/, '-01'),
    date_to: new Date().toISOString().substr(0, 10),
    payment_method: null,
});

const summary = reactive({
    total: 0,
    byMethod: [],
    dailyTotals: [],
});

const headers = [
    { title: 'Invoice', key: 'invoice' },
    { title: 'Amount', key: 'amount', align: 'end' },
    { title: 'Payment Method', key: 'payment_method' },
    { title: 'Reference', key: 'payment_reference' },
    { title: 'Date/Time', key: 'date' },
    { title: 'Received By', key: 'received_by' },
    { title: 'Notes', key: 'notes' },
];

const formatNumber = (num) => Number(num || 0).toLocaleString('en-BD');
const formatDateTime = (date) => new Date(date).toLocaleString('en-GB');
const formatShortDate = (date) => {
    const d = new Date(date);
    return `${d.getDate()}/${d.getMonth() + 1}`;
};

const getMethodColor = (method) => ({
    'Cash': 'success',
    'Card': 'primary',
    'Mobile Banking': 'info',
    'Bank Transfer': 'warning',
    'Cheque': 'secondary',
}[method] || 'grey');

const getBarHeight = (value) => {
    const maxValue = Math.max(...summary.dailyTotals.map(d => parseFloat(d.total) || 0));
    if (maxValue === 0) return 0;
    return Math.max(10, (parseFloat(value) / maxValue) * 180);
};

const fetchTransactions = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/billing/transactions', {
            params: {
                page: page.value,
                per_page: itemsPerPage.value,
                ...filters,
            },
        });
        transactions.value = response.data.data || [];
        totalItems.value = response.data.total || 0;
    } catch (error) {
        console.error('Failed to fetch transactions:', error);
    }
    loading.value = false;
};

const fetchSummary = async () => {
    try {
        const response = await axios.get('/api/billing/transactions/summary', {
            params: filters,
        });
        summary.total = response.data.total || 0;
        summary.byMethod = response.data.by_method || [];
        summary.dailyTotals = response.data.daily_totals || [];
    } catch (error) {
        console.error('Failed to fetch summary:', error);
    }
};

onMounted(() => {
    fetchTransactions();
    fetchSummary();
});
</script>
