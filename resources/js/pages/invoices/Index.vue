<template>
    <div>
        <v-card>
            <v-card-title class="d-flex align-center justify-space-between flex-wrap ga-2">
                <div class="d-flex align-center">
                    <v-icon class="mr-2">mdi-receipt</v-icon>
                    Invoices
                </div>
                <v-btn color="primary" :to="{ name: 'invoices.create' }">
                    <v-icon start>mdi-plus</v-icon>
                    New Invoice
                </v-btn>
            </v-card-title>

            <v-card-text>
                <v-row class="mb-4">
                    <v-col cols="12" md="3">
                        <v-text-field v-model="search" label="Search..." prepend-inner-icon="mdi-magnify" clearable hide-details @input="debouncedSearch" />
                    </v-col>
                    <v-col cols="12" md="2">
                        <v-select v-model="filters.status" label="Status" :items="statuses" clearable hide-details @update:model-value="fetchInvoices" />
                    </v-col>
                    <v-col cols="12" md="2">
                        <v-text-field v-model="filters.from_date" label="From Date" type="date" hide-details @update:model-value="fetchInvoices" />
                    </v-col>
                    <v-col cols="12" md="2">
                        <v-text-field v-model="filters.to_date" label="To Date" type="date" hide-details @update:model-value="fetchInvoices" />
                    </v-col>
                </v-row>

                <v-data-table-server
                    v-model:items-per-page="itemsPerPage"
                    v-model:page="page"
                    :headers="headers"
                    :items="invoices"
                    :items-length="totalItems"
                    :loading="loading"
                    @update:options="fetchInvoices"
                >
                    <template v-slot:item.patient="{ item }">
                        <div>
                            <p class="font-weight-medium mb-0">{{ item.patient?.name }}</p>
                            <p class="text-caption text-grey mb-0">{{ item.patient?.phone }}</p>
                        </div>
                    </template>

                    <template v-slot:item.total="{ item }">
                        <span class="font-weight-bold">{{ item.total }}</span>
                    </template>

                    <template v-slot:item.paid="{ item }">
                        <span class="text-success">{{ item.paid }}</span>
                    </template>

                    <template v-slot:item.due="{ item }">
                        <span :class="item.due > 0 ? 'text-error' : 'text-success'">{{ item.due }}</span>
                    </template>

                    <template v-slot:item.status="{ item }">
                        <v-chip :color="getStatusColor(item.status)" size="small">{{ item.status }}</v-chip>
                    </template>

                    <template v-slot:item.actions="{ item }">
                        <v-btn icon variant="text" size="small" color="info" :to="{ name: 'invoices.show', params: { id: item.id } }">
                            <v-icon>mdi-eye</v-icon>
                        </v-btn>
                        <v-btn icon variant="text" size="small" color="success" @click="printInvoice(item)">
                            <v-icon>mdi-printer</v-icon>
                        </v-btn>
                        <v-btn icon variant="text" size="small" color="warning" @click="openPaymentDialog(item)" v-if="item.due > 0">
                            <v-icon>mdi-cash-plus</v-icon>
                        </v-btn>
                    </template>
                </v-data-table-server>
            </v-card-text>
        </v-card>

        <!-- Payment Dialog -->
        <v-dialog v-model="paymentDialog" max-width="400">
            <v-card>
                <v-card-title>Add Payment</v-card-title>
                <v-card-text>
                    <p class="mb-4">Due Amount: <strong class="text-error">{{ selectedInvoice?.due }}</strong></p>
                    <v-text-field v-model="paymentAmount" label="Payment Amount" type="number" prepend-inner-icon="mdi-currency-bdt" />
                    <v-select v-model="paymentMethod" label="Payment Method" :items="paymentMethods" />
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="paymentDialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="addPayment" :loading="saving">Add Payment</v-btn>
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
const saving = ref(false);
const invoices = ref([]);
const totalItems = ref(0);
const page = ref(1);
const itemsPerPage = ref(10);
const search = ref('');
const paymentDialog = ref(false);
const selectedInvoice = ref(null);
const paymentAmount = ref(0);
const paymentMethod = ref('Cash');

const statuses = ['Unpaid', 'Partial', 'Paid'];
const paymentMethods = ['Cash', 'Card', 'Mobile Banking', 'Bank Transfer', 'Other'];
const filters = reactive({ status: null, from_date: '', to_date: '' });
const snackbar = reactive({ show: false, message: '', color: 'success' });

const headers = [
    { title: 'Invoice No', key: 'invoice_no' },
    { title: 'Patient', key: 'patient' },
    { title: 'Date', key: 'date' },
    { title: 'Total', key: 'total', align: 'end' },
    { title: 'Paid', key: 'paid', align: 'end' },
    { title: 'Due', key: 'due', align: 'end' },
    { title: 'Status', key: 'status' },
    { title: 'Actions', key: 'actions', sortable: false },
];

const getStatusColor = (status) => ({ Unpaid: 'error', Partial: 'warning', Paid: 'success' }[status] || 'grey');

let searchTimeout = null;
const debouncedSearch = () => { clearTimeout(searchTimeout); searchTimeout = setTimeout(() => { page.value = 1; fetchInvoices(); }, 500); };

const fetchInvoices = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/invoices', {
            params: { page: page.value, per_page: itemsPerPage.value, search: search.value, ...filters },
        });
        invoices.value = response.data.data || [];
        totalItems.value = response.data.total || 0;
    } catch (error) { console.error('Failed to fetch invoices:', error); }
    loading.value = false;
};

const printInvoice = (invoice) => { window.open(`/invoices/${invoice.id}/print`, '_blank'); };

const openPaymentDialog = (invoice) => {
    selectedInvoice.value = invoice;
    paymentAmount.value = invoice.due;
    paymentMethod.value = 'Cash';
    paymentDialog.value = true;
};

const addPayment = async () => {
    saving.value = true;
    try {
        await axios.post(`/api/invoices/${selectedInvoice.value.id}/payments`, {
            amount: paymentAmount.value,
            payment_method: paymentMethod.value,
        });
        snackbar.message = 'Payment added successfully'; snackbar.color = 'success'; snackbar.show = true;
        paymentDialog.value = false;
        fetchInvoices();
    } catch (error) {
        snackbar.message = 'Failed to add payment'; snackbar.color = 'error'; snackbar.show = true;
    }
    saving.value = false;
};

onMounted(() => fetchInvoices());
</script>
