<template>
    <div>
        <v-card>
            <v-card-title class="d-flex align-center">
                <v-btn icon variant="text" :to="{ name: 'doctors.index' }" class="mr-2">
                    <v-icon>mdi-arrow-left</v-icon>
                </v-btn>
                <v-icon class="mr-2">mdi-doctor</v-icon>
                Add New Doctor
            </v-card-title>

            <v-card-text>
                <v-form ref="form" @submit.prevent="submitForm">
                    <v-row>
                        <v-col cols="12" md="6">
                            <v-text-field
                                v-model="formData.name"
                                label="Full Name *"
                                :rules="[rules.required]"
                                prepend-inner-icon="mdi-account"
                            />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field
                                v-model="formData.phone"
                                label="Phone *"
                                :rules="[rules.required]"
                                prepend-inner-icon="mdi-phone"
                            />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field
                                v-model="formData.email"
                                label="Email"
                                type="email"
                                prepend-inner-icon="mdi-email"
                            />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-select
                                v-model="formData.specialization"
                                label="Specialization *"
                                :items="specializations"
                                :rules="[rules.required]"
                                prepend-inner-icon="mdi-medical-bag"
                            />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field
                                v-model="formData.qualification"
                                label="Qualification"
                                prepend-inner-icon="mdi-certificate"
                            />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field
                                v-model="formData.registration_no"
                                label="Registration No."
                                prepend-inner-icon="mdi-card-account-details"
                            />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field
                                v-model="formData.commission_percentage"
                                label="Commission %"
                                type="number"
                                min="0"
                                max="100"
                                prepend-inner-icon="mdi-percent"
                            />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-switch
                                v-model="formData.is_active"
                                label="Active"
                                color="primary"
                            />
                        </v-col>
                        <v-col cols="12">
                            <v-textarea
                                v-model="formData.address"
                                label="Address"
                                rows="2"
                                prepend-inner-icon="mdi-home"
                            />
                        </v-col>
                        <v-col cols="12">
                            <v-textarea
                                v-model="formData.notes"
                                label="Notes"
                                rows="2"
                                prepend-inner-icon="mdi-note"
                            />
                        </v-col>
                    </v-row>

                    <v-divider class="my-4" />

                    <div class="d-flex justify-end ga-2">
                        <v-btn variant="outlined" :to="{ name: 'doctors.index' }">Cancel</v-btn>
                        <v-btn color="primary" type="submit" :loading="loading">
                            <v-icon start>mdi-content-save</v-icon>
                            Save Doctor
                        </v-btn>
                    </div>
                </v-form>
            </v-card-text>
        </v-card>

        <v-snackbar v-model="snackbar.show" :color="snackbar.color">
            {{ snackbar.message }}
        </v-snackbar>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const form = ref(null);
const loading = ref(false);
const specializations = ref([]);

const formData = reactive({
    name: '',
    phone: '',
    email: '',
    specialization: '',
    qualification: '',
    registration_no: '',
    commission_percentage: 0,
    is_active: true,
    address: '',
    notes: '',
});

const snackbar = reactive({ show: false, message: '', color: 'success' });
const rules = { required: (v) => !!v || 'This field is required' };

const fetchSpecializations = async () => {
    try {
        const response = await axios.get('/api/specializations');
        specializations.value = response.data.map(s => s.name);
    } catch (error) {
        specializations.value = ['General Medicine', 'Cardiology', 'Neurology', 'Orthopedics', 'Dermatology', 'Pediatrics', 'Radiology', 'Pathology'];
    }
};

const submitForm = async () => {
    const { valid } = await form.value.validate();
    if (!valid) return;

    loading.value = true;
    try {
        await axios.post('/api/doctors', formData);
        snackbar.message = 'Doctor created successfully';
        snackbar.color = 'success';
        snackbar.show = true;
        setTimeout(() => router.push({ name: 'doctors.index' }), 1000);
    } catch (error) {
        snackbar.message = error.response?.data?.message || 'Failed to create doctor';
        snackbar.color = 'error';
        snackbar.show = true;
    }
    loading.value = false;
};

onMounted(() => fetchSpecializations());
</script>
