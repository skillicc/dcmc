<template>
    <div>
        <!-- Tabs -->
        <v-tabs v-model="activeTab" color="primary" class="mb-4">
            <v-tab value="doctors">Doctor Commission</v-tab>
            <v-tab value="referrals">Referral Commission</v-tab>
            <v-tab value="ledger">Commission Ledger</v-tab>
        </v-tabs>

        <v-window v-model="activeTab">
            <!-- Doctor Commissions Tab -->
            <v-window-item value="doctors">
                <v-card>
                    <v-card-title class="d-flex align-center">
                        <v-icon class="mr-2">mdi-doctor</v-icon>
                        Doctor Commission Summary
                    </v-card-title>
                    <v-card-text>
                        <v-text-field v-model="doctorSearch" label="Search Doctor" prepend-inner-icon="mdi-magnify" clearable hide-details class="mb-4" @input="fetchDoctorCommissions" />

                        <v-data-table :headers="doctorHeaders" :items="doctors" :loading="loadingDoctors" :search="doctorSearch">
                            <template v-slot:item.total_earned="{ item }">
                                <span class="text-success">{{ formatNumber(item.total_earned) }}</span>
                            </template>
                            <template v-slot:item.total_paid="{ item }">
                                <span class="text-info">{{ formatNumber(item.total_paid) }}</span>
                            </template>
                            <template v-slot:item.pending="{ item }">
                                <span class="text-warning font-weight-bold">{{ formatNumber(item.pending) }}</span>
                            </template>
                            <template v-slot:item.actions="{ item }">
                                <v-btn size="small" color="primary" variant="tonal" @click="viewDoctorDetails(item)" class="mr-2">
                                    <v-icon start>mdi-eye</v-icon>
                                    Details
                                </v-btn>
                                <v-btn size="small" color="success" variant="tonal" @click="openPaymentDialog('Doctor', item)" :disabled="item.pending <= 0">
                                    <v-icon start>mdi-cash</v-icon>
                                    Payment
                                </v-btn>
                            </template>
                        </v-data-table>
                    </v-card-text>
                </v-card>
            </v-window-item>

            <!-- Referral Commissions Tab -->
            <v-window-item value="referrals">
                <v-card>
                    <v-card-title class="d-flex align-center">
                        <v-icon class="mr-2">mdi-account-group</v-icon>
                        Referral Commission Summary
                    </v-card-title>
                    <v-card-text>
                        <v-row class="mb-4">
                            <v-col cols="12" md="4">
                                <v-text-field v-model="referralSearch" label="Search Referral" prepend-inner-icon="mdi-magnify" clearable hide-details @input="fetchReferralCommissions" />
                            </v-col>
                        </v-row>

                        <v-data-table :headers="referralHeaders" :items="referralCommissions" :loading="loadingReferrals">
                            <template v-slot:item.total_earned="{ item }">
                                <span class="text-success">{{ formatNumber(item.total_commission_earned) }}</span>
                            </template>
                            <template v-slot:item.total_paid="{ item }">
                                <span class="text-info">{{ formatNumber(item.total_commission_paid) }}</span>
                            </template>
                            <template v-slot:item.pending="{ item }">
                                <span class="text-warning font-weight-bold">{{ formatNumber(item.total_commission_earned - item.total_commission_paid) }}</span>
                            </template>
                            <template v-slot:item.actions="{ item }">
                                <v-btn size="small" color="success" variant="tonal" @click="openPaymentDialog('Referral', item)" :disabled="(item.total_commission_earned - item.total_commission_paid) <= 0">
                                    <v-icon start>mdi-cash</v-icon>
                                    Payment
                                </v-btn>
                            </template>
                        </v-data-table>
                    </v-card-text>
                </v-card>
            </v-window-item>

            <!-- Commission Ledger Tab -->
            <v-window-item value="ledger">
                <v-card>
                    <v-card-title class="d-flex align-center">
                        <v-icon class="mr-2">mdi-book-open-page-variant</v-icon>
                        Commission Ledger
                    </v-card-title>
                    <v-card-text>
                        <v-row class="mb-4">
                            <v-col cols="12" md="2">
                                <v-select v-model="ledgerFilters.entity_type" label="Entity Type" :items="['Doctor', 'Referral']" clearable hide-details @update:model-value="fetchLedger" />
                            </v-col>
                            <v-col cols="12" md="2">
                                <v-select v-model="ledgerFilters.type" label="Transaction Type" :items="['Earned', 'Paid', 'Adjustment']" clearable hide-details @update:model-value="fetchLedger" />
                            </v-col>
                            <v-col cols="12" md="2">
                                <v-text-field v-model="ledgerFilters.date_from" label="From Date" type="date" hide-details @update:model-value="fetchLedger" />
                            </v-col>
                            <v-col cols="12" md="2">
                                <v-text-field v-model="ledgerFilters.date_to" label="To Date" type="date" hide-details @update:model-value="fetchLedger" />
                            </v-col>
                        </v-row>

                        <v-data-table-server
                            v-model:items-per-page="ledgerPerPage"
                            v-model:page="ledgerPage"
                            :headers="ledgerHeaders"
                            :items="ledgerEntries"
                            :items-length="ledgerTotal"
                            :loading="loadingLedger"
                            @update:options="fetchLedger"
                        >
                            <template v-slot:item.entity="{ item }">
                                <div>
                                    <v-chip size="x-small" :color="item.entity_type === 'Doctor' ? 'primary' : 'success'" class="mr-1">{{ item.entity_type }}</v-chip>
                                    {{ item.entity?.name || 'N/A' }}
                                </div>
                            </template>
                            <template v-slot:item.type="{ item }">
                                <v-chip :color="item.type === 'Earned' ? 'success' : item.type === 'Paid' ? 'info' : 'warning'" size="small">{{ item.type }}</v-chip>
                            </template>
                            <template v-slot:item.invoice="{ item }">
                                {{ item.invoice?.invoice_no || '-' }}
                            </template>
                            <template v-slot:item.amount="{ item }">
                                <span :class="item.amount < 0 ? 'text-error' : 'text-success'">{{ formatNumber(item.amount) }}</span>
                            </template>
                            <template v-slot:item.created_at="{ item }">
                                {{ formatDateTime(item.created_at) }}
                            </template>
                        </v-data-table-server>
                    </v-card-text>
                </v-card>
            </v-window-item>
        </v-window>

        <!-- Doctor Details Dialog -->
        <v-dialog v-model="doctorDetailsDialog" max-width="900">
            <v-card>
                <v-card-title class="d-flex align-center">
                    <v-icon class="mr-2">mdi-doctor</v-icon>
                    Doctor Commission Details - {{ selectedDoctor?.name }}
                </v-card-title>
                <v-card-text v-if="doctorDetails">
                    <v-row class="mb-4">
                        <v-col cols="4">
                            <v-card color="success" variant="tonal">
                                <v-card-text class="text-center">
                                    <div class="text-h5">{{ formatNumber(doctorDetails.doctor?.total_earned) }}</div>
                                    <div class="text-caption">Total Earned</div>
                                </v-card-text>
                            </v-card>
                        </v-col>
                        <v-col cols="4">
                            <v-card color="info" variant="tonal">
                                <v-card-text class="text-center">
                                    <div class="text-h5">{{ formatNumber(doctorDetails.doctor?.total_paid) }}</div>
                                    <div class="text-caption">Total Paid</div>
                                </v-card-text>
                            </v-card>
                        </v-col>
                        <v-col cols="4">
                            <v-card color="warning" variant="tonal">
                                <v-card-text class="text-center">
                                    <div class="text-h5">{{ formatNumber(doctorDetails.doctor?.pending) }}</div>
                                    <div class="text-caption">Pending</div>
                                </v-card-text>
                            </v-card>
                        </v-col>
                    </v-row>

                    <v-tabs v-model="doctorDetailsTab" class="mb-4">
                        <v-tab value="ledger">Ledger Entries</v-tab>
                        <v-tab value="unpaid">Unpaid Invoices</v-tab>
                    </v-tabs>

                    <v-window v-model="doctorDetailsTab">
                        <v-window-item value="ledger">
                            <v-table density="compact">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Invoice</th>
                                        <th>Description</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="entry in doctorDetails.ledger_entries?.data" :key="entry.id">
                                        <td>{{ formatDateTime(entry.created_at) }}</td>
                                        <td><v-chip :color="entry.type === 'Earned' ? 'success' : 'info'" size="x-small">{{ entry.type }}</v-chip></td>
                                        <td>{{ entry.invoice?.invoice_no || '-' }}</td>
                                        <td>{{ entry.description }}</td>
                                        <td class="text-end" :class="entry.amount < 0 ? 'text-error' : 'text-success'">{{ formatNumber(entry.amount) }}</td>
                                    </tr>
                                </tbody>
                            </v-table>
                        </v-window-item>

                        <v-window-item value="unpaid">
                            <v-table density="compact">
                                <thead>
                                    <tr>
                                        <th>Invoice No</th>
                                        <th>Patient</th>
                                        <th>Date</th>
                                        <th class="text-end">Commission</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="invoice in doctorDetails.unpaid_invoices" :key="invoice.id">
                                        <td>{{ invoice.invoice_no }}</td>
                                        <td>{{ invoice.patient?.name }}</td>
                                        <td>{{ invoice.date }}</td>
                                        <td class="text-end">{{ formatNumber(invoice.doctor_commission) }}</td>
                                    </tr>
                                </tbody>
                            </v-table>
                        </v-window-item>
                    </v-window>
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="doctorDetailsDialog = false">Close</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Payment Dialog -->
        <v-dialog v-model="paymentDialog" max-width="500">
            <v-card>
                <v-card-title>Commission Payment</v-card-title>
                <v-card-text>
                    <v-alert type="info" variant="tonal" class="mb-4">
                        <strong>{{ paymentForm.entity_type === 'Doctor' ? 'Doctor' : 'Referral' }}:</strong> {{ selectedEntity?.name }}<br>
                        <strong>Pending Commission:</strong> {{ formatNumber(selectedEntity?.pending || (selectedEntity?.total_commission_earned - selectedEntity?.total_commission_paid)) }}
                    </v-alert>

                    <v-text-field v-model.number="paymentForm.amount" label="Amount *" type="number" prepend-inner-icon="mdi-currency-bdt" :rules="[v => v > 0 || 'Amount is required']" />

                    <v-select v-model="paymentForm.payment_method" label="Payment Method *" :items="paymentMethods" :rules="[v => !!v || 'Payment method is required']" />

                    <v-text-field v-model="paymentForm.payment_reference" label="Reference (Check No, Transfer ID, etc.)" />

                    <v-textarea v-model="paymentForm.notes" label="Notes" rows="2" />
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="paymentDialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="submitPayment" :loading="paymentSaving">Make Payment</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-snackbar v-model="snackbar.show" :color="snackbar.color">{{ snackbar.message }}</v-snackbar>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

