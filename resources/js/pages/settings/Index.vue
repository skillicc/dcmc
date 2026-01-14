<template>
    <div>
        <v-card>
            <v-card-title class="d-flex align-center">
                <v-icon class="mr-2">mdi-cog</v-icon>
                Settings
            </v-card-title>

            <v-card-text>
                <v-tabs v-model="activeTab" color="primary">
                    <v-tab value="general">General</v-tab>
                    <v-tab value="organization">Organization</v-tab>
                    <v-tab value="invoice">Invoice</v-tab>
                    <v-tab value="specializations">Specializations</v-tab>
                </v-tabs>

                <v-tabs-window v-model="activeTab" class="mt-4">
                    <!-- General Settings -->
                    <v-tabs-window-item value="general">
                        <v-form ref="generalForm" @submit.prevent="saveSettings('general')">
                            <v-row>
                                <v-col cols="12" md="6">
                                    <v-text-field v-model="settings.app_name" label="Application Name" />
                                </v-col>
                                <v-col cols="12" md="6">
                                    <v-text-field v-model="settings.currency" label="Currency Symbol" />
                                </v-col>
                                <v-col cols="12" md="6">
                                    <v-text-field v-model="settings.date_format" label="Date Format" placeholder="Y-m-d" />
                                </v-col>
                                <v-col cols="12" md="6">
                                    <v-text-field v-model="settings.time_format" label="Time Format" placeholder="H:i" />
                                </v-col>
                            </v-row>
                            <v-btn color="primary" type="submit" :loading="saving" class="mt-4">
                                <v-icon start>mdi-content-save</v-icon>Save Changes
                            </v-btn>
                        </v-form>
                    </v-tabs-window-item>

                    <!-- Organization Settings -->
                    <v-tabs-window-item value="organization">
                        <v-form ref="orgForm" @submit.prevent="saveSettings('organization')">
                            <v-row>
                                <v-col cols="12" md="6">
                                    <v-text-field v-model="settings.org_name" label="Organization Name" />
                                </v-col>
                                <v-col cols="12" md="6">
                                    <v-text-field v-model="settings.org_phone" label="Phone" />
                                </v-col>
                                <v-col cols="12" md="6">
                                    <v-text-field v-model="settings.org_email" label="Email" />
                                </v-col>
                                <v-col cols="12" md="6">
                                    <v-text-field v-model="settings.org_website" label="Website" />
                                </v-col>
                                <v-col cols="12">
                                    <v-textarea v-model="settings.org_address" label="Address" rows="2" />
                                </v-col>
                            </v-row>
                            <v-btn color="primary" type="submit" :loading="saving" class="mt-4">
                                <v-icon start>mdi-content-save</v-icon>Save Changes
                            </v-btn>
                        </v-form>
                    </v-tabs-window-item>

                    <!-- Invoice Settings -->
                    <v-tabs-window-item value="invoice">
                        <v-form ref="invoiceForm" @submit.prevent="saveSettings('invoice')">
                            <v-row>
                                <v-col cols="12" md="6">
                                    <v-text-field v-model="settings.invoice_prefix" label="Invoice Prefix" placeholder="INV-" />
                                </v-col>
                                <v-col cols="12" md="6">
                                    <v-text-field v-model="settings.invoice_start_number" label="Starting Number" type="number" />
                                </v-col>
                                <v-col cols="12">
                                    <v-textarea v-model="settings.invoice_footer" label="Invoice Footer Text" rows="2" />
                                </v-col>
                                <v-col cols="12">
                                    <v-textarea v-model="settings.invoice_terms" label="Terms & Conditions" rows="3" />
                                </v-col>
                            </v-row>
                            <v-btn color="primary" type="submit" :loading="saving" class="mt-4">
                                <v-icon start>mdi-content-save</v-icon>Save Changes
                            </v-btn>
                        </v-form>
                    </v-tabs-window-item>

                    <!-- Specializations -->
                    <v-tabs-window-item value="specializations">
                        <div class="d-flex justify-space-between align-center mb-4">
                            <h3 class="text-subtitle-1">Doctor Specializations</h3>
                            <v-btn color="primary" size="small" @click="openSpecDialog()">
                                <v-icon start>mdi-plus</v-icon>Add
                            </v-btn>
                        </div>
                        <v-table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="spec in specializations" :key="spec.id">
                                    <td>{{ spec.name }}</td>
                                    <td>
                                        <v-btn icon variant="text" size="small" @click="openSpecDialog(spec)">
                                            <v-icon>mdi-pencil</v-icon>
                                        </v-btn>
                                        <v-btn icon variant="text" size="small" color="error" @click="deleteSpecialization(spec)">
                                            <v-icon>mdi-delete</v-icon>
                                        </v-btn>
                                    </td>
                                </tr>
                            </tbody>
                        </v-table>
                    </v-tabs-window-item>
                </v-tabs-window>
            </v-card-text>
        </v-card>

        <!-- Specialization Dialog -->
        <v-dialog v-model="specDialog" max-width="400">
            <v-card>
                <v-card-title>{{ editSpec ? 'Edit' : 'Add' }} Specialization</v-card-title>
                <v-card-text>
                    <v-text-field v-model="specName" label="Specialization Name" />
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="specDialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="saveSpecialization" :loading="savingSpec">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-snackbar v-model="snackbar.show" :color="snackbar.color">{{ snackbar.message }}</v-snackbar>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

