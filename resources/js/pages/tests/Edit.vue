<template>
    <div>
        <v-card :loading="pageLoading">
            <v-card-title class="d-flex align-center">
                <v-btn icon variant="text" :to="{ name: 'tests.index' }" class="mr-2"><v-icon>mdi-arrow-left</v-icon></v-btn>
                <v-icon class="mr-2">mdi-test-tube</v-icon>
                Edit Test
            </v-card-title>

            <v-card-text>
                <v-form ref="form" @submit.prevent="submitForm">
                    <v-row>
                        <v-col cols="12" md="6">
                            <v-text-field v-model="formData.name" label="Test Name *" :rules="[rules.required]" prepend-inner-icon="mdi-test-tube" />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field v-model="formData.code" label="Test Code" prepend-inner-icon="mdi-barcode" />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-select v-model="formData.category_id" label="Category *" :items="categories" item-title="name" item-value="id" :rules="[rules.required]" prepend-inner-icon="mdi-folder" />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field v-model="formData.price" label="Price *" type="number" :rules="[rules.required]" prepend-inner-icon="mdi-currency-bdt" />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field v-model="formData.duration" label="Duration" prepend-inner-icon="mdi-clock" />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-text-field v-model="formData.sample_type" label="Sample Type" prepend-inner-icon="mdi-water" />
                        </v-col>
                        <v-col cols="12" md="6">
                            <v-switch v-model="formData.is_active" label="Active" color="primary" />
                        </v-col>
                        <v-col cols="12">
                            <v-textarea v-model="formData.description" label="Description" rows="2" prepend-inner-icon="mdi-text" />
                        </v-col>
                        <v-col cols="12">
                            <v-textarea v-model="formData.normal_range" label="Normal Range" rows="3" prepend-inner-icon="mdi-chart-line" />
                        </v-col>
                    </v-row>

                    <v-divider class="my-4" />

                    <div class="d-flex justify-end ga-2">
                        <v-btn variant="outlined" :to="{ name: 'tests.index' }">Cancel</v-btn>
                        <v-btn color="primary" type="submit" :loading="loading">
                            <v-icon start>mdi-content-save</v-icon>Update Test
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
const categories = ref([]);

const formData = reactive({
    name: '', code: '', category_id: null, price: '', duration: '',
    sample_type: '', is_active: true, description: '', normal_range: '',
});

const snackbar = reactive({ show: false, message: '', color: 'success' });
const rules = { required: (v) => !!v || 'This field is required' };

const fetchTest = async () => {
    pageLoading.value = true;
    try {
        const response = await axios.get(`/api/tests/${route.params.id}`);
        Object.assign(formData, response.data.data);
    } catch (error) { snackbar.message = 'Failed to load test'; snackbar.color = 'error'; snackbar.show = true; }
    pageLoading.value = false;
};

const fetchCategories = async () => {
    try {
        const response = await axios.get('/api/test-categories');
        categories.value = response.data.data || [];
    } catch (error) { console.error('Failed to fetch categories:', error); }
};

const submitForm = async () => {
    const { valid } = await form.value.validate();
    if (!valid) return;

    loading.value = true;
    try {
        await axios.put(`/api/tests/${route.params.id}`, formData);
        snackbar.message = 'Test updated successfully'; snackbar.color = 'success'; snackbar.show = true;
        setTimeout(() => router.push({ name: 'tests.index' }), 1000);
    } catch (error) {
        snackbar.message = error.response?.data?.message || 'Failed to update test';
        snackbar.color = 'error'; snackbar.show = true;
    }
    loading.value = false;
};

onMounted(() => { fetchTest(); fetchCategories(); });
</script>
