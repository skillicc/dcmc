<template>
    <div>
        <v-card>
            <v-card-title class="d-flex align-center justify-space-between flex-wrap ga-2">
                <div class="d-flex align-center">
                    <v-icon class="mr-2">mdi-account-group</v-icon>
                    Referral Management
                </div>
                <v-btn color="primary" @click="openDialog()">
                    <v-icon start>mdi-plus</v-icon>
                    New Referral
                </v-btn>
            </v-card-title>

            <v-card-text>
                <v-row class="mb-4">
                    <v-col cols="12" md="4">
                        <v-text-field v-model="search" label="Search (Name, Code, Phone)" prepend-inner-icon="mdi-magnify" clearable hide-details @input="debouncedSearch" />
                    </v-col>
                    <v-col cols="12" md="3">
                        <v-select v-model="filterType" label="Type" :items="referralTypes" clearable hide-details @update:model-value="fetchReferrals" />
                    </v-col>
                    <v-col cols="12" md="3">
                        <v-switch v-model="activeOnly" label="Active Only" color="primary" hide-details @update:model-value="fetchReferrals" />
                    </v-col>
                </v-row>

                <v-data-table-server
                    v-model:items-per-page="itemsPerPage"
                    v-model:page="page"
                    :headers="headers"
                    :items="referrals"
                    :items-length="totalItems"
                    :loading="loading"
                    @update:options="fetchReferrals"
                >
                    <template v-slot:item.code="{ item }">
                        <v-chip size="small" color="primary" variant="outlined">{{ item.code }}</v-chip>
                    </template>

                    <template v-slot:item.type="{ item }">
                        <v-chip :color="getTypeColor(item.type)" size="small">{{ item.type }}</v-chip>
                    </template>

                    <template v-slot:item.discount="{ item }">
                        {{ item.discount_value }}{{ item.discount_type === 'Percentage' ? '%' : '' }}
                    </template>

                    <template v-slot:item.commission="{ item }">
                        {{ item.commission_value }}{{ item.commission_type === 'Percentage' ? '%' : '' }}
                    </template>

                    <template v-slot:item.stats="{ item }">
                        <div class="text-caption">
                            <div>Referrals: {{ item.total_referrals }}</div>
                            <div>Revenue: {{ formatNumber(item.total_revenue) }}</div>
                        </div>
                    </template>

                    <template v-slot:item.pending_commission="{ item }">
                        <span :class="(item.total_commission_earned - item.total_commission_paid) > 0 ? 'text-warning font-weight-bold' : ''">
                            {{ formatNumber(item.total_commission_earned - item.total_commission_paid) }}
                        </span>
                    </template>

                    <template v-slot:item.is_active="{ item }">
                        <v-chip :color="item.is_active ? 'success' : 'grey'" size="small">
                            {{ item.is_active ? 'Active' : 'Inactive' }}
                        </v-chip>
                    </template>

                    <template v-slot:item.actions="{ item }">
                        <v-btn icon variant="text" size="small" color="info" @click="viewStats(item)">
                            <v-icon>mdi-chart-bar</v-icon>
                        </v-btn>
                        <v-btn icon variant="text" size="small" color="warning" @click="openDialog(item)">
                            <v-icon>mdi-pencil</v-icon>
                        </v-btn>
                        <v-btn icon variant="text" size="small" color="error" @click="confirmDelete(item)">
                            <v-icon>mdi-delete</v-icon>
                        </v-btn>
                    </template>
                </v-data-table-server>
            </v-card-text>
        </v-card>

        <!-- Create/Edit Dialog -->
        <v-dialog v-model="dialog" max-width="600" persistent>
            <v-card>
                <v-card-title>{{ editingId ? 'Edit Referral' : 'New Referral' }}</v-card-title>
                <v-card-text>
                    <v-form ref="formRef">
                        <v-row>
                            <v-col cols="12" md="6">
                                <v-text-field v-model="form.name" label="Name *" :rules="[v => !!v || 'Name is required']" />
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-select v-model="form.type" label="Type *" :items="referralTypes" :rules="[v => !!v || 'Type is required']" />
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-text-field v-model="form.phone" label="Phone" />
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-text-field v-model="form.email" label="Email" type="email" />
                            </v-col>

                            <v-col cols="12">
                                <v-divider class="my-2" />
                                <div class="text-subtitle-2 mb-2">Discount Settings</div>
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-select v-model="form.discount_type" label="Discount Type" :items="['Fixed', 'Percentage']" />
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-text-field v-model.number="form.discount_value" label="Discount Value" type="number" min="0" :suffix="form.discount_type === 'Percentage' ? '%' : ''" />
                            </v-col>

                            <v-col cols="12">
                                <v-divider class="my-2" />
                                <div class="text-subtitle-2 mb-2">Commission Settings</div>
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-select v-model="form.commission_type" label="Commission Type" :items="['Fixed', 'Percentage']" />
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-text-field v-model.number="form.commission_value" label="Commission Value" type="number" min="0" :suffix="form.commission_type === 'Percentage' ? '%' : ''" />
                            </v-col>

                            <v-col cols="12">
                                <v-divider class="my-2" />
                                <div class="text-subtitle-2 mb-2">Validity</div>
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-text-field v-model="form.valid_from" label="Start Date" type="date" clearable />
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-text-field v-model="form.valid_until" label="End Date" type="date" clearable />
                            </v-col>
                            <v-col cols="12">
                                <v-switch v-model="form.is_active" label="Active" color="primary" />
                            </v-col>
                            <v-col cols="12">
                                <v-textarea v-model="form.notes" label="Notes" rows="2" />
                            </v-col>
                        </v-row>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="closeDialog">Cancel</v-btn>
                    <v-btn color="primary" @click="save" :loading="saving">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Stats Dialog -->
        <v-dialog v-model="statsDialog" max-width="700">
            <v-card>
                <v-card-title class="d-flex align-center">
                    <v-icon class="mr-2">mdi-chart-bar</v-icon>
                    Referral Statistics - {{ selectedReferral?.name }}
                </v-card-title>
                <v-card-text v-if="referralStats">
                    <v-row>
                        <v-col cols="6" md="3">
                            <v-card color="primary" variant="tonal">
                                <v-card-text class="text-center">
                                    <div class="text-h5">{{ referralStats.referral?.total_referrals || 0 }}</div>
                                    <div class="text-caption">Total Referrals</div>
                                </v-card-text>
                            </v-card>
                        </v-col>
                        <v-col cols="6" md="3">
                            <v-card color="success" variant="tonal">
                                <v-card-text class="text-center">
                                    <div class="text-h5">{{ formatNumber(referralStats.referral?.total_revenue || 0) }}</div>
                                    <div class="text-caption">Total Revenue</div>
                                </v-card-text>
                            </v-card>
                        </v-col>
                        <v-col cols="6" md="3">
                            <v-card color="info" variant="tonal">
                                <v-card-text class="text-center">
                                    <div class="text-h5">{{ formatNumber(referralStats.referral?.total_commission_earned || 0) }}</div>
                                    <div class="text-caption">Earned Commission</div>
                                </v-card-text>
                            </v-card>
                        </v-col>
                        <v-col cols="6" md="3">
                            <v-card color="warning" variant="tonal">
                                <v-card-text class="text-center">
                                    <div class="text-h5">{{ formatNumber(referralStats.pending_commission || 0) }}</div>
                                    <div class="text-caption">Pending Commission</div>
                                </v-card-text>
                            </v-card>
                        </v-col>
                    </v-row>

                    <v-divider class="my-4" />

                    <div class="text-subtitle-1 mb-2">Recent Ledger Entries</div>
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
                            <tr v-for="entry in referralStats.ledger_entries" :key="entry.id">
                                <td>{{ formatDate(entry.created_at) }}</td>
                                <td><v-chip :color="entry.type === 'Earned' ? 'success' : entry.type === 'Paid' ? 'info' : 'warning'" size="x-small">{{ entry.type }}</v-chip></td>
                                <td>{{ entry.invoice?.invoice_no || '-' }}</td>
                                <td>{{ entry.description }}</td>
                                <td class="text-end" :class="entry.amount < 0 ? 'text-error' : 'text-success'">{{ formatNumber(entry.amount) }}</td>
                            </tr>
                        </tbody>
                    </v-table>
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="statsDialog = false">Close</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete Dialog -->
        <v-dialog v-model="deleteDialog" max-width="400">
            <v-card>
                <v-card-title>Delete Referral</v-card-title>
                <v-card-text>Are you sure you want to delete "{{ selectedReferral?.name }}"?</v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="deleteDialog = false">Cancel</v-btn>
                    <v-btn color="error" @click="deleteReferral" :loading="deleting">Delete</v-btn>
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
const deleting = ref(false);
const referrals = ref([]);
const totalItems = ref(0);
const page = ref(1);
const itemsPerPage = ref(15);
const search = ref('');
const filterType = ref(null);
const activeOnly = ref(false);
const dialog = ref(false);
const statsDialog = ref(false);
const deleteDialog = ref(false);
const editingId = ref(null);
const selectedReferral = ref(null);
const referralStats = ref(null);
const formRef = ref(null);

