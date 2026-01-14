<template>
    <div>
        <v-card>
            <v-card-title class="d-flex align-center justify-space-between flex-wrap ga-2">
                <div class="d-flex align-center">
                    <v-icon class="mr-2">mdi-folder-multiple</v-icon>
                    Test Categories
                </div>
                <v-btn color="primary" @click="openDialog()">
                    <v-icon start>mdi-plus</v-icon>
                    Add Category
                </v-btn>
            </v-card-title>

            <v-card-text>
                <v-data-table
                    :headers="headers"
                    :items="categories"
                    :loading="loading"
                >
                    <template v-slot:item.status="{ item }">
                        <v-chip :color="item.is_active ? 'success' : 'error'" size="small">
                            {{ item.is_active ? 'Active' : 'Inactive' }}
                        </v-chip>
                    </template>
                    <template v-slot:item.actions="{ item }">
                        <v-btn icon variant="text" size="small" color="primary" @click="openDialog(item)">
                            <v-icon>mdi-pencil</v-icon>
                        </v-btn>
                        <v-btn icon variant="text" size="small" color="error" @click="confirmDelete(item)">
                            <v-icon>mdi-delete</v-icon>
                        </v-btn>
                    </template>
                </v-data-table>
            </v-card-text>
        </v-card>

        <!-- Add/Edit Dialog -->
        <v-dialog v-model="dialog" max-width="500">
            <v-card>
                <v-card-title>{{ editMode ? 'Edit' : 'Add' }} Category</v-card-title>
                <v-card-text>
                    <v-form ref="form">
                        <v-text-field v-model="formData.name" label="Category Name *" :rules="[rules.required]" class="mb-2" />
                        <v-textarea v-model="formData.description" label="Description" rows="2" class="mb-2" />
                        <v-switch v-model="formData.is_active" label="Active" color="primary" />
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="dialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="saveCategory" :loading="saving">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete Dialog -->
        <v-dialog v-model="deleteDialog" max-width="400">
            <v-card>
                <v-card-title>Confirm Delete</v-card-title>
                <v-card-text>Delete category <strong>{{ selectedCategory?.name }}</strong>?</v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="deleteDialog = false">Cancel</v-btn>
                    <v-btn color="error" @click="deleteCategory" :loading="deleting">Delete</v-btn>
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
const categories = ref([]);
const dialog = ref(false);
const deleteDialog = ref(false);
const editMode = ref(false);
const selectedCategory = ref(null);
const form = ref(null);

const formData = reactive({ id: null, name: '', description: '', is_active: true });
const snackbar = reactive({ show: false, message: '', color: 'success' });
const rules = { required: (v) => !!v || 'This field is required' };

const headers = [
    { title: 'Name', key: 'name' },
    { title: 'Description', key: 'description' },
    { title: 'Tests Count', key: 'tests_count' },
    { title: 'Status', key: 'status' },
    { title: 'Actions', key: 'actions', sortable: false },
];

const fetchCategories = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/test-categories');
        categories.value = response.data.data || [];
    } catch (error) { console.error('Failed to fetch categories:', error); }
    loading.value = false;
};

const openDialog = (category = null) => {
    editMode.value = !!category;
    if (category) {
        Object.assign(formData, category);
    } else {
        formData.id = null; formData.name = ''; formData.description = ''; formData.is_active = true;
    }
    dialog.value = true;
};

const saveCategory = async () => {
    const { valid } = await form.value.validate();
    if (!valid) return;

    saving.value = true;
    try {
        if (editMode.value) {
            await axios.put(`/api/test-categories/${formData.id}`, formData);
        } else {
            await axios.post('/api/test-categories', formData);
        }
        snackbar.message = `Category ${editMode.value ? 'updated' : 'created'} successfully`;
        snackbar.color = 'success'; snackbar.show = true;
        dialog.value = false; fetchCategories();
    } catch (error) {
        snackbar.message = error.response?.data?.message || 'Failed to save category';
        snackbar.color = 'error'; snackbar.show = true;
    }
    saving.value = false;
};

const confirmDelete = (category) => { selectedCategory.value = category; deleteDialog.value = true; };

const deleteCategory = async () => {
    deleting.value = true;
    try {
        await axios.delete(`/api/test-categories/${selectedCategory.value.id}`);
        snackbar.message = 'Category deleted successfully'; snackbar.color = 'success'; snackbar.show = true;
        deleteDialog.value = false; fetchCategories();
    } catch (error) { snackbar.message = 'Failed to delete category'; snackbar.color = 'error'; snackbar.show = true; }
    deleting.value = false;
};

onMounted(() => fetchCategories());
</script>
