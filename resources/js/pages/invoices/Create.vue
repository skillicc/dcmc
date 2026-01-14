<template>
    <div>
        <v-card>
            <v-card-title class="d-flex align-center">
                <v-btn icon variant="text" :to="{ name: 'invoices.index' }" class="mr-2"><v-icon>mdi-arrow-left</v-icon></v-btn>
                <v-icon class="mr-2">mdi-receipt-text-plus</v-icon>
                New Invoice
            </v-card-title>

            <v-card-text>
                <v-form ref="form" @submit.prevent="submitForm">
                    <v-row>
                        <v-col cols="12" md="6">
                            <v-autocomplete
                                v-model="formData.patient_id"
                                label="Patient *"
                                :items="patients"
                                item-title="name"
                                item-value="id"
                                :rules="[rules.required]"
                                prepend-inner-icon="mdi-account"
                                :loading="loadingPatients"
                                @update:search="searchPatients"
                            >
                                <template v-slot:item="{ props, item }">
                                    <v-list-item v-bind="props">
                                        <template v-slot:subtitle>{{ item.raw.phone }}</template>
                                    </v-list-item>
                                </template>
                            </v-autocomplete>
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field v-model="formData.date" label="Invoice Date *" type="date" :rules="[rules.required]" prepend-inner-icon="mdi-calendar" />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-select v-model="formData.doctor_id" label="Referred By (Doctor)" :items="doctors" item-title="name" item-value="id" clearable prepend-inner-icon="mdi-doctor" />
                        </v-col>
                    </v-row>

                    <!-- Invoice Items -->
                    <h3 class="text-subtitle-1 font-weight-bold mt-4 mb-3 d-flex align-center justify-space-between">
                        <span><v-icon class="mr-1" size="small">mdi-format-list-bulleted</v-icon> Invoice Items</span>
                    </h3>

                    <v-card variant="outlined" class="mb-4">
                        <v-card-text class="pa-0">
                            <v-table>
                                <thead>
                                    <tr>
                                        <th style="width: 50%">Item / Test</th>
                                        <th style="width: 15%">Qty</th>
                                        <th style="width: 15%">Price</th>
                                        <th style="width: 15%">Total</th>
                                        <th style="width: 5%"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in formData.items" :key="index">
                                        <td>
                                            <v-autocomplete
                                                v-model="item.test_id"
                                                :items="tests"
                                                item-title="name"
                                                item-value="id"
                                                density="compact"
                                                hide-details
                                                @update:model-value="onItemSelect(index)"
                                            />
                                        </td>
                                        <td>
                                            <v-text-field v-model="item.quantity" type="number" min="1" density="compact" hide-details @input="calculateItemTotal(index)" />
                                        </td>
                                        <td>
                                            <v-text-field v-model="item.price" type="number" density="compact" hide-details @input="calculateItemTotal(index)" />
                                        </td>
                                        <td class="font-weight-medium">{{ item.total }}</td>
                                        <td>
                                            <v-btn icon variant="text" size="small" color="error" @click="removeItem(index)" :disabled="formData.items.length === 1">
                                                <v-icon>mdi-delete</v-icon>
                                            </v-btn>
                                        </td>
                                    </tr>
                                </tbody>
                            </v-table>
                        </v-card-text>
                    </v-card>

                    <v-btn variant="tonal" color="primary" size="small" @click="addItem" class="mb-4">
                        <v-icon start>mdi-plus</v-icon>Add Item
                    </v-btn>

                    <!-- Summary -->
                    <v-card variant="outlined" class="mb-4">
                        <v-card-text>
                            <v-row>
                                <v-col cols="12" md="6">
                                    <v-textarea v-model="formData.notes" label="Notes" rows="3" />
                                </v-col>
                                <v-col cols="12" md="6">
                                    <div class="d-flex justify-space-between mb-2">
                                        <span>Subtotal:</span>
                                        <span>{{ subtotal }}</span>
                                    </div>
                                    <v-text-field
                                        v-model="formData.discount"
                                        label="Discount"
                                        type="number"
                                        density="compact"
                                        prepend-inner-icon="mdi-percent"
                                        @input="calculateTotal"
                                    />
                                    <v-divider class="my-2" />
                                    <div class="d-flex justify-space-between text-h6">
                                        <span>Total:</span>
                                        <span class="text-primary">{{ grandTotal }}</span>
                                    </div>
                                    <v-divider class="my-2" />
                                    <v-text-field
                                        v-model="formData.paid_amount"
                                        label="Paid Amount"
                                        type="number"
                                        density="compact"
                                        prepend-inner-icon="mdi-currency-bdt"
                                    />
                                    <v-select
                                        v-model="formData.payment_method"
                                        label="Payment Method"
                                        :items="paymentMethods"
                                        density="compact"
                                    />
                                    <div class="d-flex justify-space-between mt-2">
                                        <span>Due:</span>
                                        <span :class="dueAmount > 0 ? 'text-error' : 'text-success'">{{ dueAmount }}</span>
                                    </div>
                                </v-col>
                            </v-row>
                        </v-card-text>
                    </v-card>

                    <v-divider class="my-4" />

                    <div class="d-flex justify-end ga-2">
                        <v-btn variant="outlined" :to="{ name: 'invoices.index' }">Cancel</v-btn>
                        <v-btn color="primary" type="submit" :loading="loading">
                            <v-icon start>mdi-content-save</v-icon>Create Invoice
                        </v-btn>
                    </div>
                </v-form>
            </v-card-text>
        </v-card>

        <v-snackbar v-model="snackbar.show" :color="snackbar.color">{{ snackbar.message }}</v-snackbar>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const route = useRoute();