const referralTypes = ['Doctor', 'Agent', 'Patient', 'Staff', 'Other'];

const form = reactive({
    name: '',
    type: 'Agent',
    phone: '',
    email: '',
    discount_type: 'Percentage',
    discount_value: 0,
    commission_type: 'Percentage',
    commission_value: 0,
    valid_from: null,
    valid_until: null,
    is_active: true,
    notes: '',
});

const snackbar = reactive({ show: false, message: '', color: 'success' });

const headers = [
    { title: 'Code', key: 'code' },
    { title: 'Name', key: 'name' },
    { title: 'Type', key: 'type' },
    { title: 'Phone', key: 'phone' },
    { title: 'Discount', key: 'discount' },
    { title: 'Commission', key: 'commission' },
    { title: 'Statistics', key: 'stats', sortable: false },
    { title: 'Pending Commission', key: 'pending_commission' },
    { title: 'Status', key: 'is_active' },
    { title: 'Actions', key: 'actions', sortable: false },
];

const getTypeColor = (type) => ({
    Doctor: 'primary',
    Agent: 'success',
    Patient: 'info',
    Staff: 'warning',
    Other: 'grey',
}[type] || 'grey');

const formatNumber = (num) => Number(num || 0).toLocaleString('en-BD');
const formatDate = (date) => new Date(date).toLocaleDateString('en-GB');

