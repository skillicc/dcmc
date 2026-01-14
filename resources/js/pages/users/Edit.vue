<template>
    <div>
        <v-card :loading="pageLoading">
            <v-card-title class="d-flex align-center">
                <v-btn icon variant="text" :to="{ name: 'users.index' }" class="mr-2"><v-icon>mdi-arrow-left</v-icon></v-btn>
                <v-icon class="mr-2">mdi-account-edit</v-icon>
                Edit User
            </v-card-title>

            <v-card-text>
                <v-form ref="form" @submit.prevent="submitForm">
                    <v-row>
                        <v-col cols="12" md="6">
                            <v-text-field v-model="formData.name" label="Full Name *" :rules="[rules.required]" prepend-inner-icon="mdi-account" />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field v-model="formData.email" label="Email *" type="email" :rules="[rules.required, rules.email]" prepend-inner-icon="mdi-email" />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field v-model="formData.phone" label="Phone" prepend-inner-icon="mdi-phone" />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-select v-model="formData.role" label="Role *" :items="roles" :rules="[rules.required]" prepend-inner-icon="mdi-shield-account" />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field
                                v-model="formData.password"
                                label="New Password (leave blank to keep current)"
                                :type="showPassword ? 'text' : 'password'"
                                :rules="formData.password ? [rules.minLength] : []"
                                prepend-inner-icon="mdi-lock"
                                :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
                                @click:append-inner="showPassword = !showPassword"
                            />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field
                                v-model="formData.password_confirmation"
                                label="Confirm New Password"
                                :type="showPassword ? 'text' : 'password'"
                                :rules="formData.password ? [rules.match] : []"
                                prepend-inner-icon="mdi-lock-check"
                            />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-switch v-model="formData.is_active" label="Active" color="primary" />
                        </v-col>
                    </v-row>

                    <v-divider class="my-4" />

                    <div class="d-flex justify-end ga-2">
                        <v-btn variant="outlined" :to="{ name: 'users.index' }">Cancel</v-btn>
                        <v-btn color="primary" type="submit" :loading="loading">
                            <v-icon start>mdi-content-save</v-icon>Update User
                        </v-btn>
                    </div>
                </v-form>
            </v-card-text>
        </v-card>

        <v-snackbar v-model="snackbar.show" :color="snackbar.color">{{ snackbar.message }}</v-snackbar>
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
const showPassword = ref(false);

const roles = ['Admin', 'Manager', 'Receptionist', 'Lab Technician', 'Accountant'];

const formData = reactive({
    name: '', email: '', phone: '', role: '', password: '', password_confirmation: '', is_active: true,
});

const snackbar = reactive({ show: false, message: '', color: 'success' });

const rules = {
    required: (v) => !!v || 'This field is required',
    email: (v) => /.+@.+\..+/.test(v) || 'Invalid email',
    minLength: (v) => v.length >= 8 || 'Min 8 characters',
    match: (v) => v === formData.password || 'Passwords do not match',
};

const fetchUser = async () => {
    pageLoading.value = true;
    try {
        const response = await axios.get(`/api/users/${route.params.id}`);
        Object.assign(formData, response.data.data);
        formData.password = '';
        formData.password_confirmation = '';
    } catch (error) {
        snackbar.message = 'Failed to load user'; snackbar.color = 'error'; snackbar.show = true;
    }
    pageLoading.value = false;
};

const submitForm = async () => {
    const { valid } = await form.value.validate();
    if (!valid) return;

    loading.value = true;
    try {
        const payload = { ...formData };
        if (!payload.password) {
            delete payload.password;
            delete payload.password_confirmation;
        }
        await axios.put(`/api/users/${route.params.id}`, payload);
        snackbar.message = 'User updated successfully'; snackbar.color = 'success'; snackbar.show = true;
        setTimeout(() => router.push({ name: 'users.index' }), 1000);
    } catch (error) {
        snackbar.message = error.response?.data?.message || 'Failed to update user';
        snackbar.color = 'error'; snackbar.show = true;
    }
    loading.value = false;
};

onMounted(() => fetchUser());
</script>