const activeTab = ref('doctors');
const loadingDoctors = ref(false);
const loadingReferrals = ref(false);
const loadingLedger = ref(false);
const paymentSaving = ref(false);
const doctors = ref([]);
const referralCommissions = ref([]);
const ledgerEntries = ref([]);
const ledgerTotal = ref(0);
const ledgerPage = ref(1);
const ledgerPerPage = ref(20);
const doctorSearch = ref('');
const referralSearch = ref('');
const doctorDetailsDialog = ref(false);
const doctorDetailsTab = ref('ledger');
const doctorDetails = ref(null);
const selectedDoctor = ref(null);
const selectedEntity = ref(null);
const paymentDialog = ref(false);
const paymentMethods = ref([]);

const ledgerFilters = reactive({
    entity_type: null,
    type: null,
    date_from: '',
    date_to: '',
});

const paymentForm = reactive({
    entity_type: '',
    entity_id: null,
    amount: 0,
    payment_method: 'Cash',
    payment_reference: '',
    notes: '',
});

const snackbar = reactive({ show: false, message: '', color: 'success' });

const doctorHeaders = [
    { title: 'Doctor', key: 'name' },
    { title: 'Specialization', key: 'specialization' },
    { title: 'Commission %', key: 'commission_percentage' },
    { title: 'Total Earned', key: 'total_earned' },
    { title: 'Total Paid', key: 'total_paid' },
    { title: 'Pending', key: 'pending' },
    { title: 'Actions', key: 'actions', sortable: false },
];