let searchTimeout = null;
const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => { page.value = 1; fetchReferrals(); }, 500);
};

const fetchReferrals = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/billing/referrals', {
            params: {
                page: page.value,
                per_page: itemsPerPage.value,
                search: search.value,
                type: filterType.value,
                active_only: activeOnly.value,
            },
        });
        referrals.value = response.data.data || [];
        totalItems.value = response.data.total || 0;
    } catch (error) {
        console.error('Failed to fetch referrals:', error);
    }
    loading.value = false;
};

const openDialog = (item = null) => {
    if (item) {
        editingId.value = item.id;
        Object.assign(form, {
            name: item.name,
            type: item.type,
            phone: item.phone || '',
            email: item.email || '',
            discount_type: item.discount_type,
            discount_value: item.discount_value,
            commission_type: item.commission_type,
            commission_value: item.commission_value,
            valid_from: item.valid_from,
            valid_until: item.valid_until,
            is_active: item.is_active,
            notes: item.notes || '',
        });
    } else {
        editingId.value = null;
        Object.assign(form, {
            name: '', type: 'Agent', phone: '', email: '',
            discount_type: 'Percentage', discount_value: 0,
            commission_type: 'Percentage', commission_value: 0,
            valid_from: null, valid_until: null, is_active: true, notes: '',
        });
    }
    dialog.value = true;
};

const closeDialog = () => {
    dialog.value = false;
    editingId.value = null;
};

const save = async () => {
    const { valid } = await formRef.value.validate();
    if (!valid) return;

    saving.value = true;
    try {
        if (editingId.value) {
            await axios.put(`/api/billing/referrals/${editingId.value}`, form);
            snackbar.message = 'Referral updated successfully';
        } else {
            await axios.post('/api/billing/referrals', form);
            snackbar.message = 'Referral created successfully';
        }
        snackbar.color = 'success';
        snackbar.show = true;
        closeDialog();
        fetchReferrals();
    } catch (error) {
        snackbar.message = error.response?.data?.message || 'An error occurred';
        snackbar.color = 'error';
        snackbar.show = true;
    }
    saving.value = false;
};

const viewStats = async (item) => {
    selectedReferral.value = item;
    statsDialog.value = true;
    try {
        const response = await axios.get(`/api/billing/referrals/${item.id}/stats`);
        referralStats.value = response.data;
    } catch (error) {
        console.error('Failed to fetch stats:', error);
    }
};

const confirmDelete = (item) => {
    selectedReferral.value = item;
    deleteDialog.value = true;
};

const deleteReferral = async () => {
    deleting.value = true;
    try {
        await axios.delete(`/api/billing/referrals/${selectedReferral.value.id}`);
        snackbar.message = 'Referral deleted successfully';
        snackbar.color = 'success';
        snackbar.show = true;
        deleteDialog.value = false;
        fetchReferrals();
    } catch (error) {
        snackbar.message = error.response?.data?.message || 'Failed to delete';
        snackbar.color = 'error';
        snackbar.show = true;
    }
    deleting.value = false;
};

onMounted(() => fetchReferrals());
</script>
