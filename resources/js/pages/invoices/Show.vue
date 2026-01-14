<template>
    <div>
        <v-card :loading="loading">
            <v-card-title class="d-flex align-center justify-space-between">
                <div class="d-flex align-center">
                    <v-btn icon variant="text" :to="{ name: 'invoices.index' }" class="mr-2"><v-icon>mdi-arrow-left</v-icon></v-btn>
                    <v-icon class="mr-2">mdi-receipt</v-icon>
                    Invoice Details
                </div>
                <v-btn color="success" @click="printInvoice">
                    <v-icon start>mdi-printer</v-icon>Print
                </v-btn>
            </v-card-title>

            <v-card-text>
                <v-row>
                    <v-col cols="12" md="6">
                        <v-card variant="outlined">
                            <v-card-title class="text-subtitle-1 bg-grey-lighten-4">Patient Information</v-card-title>
                            <v-card-text>
                                <p><strong>Name:</strong> {{ invoice.patient?.name }}</p>
                                <p><strong>Phone:</strong> {{ invoice.patient?.phone }}</p>
                                <p><strong>Address:</strong> {{ invoice.patient?.address || '-' }}</p>
                            </v-card-text>
                        </v-card>
                    </v-col>
                    <v-col cols="12" md="6">
                        <v-card variant="outlined">
                            <v-card-title class="text-subtitle-1 bg-grey-lighten-4">Invoice Info</v-card-title>
                            <v-card-text>
                                <p><strong>Invoice No:</strong> {{ invoice.invoice_no }}</p>
                                <p><strong>Date:</strong> {{ invoice.date }}</p>
                                <p><strong>Status:</strong>
                                    <v-chip :color="getStatusColor(invoice.status)" size="small">{{ invoice.status }}</v-chip>
                                </p>
                                <p v-if="invoice.doctor"><strong>Referred By:</strong> {{ invoice.doctor?.name }}</p>
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>

                <!-- Invoice Items -->
                <v-card variant="outlined" class="mt-4">
                    <v-card-title class="text-subtitle-1 bg-grey-lighten-4">Invoice Items</v-card-title>
                    <v-card-text class="pa-0">
                        <v-table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Item / Test</th>
                                    <th class="text-end">Qty</th>
                                    <th class="text-end">Price</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, index) in invoice.items" :key="index">
                                    <td>{{ index + 1 }}</td>
                                    <td>{{ item.name || item.test?.name }}</td>
                                    <td class="text-end">{{ item.quantity }}</td>
                                    <td class="text-end">{{ item.unit_price }}</td>
                                    <td class="text-end font-weight-medium">{{ item.total }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end">Subtotal:</td>
                                    <td class="text-end">{{ invoice.subtotal }}</td>
                                </tr>
                                <tr v-if="invoice.discount > 0">
                                    <td colspan="4" class="text-end">Discount:</td>
                                    <td class="text-end text-error">-{{ invoice.discount }}</td>
                                </tr>
                                <tr class="bg-grey-lighten-4">
                                    <td colspan="4" class="text-end font-weight-bold">Total:</td>
                                    <td class="text-end font-weight-bold text-primary">{{ invoice.total }}</td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end">Paid:</td>
                                    <td class="text-end text-success">{{ invoice.paid }}</td>
                                </tr>
                                <tr>
                                    <td colspan="4" class="text-end font-weight-bold">Due:</td>
                                    <td class="text-end font-weight-bold" :class="invoice.due > 0 ? 'text-error' : 'text-success'">{{ invoice.due }}</td>
                                </tr>
                            </tfoot>
                        </v-table>
                    </v-card-text>
                </v-card>

                <!-- Payment History -->
                <v-card variant="outlined" class="mt-4">
                    <v-card-title class="text-subtitle-1 bg-grey-lighten-4 d-flex justify-space-between align-center">
                        <span>Payment History</span>
                        <v-btn size="small" color="primary" variant="tonal" @click="openPaymentDialog" v-if="invoice.due > 0">
                            <v-icon start>mdi-plus</v-icon>Add Payment
                        </v-btn>
                    </v-card-title>
                    <v-card-text class="pa-0">
                        <v-table v-if="invoice.payments?.length">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="payment in invoice.payments" :key="payment.id">
                                    <td>{{ payment.date }}</td>
                                    <td class="text-success font-weight-medium">{{ payment.amount }}</td>
                                    <td>{{ payment.payment_method }}</td>
                                </tr>
                            </tbody>
                        </v-table>
                        <p v-else class="text-grey text-center py-4">No payments recorded</p>
                    </v-card-text>
                </v-card>

                <!-- Notes -->
                <v-card variant="outlined" class="mt-4" v-if="invoice.notes">
                    <v-card-title class="text-subtitle-1 bg-grey-lighten-4">Notes</v-card-title>
                    <v-card-text>{{ invoice.notes }}</v-card-text>
                </v-card>
            </v-card-text>
        </v-card>

        <!-- Payment Dialog -->
        <v-dialog v-model="paymentDialog" max-width="400">
            <v-card>
                <v-card-title>Add Payment</v-card-title>
                <v-card-text>
                    <p class="mb-4">Due Amount: <strong class="text-error">{{ invoice.due }}</strong></p>
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
import { useRoute } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const loading = ref(true);
const saving = ref(false);
const paymentDialog = ref(false);
const paymentAmount = ref(0);
const paymentMethod = ref('Cash');

const paymentMethods = ['Cash', 'Card', 'Mobile Banking', 'Bank Transfer', 'Other'];
const snackbar = reactive({ show: false, message: '', color: 'success' });

const invoice = reactive({
    id: '', invoice_no: '', patient: {}, doctor: {}, date: '',
    items: [], subtotal: 0, discount: 0, total: 0, paid: 0, due: 0,
    status: '', payments: [], notes: '',
});

const getStatusColor = (status) => ({ Unpaid: 'error', Partial: 'warning', Paid: 'success' }[status] || 'grey');

const fetchInvoice = async () => {
    loading.value = true;
    try {
        const response = await axios.get(`/api/invoices/${route.params.id}`);
        Object.assign(invoice, response.data.data);
    } catch (error) { console.error('Failed to load invoice:', error); }
    loading.value = false;
};

const openPaymentDialog = () => {
    paymentAmount.value = invoice.due;
    paymentMethod.value = 'Cash';
    paymentDialog.value = true;
};

const addPayment = async () => {
    saving.value = true;
    try {
        await axios.post(`/api/invoices/${invoice.id}/payments`, {
            amount: paymentAmount.value,
            payment_method: paymentMethod.value,
        });
        snackbar.message = 'Payment added successfully'; snackbar.color = 'success'; snackbar.show = true;
        paymentDialog.value = false;
        fetchInvoice();
    } catch (error) {
        snackbar.message = 'Failed to add payment'; snackbar.color = 'error'; snackbar.show = true;
    }
    saving.value = false;
};

const printInvoice = () => { window.open(`/invoices/${invoice.id}/print`, '_blank'); };

onMounted(() => fetchInvoice());
</script>