const form = ref(null);
const loading = ref(false);
const loadingPatients = ref(false);
const patients = ref([]);
const doctors = ref([]);
const tests = ref([]);

const paymentMethods = ['Cash', 'Card', 'Mobile Banking', 'Bank Transfer', 'Other'];

const formData = reactive({
    patient_id: route.query.patient_id ? parseInt(route.query.patient_id) : null,
    doctor_id: null,
    date: new Date().toISOString().split('T')[0],
    items: [{ test_id: null, name: '', quantity: 1, price: 0, total: 0 }],
    discount: 0,
    paid_amount: 0,
    payment_method: 'Cash',
    notes: '',
});

const snackbar = reactive({ show: false, message: '', color: 'success' });
const rules = { required: (v) => !!v || 'This field is required' };

const subtotal = computed(() => formData.items.reduce((sum, item) => sum + (parseFloat(item.total) || 0), 0));
const grandTotal = computed(() => Math.max(0, subtotal.value - (parseFloat(formData.discount) || 0)));
const dueAmount = computed(() => Math.max(0, grandTotal.value - (parseFloat(formData.paid_amount) || 0)));

const addItem = () => {
    formData.items.push({ test_id: null, name: '', quantity: 1, price: 0, total: 0 });
};

const removeItem = (index) => {
    if (formData.items.length > 1) formData.items.splice(index, 1);
};

const onItemSelect = (index) => {
    const item = formData.items[index];
    const test = tests.value.find(t => t.id === item.test_id);
    if (test) {
        item.name = test.name;
        item.price = test.price;
        calculateItemTotal(index);
    }
};

const calculateItemTotal = (index) => {
    const item = formData.items[index];
    item.total = (parseFloat(item.quantity) || 0) * (parseFloat(item.price) || 0);
};

const calculateTotal = () => {};

let searchTimeout = null;
const searchPatients = (search) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(async () => {
        if (!search || search.length < 2) return;
        loadingPatients.value = true;
        try {
            const response = await axios.get('/api/patients', { params: { search, per_page: 10 } });
            patients.value = response.data.data || [];
        } catch (error) { console.error('Failed to search patients:', error); }
        loadingPatients.value = false;
    }, 500);
};

const fetchDoctors = async () => {
    try { const response = await axios.get('/api/doctors'); doctors.value = response.data.data || []; }
    catch (error) { console.error('Failed to fetch doctors:', error); }
};

const fetchTests = async () => {
    try { const response = await axios.get('/api/tests'); tests.value = response.data.data || []; }
    catch (error) { console.error('Failed to fetch tests:', error); }
};

const fetchPatientIfNeeded = async () => {
    if (formData.patient_id) {
        try {
            const response = await axios.get(`/api/patients/${formData.patient_id}`);
            patients.value = [response.data.data];
        } catch (error) { console.error('Failed to fetch patient:', error); }
    }
};

const submitForm = async () => {
    const { valid } = await form.value.validate();
    if (!valid) return;

    loading.value = true;
    try {
        // Format items properly for the backend
        const formattedItems = formData.items.map(item => ({
            name: item.name,
            item_type: 'Test',
            item_id: item.test_id,
            quantity: parseInt(item.quantity) || 1,
            unit_price: parseFloat(item.price) || 0,
            total: parseFloat(item.total) || 0,
        }));

        const payload = {
            ...formData,
            items: formattedItems,
            subtotal: subtotal.value,
            total: grandTotal.value,
            due: dueAmount.value,
        };
        await axios.post('/api/invoices', payload);
        snackbar.message = 'Invoice created successfully'; snackbar.color = 'success'; snackbar.show = true;
        setTimeout(() => router.push({ name: 'invoices.index' }), 1000);
    } catch (error) {
        snackbar.message = error.response?.data?.message || 'Failed to create invoice';
        snackbar.color = 'error'; snackbar.show = true;
    }
    loading.value = false;
};

onMounted(() => { fetchDoctors(); fetchTests(); fetchPatientIfNeeded(); });
</script>
