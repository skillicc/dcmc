<template>
    <div>
        <v-card :loading="pageLoading">
            <v-card-title class="d-flex align-center">
                <v-btn icon variant="text" :to="{ name: 'patients.index' }" class="mr-2">
                    <v-icon>mdi-arrow-left</v-icon>
                </v-btn>
                <v-icon class="mr-2">mdi-account-edit</v-icon>
                Edit Patient
            </v-card-title>

            <v-card-text>
                <v-form ref="form" @submit.prevent="submitForm">
                    <v-row>
                        <!-- Basic Information -->
                        <v-col cols="12">
                            <h3 class="text-subtitle-1 font-weight-bold mb-3">
                                <v-icon class="mr-1" size="small">mdi-account</v-icon>
                                Basic Information
                            </h3>
                        </v-col>

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
                                label="Phone Number *"
                                :rules="[rules.required, rules.phone]"
                                prepend-inner-icon="mdi-phone"
                            />
                        </v-col>

                        <v-col cols="12" md="6">
                            <v-text-field
                                v-model="formData.email"
                                label="Email"
                                type="email"
                                :rules="[rules.email]"
                                prepend-inner-icon="mdi-email"
                            />
                        </v-col>

                        <v-col cols="12" md="6">
                            <v-text-field
                                v-model="formData.date_of_birth"
                                label="Date of Birth"
                                type="date"
                                prepend-inner-icon="mdi-calendar"
                                @update:model-value="calculateAge"
                            />
                        </v-col>

                        <v-col cols="12" md="4">
                            <v-text-field
                                v-model="formData.age"
                                label="Age *"
                                type="number"
                                :rules="[rules.required]"
                                prepend-inner-icon="mdi-numeric"
                            />
                        </v-col>

                        <v-col cols="12" md="4">
                            <v-select
                                v-model="formData.gender"
                                label="Gender *"
                                :items="genderOptions"
                                :rules="[rules.required]"
                                prepend-inner-icon="mdi-gender-male-female"
                            />
                        </v-col>

                        <v-col cols="12" md="4">
                            <v-select
                                v-model="formData.blood_group"
                                label="Blood Group"
                                :items="bloodGroups"
                                prepend-inner-icon="mdi-water"
                            />
                        </v-col>

                        <!-- Contact Information -->
                        <v-col cols="12" class="mt-4">
                            <h3 class="text-subtitle-1 font-weight-bold mb-3">
                                <v-icon class="mr-1" size="small">mdi-map-marker</v-icon>
                                Contact Information
                            </h3>
                        </v-col>

                        <v-col cols="12">
                            <v-textarea
                                v-model="formData.address"
                                label="Address"
                                rows="2"
                                prepend-inner-icon="mdi-home"
                            />
                        </v-col>

                        <v-col cols="12" md="6">
                            <v-text-field
                                v-model="formData.emergency_contact_name"
                                label="Emergency Contact Name"
                                prepend-inner-icon="mdi-account-alert"
                            />
                        </v-col>

                        <v-col cols="12" md="6">
                            <v-text-field
                                v-model="formData.emergency_contact_phone"
                                label="Emergency Contact Phone"
                                prepend-inner-icon="mdi-phone-alert"
                            />
                        </v-col>

                        <!-- Medical Information -->
                        <v-col cols="12" class="mt-4">
                            <h3 class="text-subtitle-1 font-weight-bold mb-3">
                                <v-icon class="mr-1" size="small">mdi-medical-bag</v-icon>
                                Medical Information
                            </h3>
                        </v-col>

                        <v-col cols="12" md="6">
                            <v-textarea
                                v-model="formData.allergies"
                                label="Known Allergies"
                                rows="2"
                                prepend-inner-icon="mdi-alert-circle"
                            />
                        </v-col>

                        <v-col cols="12" md="6">
                            <v-textarea
                                v-model="formData.medical_history"
                                label="Medical History"
                                rows="2"
                                prepend-inner-icon="mdi-history"
                            />
                        </v-col>

                        <v-col cols="12">
                            <v-textarea
                                v-model="formData.notes"
                                label="Additional Notes"
                                rows="2"
                                prepend-inner-icon="mdi-note-text"
                            />
                        </v-col>
                    </v-row>

                    <v-divider class="my-4" />

                    <div class="d-flex justify-end ga-2">
                        <v-btn variant="outlined" :to="{ name: 'patients.index' }">
                            Cancel
                        </v-btn>
                        <v-btn color="primary" type="submit" :loading="loading">
                            <v-icon start>mdi-content-save</v-icon>
                            Update Patient
                        </v-btn>
                    </div>
                </v-form>
            </v-card-text>
        </v-card>

        <!-- Snackbar -->
        <v-snackbar v-model="snackbar.show" :color="snackbar.color" :timeout="3000">
            {{ snackbar.message }}
        </v-snackbar>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const route = useRoute();
const form = ref(null);
const loading = ref(false);
const pageLoading = ref(true);

const formData = reactive({
    name: '',
    phone: '',
    email: '',
    date_of_birth: '',
    age: '',
    gender: '',
    blood_group: '',
    address: '',
    emergency_contact_name: '',
    emergency_contact_phone: '',
    allergies: '',
    medical_history: '',
    notes: '',
});

const snackbar = reactive({
    show: false,
    message: '',
    color: 'success',
});

const genderOptions = ['Male', 'Female', 'Other'];
const bloodGroups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

const rules = {
    required: (v) => !!v || 'This field is required',
    email: (v) => !v || /.+@.+\..+/.test(v) || 'Invalid email',
    phone: (v) => !v || /^[0-9+\-\s()]+$/.test(v) || 'Invalid phone number',
};

const fetchPatient = async () => {
    pageLoading.value = true;
    try {
        const response = await axios.get(`/api/patients/${route.params.id}`);
        Object.assign(formData, response.data.data);
    } catch (error) {
        showSnackbar('Failed to load patient data', 'error');
    }
    pageLoading.value = false;
};

const calculateAge = () => {
    if (formData.date_of_birth) {
        const today = new Date();
        const birthDate = new Date(formData.date_of_birth);
        let age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
        formData.age = age;
    }
};

const submitForm = async () => {
    const { valid } = await form.value.validate();
    if (!valid) return;

    loading.value = true;
    try {
        await axios.put(`/api/patients/${route.params.id}`, formData);
        showSnackbar('Patient updated successfully', 'success');
        setTimeout(() => {
            router.push({ name: 'patients.index' });
        }, 1000);
    } catch (error) {
        const message = error.response?.data?.message || 'Failed to update patient';
        showSnackbar(message, 'error');
    }
    loading.value = false;
};

const showSnackbar = (message, color = 'success') => {
    snackbar.message = message;
    snackbar.color = color;
    snackbar.show = true;
};

onMounted(() => {
    fetchPatient();
});
</script>
