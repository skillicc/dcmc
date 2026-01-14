<template>
    <div>
        <v-row class="mb-4">
            <!-- Summary Cards -->
            <v-col cols="6" md="3">
                <v-card color="error" variant="tonal">
                    <v-card-text class="text-center pa-3">
                        <v-icon size="32" class="mb-1 d-none d-sm-inline">mdi-cash-clock</v-icon>
                        <div class="text-h6 text-sm-h5">{{ formatNumber(summary.totalDue) }}</div>
                        <div class="text-caption">Total Due</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="6" md="3">
                <v-card color="warning" variant="tonal">
                    <v-card-text class="text-center pa-3">
                        <v-icon size="32" class="mb-1 d-none d-sm-inline">mdi-alert-circle</v-icon>
                        <div class="text-h6 text-sm-h5">{{ summary.overdueCount }}</div>
                        <div class="text-caption">Overdue</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="6" md="3">
                <v-card color="info" variant="tonal">
                    <v-card-text class="text-center pa-3">
                        <v-icon size="32" class="mb-1 d-none d-sm-inline">mdi-file-document-multiple</v-icon>
                        <div class="text-h6 text-sm-h5">{{ summary.totalInvoices }}</div>
                        <div class="text-caption">Due Invoices</div>
                    </v-card-text>
                </v-card>
            </v-col>
            <v-col cols="6" md="3">
                <v-card color="success" variant="tonal">
                    <v-card-text class="text-center pa-3">
                        <v-icon size="32" class="mb-1 d-none d-sm-inline">mdi-account-group</v-icon>
                        <div class="text-h6 text-sm-h5">{{ summary.patientsWithDue }}</div>
                        <div class="text-caption">Patients with Due</div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <v-tabs v-model="activeTab" color="primary" class="mb-4">
            <v-tab value="invoices">Due Invoices</v-tab>
            <v-tab value="patients">Due by Patient</v-tab>
        </v-tabs>

        <v-window v-model="activeTab">
            <!-- Due Invoices Tab -->
            <v-window-item value="invoices">
                <v-card>
                    <v-card-title class="d-flex align-center">
                        <v-icon class="mr-2">mdi-cash-clock</v-icon>
                        Due Invoice List
                    </v-card-title>
                    <v-card-text>
                        <v-row class="mb-4">
                            <v-col cols="12" md="4">
                                <v-text-field v-model="search" label="Search (Invoice No, Patient Name/Phone)" prepend-inner-icon="mdi-magnify" clearable hide-details @input="debouncedSearch" />
                            </v-col>
                            <v-col cols="12" md="3">
                                <v-switch v-model="overdueOnly" label="Overdue Only" color="error" hide-details @update:model-value="fetchDueInvoices" />
                            </v-col>
                        </v-row>

                        <v-data-table-server
                            v-model:items-per-page="itemsPerPage"
                            v-model:page="page"
                            :headers="invoiceHeaders"
                            :items="invoices"
                            :items-length="totalItems"
                            :loading="loading"
                            @update:options="fetchDueInvoices"
                        >
                            <template v-slot:item.patient="{ item }">
                                <div>
                                    <p class="font-weight-medium mb-0">{{ item.patient?.name }}</p>
                                    <p class="text-caption text-grey mb-0">{{ item.patient?.phone }}</p>
                                </div>
                            </template>

                            <template v-slot:item.total="{ item }">
                                <span class="font-weight-bold">{{ formatNumber(item.total) }}</span>
                            </template>

                            <template v-slot:item.paid="{ item }">
                                <span class="text-success">{{ formatNumber(item.paid) }}</span>
                            </template>

                            <template v-slot:item.due="{ item }">
                                <span class="text-error font-weight-bold">{{ formatNumber(item.due) }}</span>
                            </template>

                            <template v-slot:item.due_date="{ item }">
                                <div v-if="item.due_date">
                                    {{ item.due_date }}
                                    <v-chip v-if="isOverdue(item.due_date)" color="error" size="x-small" class="ml-1">Overdue</v-chip>
                                </div>
                                <span v-else class="text-grey">-</span>
                            </template>

                            <template v-slot:item.status="{ item }">
                                <v-chip :color="item.status === 'Partial' ? 'warning' : 'error'" size="small">{{ item.status }}</v-chip>
                            </template>

                            <template v-slot:item.actions="{ item }">
                                <v-btn size="small" color="success" variant="tonal" @click="openCollectDialog(item)">
                                    <v-icon start>mdi-cash-plus</v-icon>
                                    Collect
                                </v-btn>
                            </template>
                        </v-data-table-server>
                    </v-card-text>
                </v-card>
            </v-window-item>

            <!-- Patients with Due Tab -->
            <v-window-item value="patients">
                <v-card>
                    <v-card-title class="d-flex align-center">
                        <v-icon class="mr-2">mdi-account-group</v-icon>
                        Due by Patient
                    </v-card-title>
                    <v-card-text>
                        <v-data-table :headers="patientHeaders" :items="patientsDue" :loading="loadingPatients">
                            <template v-slot:item.patient="{ item }">
                                <div>
                                    <p class="font-weight-medium mb-0">{{ item.patient?.name }}</p>
                                    <p class="text-caption text-grey mb-0">{{ item.patient?.phone }}</p>
                                </div>
                            </template>

                            <template v-slot:item.total_due="{ item }">
                                <span class="text-error font-weight-bold">{{ formatNumber(item.total_due) }}</span>
                            </template>

                            <template v-slot:item.actions="{ item }">
                                <v-btn size="small" color="info" variant="tonal" @click="viewPatientInvoices(item.patient_id)" class="mr-2">
                                    <v-icon start>mdi-eye</v-icon>
                                    View Invoices
                                </v-btn>
                            </template>
                        </v-data-table>
                    </v-card-text>
                </v-card>
            </v-window-item>
        </v-window>

        <!-- Collect Due Dialog -->
        <v-dialog v-model="collectDialog" max-width="500">
            <v-card>
                <v-card-title>Collect Due</v-card-title>
                <v-card-text>
                    <v-alert type="info" variant="tonal" class="mb-4">
                        <strong>Invoice:</strong> {{ selectedInvoice?.invoice_no }}<br>
                        <strong>Patient:</strong> {{ selectedInvoice?.patient?.name }}<br>
                        <strong>Total Due:</strong> <span class="text-error font-weight-bold">{{ formatNumber(selectedInvoice?.due) }}</span>
                    </v-alert>

                    <v-text-field v-model.number="collectForm.amount" label="Collection Amount *" type="number" prepend-inner-icon="mdi-currency-bdt" :max="selectedInvoice?.due" :rules="[v => v > 0 || 'Amount is required', v => v <= (selectedInvoice?.due || 0) || 'Cannot exceed due amount']" />

                    <v-select v-model="collectForm.payment_method" label="Payment Method *" :items="paymentMethods" :rules="[v => !!v || 'Payment method is required']" />

                    <v-text-field v-model="collectForm.payment_reference" label="Reference (Optional)" />

                    <v-textarea v-model="collectForm.notes" label="Notes (Optional)" rows="2" />

                    <v-btn block color="primary" variant="outlined" class="mt-2" @click="collectForm.amount = selectedInvoice?.due">
                        Collect Full Due Amount
                    </v-btn>
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="collectDialog = false">Cancel</v-btn>
                    <v-btn color="success" @click="collectDue" :loading="collecting">Collect</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Patient Invoices Dialog -->
        <v-dialog v-model="patientInvoicesDialog" max-width="800">
            <v-card>
                <v-card-title>Patient Due Invoices</v-card-title>
                <v-card-text>
                    <v-table density="compact">
                        <thead>
                            <tr>
                                <th>Invoice No</th>
                                <th>Date</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Paid</th>
                                <th class="text-end">Due</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="invoice in patientInvoices" :key="invoice.id">
                                <td>{{ invoice.invoice_no }}</td>
                                <td>{{ invoice.date }}</td>
                                <td class="text-end">{{ formatNumber(invoice.total) }}</td>
                                <td class="text-end text-success">{{ formatNumber(invoice.paid) }}</td>
                                <td class="text-end text-error font-weight-bold">{{ formatNumber(invoice.due) }}</td>
                                <td>
                                    <v-btn size="small" color="success" variant="tonal" @click="openCollectDialog(invoice)">Collect</v-btn>
                                </td>
                            </tr>
                        </tbody>
                    </v-table>
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="patientInvoicesDialog = false">Close</v-btn>
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
const loadingPatients = ref(false);
const collecting = ref(false);
const invoices = ref([]);
const patientsDue = ref([]);
const patientInvoices = ref([]);
const totalItems = ref(0);
const page = ref(1);
const itemsPerPage = ref(15);
const search = ref('');
const overdueOnly = ref(false);
const activeTab = ref('invoices');
const collectDialog = ref(false);
const patientInvoicesDialog = ref(false);
const selectedInvoice = ref(null);