const activeTab = ref('general');
const saving = ref(false);
const savingSpec = ref(false);
const specDialog = ref(false);
const editSpec = ref(null);
const specName = ref('');
const specializations = ref([]);

const settings = reactive({
    app_name: 'DCMS', currency: '$', date_format: 'Y-m-d', time_format: 'H:i',
    org_name: '', org_phone: '', org_email: '', org_website: '', org_address: '',
    invoice_prefix: 'INV-', invoice_start_number: 1, invoice_footer: '', invoice_terms: '',
});

const snackbar = reactive({ show: false, message: '', color: 'success' });

const fetchSettings = async () => {
    try {
        const response = await axios.get('/api/settings');
        Object.assign(settings, response.data);
    } catch (error) { console.error('Failed to fetch settings:', error); }
};

const saveSettings = async (section) => {
    saving.value = true;
    try {
        await axios.put('/api/settings', settings);
        snackbar.message = 'Settings saved successfully'; snackbar.color = 'success'; snackbar.show = true;
    } catch (error) {
        snackbar.message = 'Failed to save settings'; snackbar.color = 'error'; snackbar.show = true;
    }
    saving.value = false;
};

const fetchSpecializations = async () => {
    try {
        const response = await axios.get('/api/specializations');
        specializations.value = response.data || [];
    } catch (error) { console.error('Failed to fetch specializations:', error); }
};

const openSpecDialog = (spec = null) => {
    editSpec.value = spec;
    specName.value = spec?.name || '';
    specDialog.value = true;
};

const saveSpecialization = async () => {
    savingSpec.value = true;
    try {
        if (editSpec.value) {
            await axios.put(`/api/specializations/${editSpec.value.id}`, { name: specName.value });
        } else {
            await axios.post('/api/specializations', { name: specName.value });
        }
        snackbar.message = 'Specialization saved'; snackbar.color = 'success'; snackbar.show = true;
        specDialog.value = false;
        fetchSpecializations();
    } catch (error) {
        snackbar.message = 'Failed to save'; snackbar.color = 'error'; snackbar.show = true;
    }
    savingSpec.value = false;
};

const deleteSpecialization = async (spec) => {
    if (!confirm(`Delete ${spec.name}?`)) return;
    try {
        await axios.delete(`/api/specializations/${spec.id}`);
        snackbar.message = 'Deleted successfully'; snackbar.color = 'success'; snackbar.show = true;
        fetchSpecializations();
    } catch (error) {
        snackbar.message = 'Failed to delete'; snackbar.color = 'error'; snackbar.show = true;
    }
};

onMounted(() => { fetchSettings(); fetchSpecializations(); });
</script>