const referralHeaders = [
    { title: 'Name', key: 'name' },
    { title: 'Code', key: 'code' },
    { title: 'Type', key: 'type' },
    { title: 'Total Earned', key: 'total_earned' },
    { title: 'Total Paid', key: 'total_paid' },
    { title: 'Pending', key: 'pending' },
    { title: 'Actions', key: 'actions', sortable: false },
];

const ledgerHeaders = [
    { title: 'Entity', key: 'entity' },
    { title: 'Type', key: 'type' },
    { title: 'Invoice', key: 'invoice' },
    { title: 'Description', key: 'description' },
    { title: 'Amount', key: 'amount', align: 'end' },
    { title: 'Date', key: 'created_at' },
];

const formatNumber = (num) => Number(num || 0).toLocaleString('en-BD');
const formatDateTime = (date) => new Date(date).toLocaleString('en-GB');

const fetchDoctorCommissions = async () => {
    loadingDoctors.value = true;
    try {
        const response = await axios.get('/api/billing/doctor-commissions', {
            params: { search: doctorSearch.value },
        });
        doctors.value = response.data;
    } catch (error) {
        console.error('Failed to fetch doctor commissions:', error);
    }
    loadingDoctors.value = false;
};

const fetchReferralCommissions = async () => {
    loadingReferrals.value = true;
    try {
        const response = await axios.get('/api/billing/referrals', {
            params: { search: referralSearch.value, active_only: false },
        });
        referralCommissions.value = response.data.data || [];
    } catch (error) {
        console.error('Failed to fetch referral commissions:', error);
    }
    loadingReferrals.value = false;
};

