<template>
    <div>
        <v-card>
            <v-card-title class="d-flex align-center justify-space-between flex-wrap ga-2">
                <div class="d-flex align-center">
                    <v-icon class="mr-2">mdi-test-tube</v-icon>
                    Tests / Services
                </div>
                <v-btn color="primary" :to="{ name: 'tests.create' }">
                    <v-icon start>mdi-plus</v-icon>
                    Add Test
                </v-btn>
            </v-card-title>

            <v-card-text>
                <v-row class="mb-4">
                    <v-col cols="12" md="4">
                        <v-text-field v-model="search" label="Search tests..." prepend-inner-icon="mdi-magnify" clearable hide-details @input="debouncedSearch" />
                    </v-col>
                    <v-col cols="12" md="3">
                        <v-select v-model="filters.category" label="Category" :items="categories" item-title="name" item-value="id" clearable hide-details @update:model-value="fetchTests" />
                    </v-col>
                </v-row>

                <v-data-table-server
                    v-model:items-per-page="itemsPerPage"
                    v-model:page="page"
                    :headers="headers"
                    :items="tests"
                    :items-length="totalItems"
                    :loading="loading"
                    @update:options="fetchTests"
                >
                    <template v-slot:item.name="{ item }">
                        <div>
                            <p class="font-weight-medium mb-0">{{ item.name }}</p>
                            <p class="text-caption text-grey mb-0">{{ item.category?.name }}</p>
                        </div>
                    </template>

                    <template v-slot:item.price="{ item }">
                        <span class="font-weight-medium">{{ item.price }}</span>
                    </template>

                    <template v-slot:item.status="{ item }">
                        <v-chip :color="item.is_active ? 'success' : 'error'" size="small">
                            {{ item.is_active ? 'Active' : 'Inactive' }}
                        </v-chip>
                    </template>

                    <template v-slot:item.actions="{ item }">
                        <v-btn icon variant="text" size="small" color="primary" :to="{ name: 'tests.edit', params: { id: item.id } }">
                            <v-icon>mdi-pencil</v-icon>
                        </v-btn>
                        <v-btn icon variant="text" size="small" color="error" @click="confirmDelete(item)">
                            <v-icon>mdi-delete</v-icon>
                        </v-btn>
                    </template>
                </v-data-table-server>
            </v-card-text>
        </v-card>

        <v-dialog v-model="deleteDialog" max-width="400">
            <v-card>
                <v-card-title>Confirm Delete</v-card-title>
                <v-card-text>Delete test <strong>{{ selectedTest?.name }}</strong>?</v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="deleteDialog = false">Cancel</v-btn>
                    <v-btn color="error" @click="deleteTest" :loading="deleting">Delete</v-btn>
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
const deleting = ref(false);
const tests = ref([]);
const categories = ref([]);
const totalItems = ref(0);
const page = ref(1);
const itemsPerPage = ref(10);
const search = ref('');
const deleteDialog = ref(false);
const selectedTest = ref(null);

const filters = reactive({ category: null });
const snackbar = reactive({ show: false, message: '', color: 'success' });

const headers = [
    { title: 'Test Name', key: 'name', sortable: true },
    { title: 'Code', key: 'code' },
    { title: 'Price', key: 'price', sortable: true },
    { title: 'Duration', key: 'duration' },
    { title: 'Status', key: 'status' },
    { title: 'Actions', key: 'actions', sortable: false, align: 'center' },
];

let searchTimeout = null;
const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => { page.value = 1; fetchTests(); }, 500);
};

const fetchTests = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/tests', {
            params: { page: page.value, per_page: itemsPerPage.value, search: search.value, category_id: filters.category },
        });
        tests.value = response.data.data || [];
        totalItems.value = response.data.total || 0;
    } catch (error) { console.error('Failed to fetch tests:', error); }
    loading.value = false;
};

const fetchCategories = async () => {
    try {
        const response = await axios.get('/api/test-categories');
        categories.value = response.data.data || [];
    } catch (error) { console.error('Failed to fetch categories:', error); }
};

const confirmDelete = (test) => { selectedTest.value = test; deleteDialog.value = true; };

const deleteTest = async () => {
    deleting.value = true;
    try {
        await axios.delete(`/api/tests/${selectedTest.value.id}`);
        snackbar.message = 'Test deleted successfully'; snackbar.color = 'success'; snackbar.show = true;
        deleteDialog.value = false; fetchTests();
    } catch (error) { snackbar.message = 'Failed to delete test'; snackbar.color = 'error'; snackbar.show = true; }
    deleting.value = false;
};

onMounted(() => { fetchTests(); fetchCategories(); });
</script>