const paymentMethods = ['Cash', 'Card', 'Mobile Banking', 'Bank Transfer', 'Cheque', 'Other'];

const collectForm = reactive({
    amount: 0,
    payment_method: 'Cash',
    payment_reference: '',
    notes: '',
});

const snackbar = reactive({ show: false, message: '', color: 'success' });

const summary = reactive({
    totalDue: 0,
    overdueCount: 0,
    totalInvoices: 0,
    patientsWithDue: 0,
});

const invoiceHeaders = [
    { title: 'Invoice No', key: 'invoice_no' },
    { title: 'Patient', key: 'patient' },
    { title: 'Date', key: 'date' },
    { title: 'Total', key: 'total', align: 'end' },
    { title: 'Paid', key: 'paid', align: 'end' },
    { title: 'Due', key: 'due', align: 'end' },
    { title: 'Due Date', key: 'due_date' },
    { title: 'Status', key: 'status' },
    { title: 'Action', key: 'actions', sortable: false },
];

const patientHeaders = [
    { title: 'Patient', key: 'patient' },
    { title: 'Invoice Count', key: 'invoice_count' },
    { title: 'Total Due', key: 'total_due', align: 'end' },
    { title: 'Action', key: 'actions', sortable: false },
];

const formatNumber = (num) => Number(num || 0).toLocaleString('en-BD');

