<template>
    <div>
        <v-card>
            <v-card-title class="d-flex align-center justify-space-between flex-wrap ga-2">
                <div class="d-flex align-center">
                    <v-icon class="mr-2">mdi-star</v-icon>
                    Medicine Favorites
                </div>
                <v-btn color="primary" @click="openDialog()">
                    <v-icon start>mdi-plus</v-icon>
                    Add Favorite
                </v-btn>
            </v-card-title>

            <v-card-text>
                <v-row class="mb-4">
                    <v-col cols="12" md="4">
                        <v-autocomplete
                            v-model="filters.doctor_id"
                            label="Filter by Doctor"
                            :items="doctors"
                            item-title="name"
                            item-value="id"
                            clearable
                            hide-details
                            @update:model-value="fetchFavorites"
                        />
                    </v-col>
                </v-row>

                <v-data-table
                    :headers="headers"
                    :items="favorites"
                    :loading="loading"
                    hover
                >
                    <template v-slot:item.medicine_name="{ item }">
                        <div class="d-flex align-center py-2">
                            <v-avatar color="primary" size="36" class="mr-3">
                                <v-icon color="white" size="small">mdi-pill</v-icon>
                            </v-avatar>
                            <div>
                                <p class="font-weight-medium mb-0">{{ item.medicine_name }}</p>
                                <p class="text-caption text-grey mb-0">{{ item.default_dosage }}</p>
                            </div>
                        </div>
                    </template>

                    <template v-slot:item.doctor="{ item }">
                        {{ item.doctor?.name || '-' }}
                    </template>

                    <template v-slot:item.frequency="{ item }">
                        <v-chip size="x-small" color="info">
                            {{ item.default_frequency }}
                        </v-chip>
                    </template>

                    <template v-slot:item.actions="{ item }">
                        <v-btn
                            icon
                            variant="text"
                            size="small"
                            color="primary"
                            @click="openDialog(item)"
                        >
                            <v-icon>mdi-pencil</v-icon>
                        </v-btn>
                        <v-btn
                            icon
                            variant="text"
                            size="small"
                            color="error"
                            @click="confirmDelete(item)"
                        >
                            <v-icon>mdi-delete</v-icon>
                        </v-btn>
                    </template>
                </v-data-table>
            </v-card-text>
        </v-card>

        <!-- Create/Edit Dialog -->
        <v-dialog v-model="dialog" max-width="600" persistent>
            <v-card>
                <v-card-title>
                    {{ editMode ? 'Edit Favorite' : 'Add Favorite Medicine' }}
                </v-card-title>
                <v-card-text>
                    <v-form ref="formRef" @submit.prevent="saveFavorite">
                        <v-row>
                            <v-col cols="12">
                                <v-autocomplete
                                    v-model="form.doctor_id"
                                    label="Doctor"
                                    :items="doctors"
                                    item-title="name"
                                    item-value="id"
                                    :rules="[v => !!v || 'Doctor is required']"
                                    required
                                />
                            </v-col>
                            <v-col cols="12">
                                <v-text-field
                                    v-model="form.medicine_name"
                                    label="Medicine Name"
                                    :rules="[v => !!v || 'Medicine name is required']"
                                    required
                                />
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-text-field
                                    v-model="form.default_dosage"
                                    label="Default Dosage"
                                    placeholder="e.g., 500mg"
                                />
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-select
                                    v-model="form.default_frequency"
                                    label="Default Frequency"
                                    :items="frequencies"
                                />
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-text-field
                                    v-model="form.default_duration"
                                    label="Default Duration"
                                    placeholder="e.g., 7 days"
                                />
                            </v-col>
                            <v-col cols="12" md="6">
                                <v-text-field
                                    v-model="form.default_instructions"
                                    label="Default Instructions"
                                    placeholder="e.g., After meals"
                                />
                            </v-col>
                        </v-row>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="closeDialog">Cancel</v-btn>
                    <v-btn color="primary" @click="saveFavorite" :loading="saving">
                        {{ editMode ? 'Update' : 'Add' }}
                    </v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Delete Dialog -->
        <v-dialog v-model="deleteDialog" max-width="400">
            <v-card>
                <v-card-title>Confirm Delete</v-card-title>
                <v-card-text>
                    Are you sure you want to remove <strong>{{ selectedFavorite?.medicine_name }}</strong> from favorites?
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="deleteDialog = false">Cancel</v-btn>
                    <v-btn color="error" @click="deleteFavorite" :loading="deleting">Delete</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-snackbar v-model="snackbar.show" :color="snackbar.color">
            {{ snackbar.message }}
        </v-snackbar>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