const fetchLedger = async () => {
    loadingLedger.value = true;
    try {
        const response = await axios.get('/api/billing/commission-ledger', {
            params: {
                page: ledgerPage.value,
                per_page: ledgerPerPage.value,
                ...ledgerFilters,
            },
        });
        ledgerEntries.value = response.data.data || [];
        ledgerTotal.value = response.data.total || 0;
    } catch (error) {
        console.error('Failed to fetch ledger:', error);
    }
    loadingLedger.value = false;
};

const fetchPaymentMethods = async () => {
    try {
        const response = await axios.get('/api/billing/payment-methods');
        paymentMethods.value = response.data;
    } catch (error) {
        console.error('Failed to fetch payment methods:', error);
    }
};

const viewDoctorDetails = async (doctor) => {
    selectedDoctor.value = doctor;
    doctorDetailsDialog.value = true;
    try {
        const response = await axios.get(`/api/billing/doctor-commissions/${doctor.id}`);
        doctorDetails.value = response.data;
    } catch (error) {
        console.error('Failed to fetch doctor details:', error);
    }
};

const openPaymentDialog = (entityType, entity) => {
    selectedEntity.value = entity;
    paymentForm.entity_type = entityType;
    paymentForm.entity_id = entity.id;
    paymentForm.amount = entityType === 'Doctor' ? entity.pending : (entity.total_commission_earned - entity.total_commission_paid);
    paymentForm.payment_method = 'Cash';
    paymentForm.payment_reference = '';
    paymentForm.notes = '';
    paymentDialog.value = true;
};

const submitPayment = async () => {
    paymentSaving.value = true;
    try {
        await axios.post('/api/billing/commission-payment', paymentForm);
        snackbar.message = 'Payment successful';
        snackbar.color = 'success';
        snackbar.show = true;
        paymentDialog.value = false;
        fetchDoctorCommissions();
        fetchReferralCommissions();
        fetchLedger();
    } catch (error) {
        snackbar.message = error.response?.data?.message || 'Payment failed';
        snackbar.color = 'error';
        snackbar.show = true;
    }
    paymentSaving.value = false;
};

onMounted(() => {
    fetchDoctorCommissions();
    fetchReferralCommissions();
    fetchLedger();
    fetchPaymentMethods();
});
</script>