const isOverdue = (dueDate) => {
    if (!dueDate) return false;
    return new Date(dueDate) < new Date();
};

let searchTimeout = null;
const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => { page.value = 1; fetchDueInvoices(); }, 500);
};

const fetchDueInvoices = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/billing/due-invoices', {
            params: {
                page: page.value,
                per_page: itemsPerPage.value,
                search: search.value,
                overdue_only: overdueOnly.value,
            },
        });
        invoices.value = response.data.data || [];
        totalItems.value = response.data.total || 0;

        // Update summary
        summary.totalInvoices = response.data.total || 0;
        summary.totalDue = invoices.value.reduce((sum, inv) => sum + parseFloat(inv.due || 0), 0);
        summary.overdueCount = invoices.value.filter(inv => isOverdue(inv.due_date)).length;
    } catch (error) {
        console.error('Failed to fetch due invoices:', error);
    }
    loading.value = false;
};

const fetchPatientsDue = async () => {
    loadingPatients.value = true;
    try {
        const response = await axios.get('/api/billing/due-stats-by-patient');
        patientsDue.value = response.data;
        summary.patientsWithDue = patientsDue.value.length;
    } catch (error) {
        console.error('Failed to fetch patients due:', error);
    }
    loadingPatients.value = false;
};

const openCollectDialog = (invoice) => {
    selectedInvoice.value = invoice;
    collectForm.amount = invoice.due;
    collectForm.payment_method = 'Cash';
    collectForm.payment_reference = '';
    collectForm.notes = '';
    collectDialog.value = true;
};

const collectDue = async () => {
    collecting.value = true;
    try {
        await axios.post(`/api/billing/due-invoices/${selectedInvoice.value.id}/collect`, collectForm);
        snackbar.message = 'Due collected successfully';
        snackbar.color = 'success';
        snackbar.show = true;
        collectDialog.value = false;
        patientInvoicesDialog.value = false;
        fetchDueInvoices();
        fetchPatientsDue();
    } catch (error) {
        snackbar.message = error.response?.data?.message || 'Failed to collect due';
        snackbar.color = 'error';
        snackbar.show = true;
    }
    collecting.value = false;
};

const viewPatientInvoices = async (patientId) => {
    try {
        const response = await axios.get('/api/billing/due-invoices', {
            params: { patient_id: patientId, per_page: 100 },
        });
        patientInvoices.value = response.data.data || [];
        patientInvoicesDialog.value = true;
    } catch (error) {
        console.error('Failed to fetch patient invoices:', error);
    }
};

onMounted(() => {
    fetchDueInvoices();
    fetchPatientsDue();
});
</script>