const loading = ref(false);
const saving = ref(false);
const deleting = ref(false);
const favorites = ref([]);
const doctors = ref([]);
const dialog = ref(false);
const deleteDialog = ref(false);
const editMode = ref(false);
const selectedFavorite = ref(null);
const formRef = ref(null);

const filters = reactive({ doctor_id: null });
const snackbar = reactive({ show: false, message: '', color: 'success' });

const frequencies = [
    'Once daily',
    'Twice daily',
    'Three times daily',
    'Four times daily',
    'Every 4 hours',
    'Every 6 hours',
    'Every 8 hours',
    'Every 12 hours',
    'As needed',
    'At bedtime',
    'Before meals',
    'After meals',
];

const defaultForm = {
    doctor_id: null,
    medicine_name: '',
    default_dosage: '',
    default_frequency: 'Twice daily',
    default_duration: '',
    default_instructions: '',
};

const form = reactive({ ...defaultForm });

const headers = [
    { title: 'Medicine', key: 'medicine_name', sortable: true },
    { title: 'Doctor', key: 'doctor' },
    { title: 'Dosage', key: 'default_dosage' },
    { title: 'Frequency', key: 'frequency' },
    { title: 'Duration', key: 'default_duration' },
    { title: 'Actions', key: 'actions', sortable: false, align: 'center' },
];

const fetchFavorites = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/medicine-favorites', {
            params: { doctor_id: filters.doctor_id },
        });
        favorites.value = response.data.data || [];
    } catch (error) {
        console.error('Failed to fetch favorites:', error);
    }
    loading.value = false;
};

const fetchDoctors = async () => {
    try {
        const response = await axios.get('/api/doctors', { params: { per_page: 100 } });
        doctors.value = response.data.data || [];
    } catch (error) {
        console.error('Failed to fetch doctors:', error);
    }
};

const openDialog = (favorite = null) => {
    if (favorite) {
        editMode.value = true;
        selectedFavorite.value = favorite;
        Object.assign(form, favorite);
    } else {
        editMode.value = false;
        selectedFavorite.value = null;
        Object.assign(form, defaultForm);
    }
    dialog.value = true;
};

const closeDialog = () => {
    dialog.value = false;
    Object.assign(form, defaultForm);
};

const saveFavorite = async () => {
    const { valid } = await formRef.value.validate();
    if (!valid) return;

    saving.value = true;
    try {
        if (editMode.value) {
            await axios.put(`/api/medicine-favorites/${selectedFavorite.value.id}`, form);
            snackbar.message = 'Favorite updated successfully';
        } else {
            await axios.post('/api/medicine-favorites', form);
            snackbar.message = 'Favorite added successfully';
        }
        snackbar.color = 'success';
        snackbar.show = true;
        closeDialog();
        fetchFavorites();
    } catch (error) {
        snackbar.message = error.response?.data?.message || 'Failed to save favorite';
        snackbar.color = 'error';
        snackbar.show = true;
    }
    saving.value = false;
};

const confirmDelete = (favorite) => {
    selectedFavorite.value = favorite;
    deleteDialog.value = true;
};

const deleteFavorite = async () => {
    deleting.value = true;
    try {
        await axios.delete(`/api/medicine-favorites/${selectedFavorite.value.id}`);
        snackbar.message = 'Favorite removed successfully';
        snackbar.color = 'success';
        snackbar.show = true;
        deleteDialog.value = false;
        fetchFavorites();
    } catch (error) {
        snackbar.message = 'Failed to remove favorite';
        snackbar.color = 'error';
        snackbar.show = true;
    }
    deleting.value = false;
};

onMounted(() => {
    fetchFavorites();
    fetchDoctors();
});
</script>
