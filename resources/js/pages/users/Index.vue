<template>
    <div>
        <v-card>
            <v-card-title class="d-flex align-center justify-space-between flex-wrap ga-2">
                <div class="d-flex align-center">
                    <v-icon class="mr-2">mdi-account-cog</v-icon>
                    Users
                </div>
                <v-btn color="primary" :to="{ name: 'users.create' }">
                    <v-icon start>mdi-plus</v-icon>
                    Add User
                </v-btn>
            </v-card-title>

            <v-card-text>
                <v-row class="mb-4">
                    <v-col cols="12" md="4">
                        <v-text-field v-model="search" label="Search users..." prepend-inner-icon="mdi-magnify" clearable hide-details @input="debouncedSearch" />
                    </v-col>
                    <v-col cols="12" md="3">
                        <v-select v-model="filters.role" label="Role" :items="roles" clearable hide-details @update:model-value="fetchUsers" />
                    </v-col>
                </v-row>

                <v-data-table-server
                    v-model:items-per-page="itemsPerPage"
                    v-model:page="page"
                    :headers="headers"
                    :items="users"
                    :items-length="totalItems"
                    :loading="loading"
                    @update:options="fetchUsers"
                >
                    <template v-slot:item.name="{ item }">
                        <div class="d-flex align-center py-2">
                            <v-avatar color="primary" size="36" class="mr-3">
                                <span class="text-white text-caption">{{ getInitials(item.name) }}</span>
                            </v-avatar>
                            <div>
                                <p class="font-weight-medium mb-0">{{ item.name }}</p>
                                <p class="text-caption text-grey mb-0">{{ item.email }}</p>
                            </div>
                        </div>
                    </template>

                    <template v-slot:item.role="{ item }">
                        <v-chip :color="getRoleColor(item.role)" size="small">{{ item.role }}</v-chip>
                    </template>

                    <template v-slot:item.status="{ item }">
                        <v-chip :color="item.is_active ? 'success' : 'error'" size="small">
                            {{ item.is_active ? 'Active' : 'Inactive' }}
                        </v-chip>
                    </template>

                    <template v-slot:item.actions="{ item }">
                        <v-btn icon variant="text" size="small" color="primary" :to="{ name: 'users.edit', params: { id: item.id } }">
                            <v-icon>mdi-pencil</v-icon>
                        </v-btn>
                        <v-btn icon variant="text" size="small" color="error" @click="confirmDelete(item)" :disabled="item.id === currentUserId">
                            <v-icon>mdi-delete</v-icon>
                        </v-btn>
                    </template>
                </v-data-table-server>
            </v-card-text>
        </v-card>

        <v-dialog v-model="deleteDialog" max-width="400">
            <v-card>
                <v-card-title>Confirm Delete</v-card-title>
                <v-card-text>Delete user <strong>{{ selectedUser?.name }}</strong>?</v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn variant="text" @click="deleteDialog = false">Cancel</v-btn>
                    <v-btn color="error" @click="deleteUser" :loading="deleting">Delete</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <v-snackbar v-model="snackbar.show" :color="snackbar.color">{{ snackbar.message }}</v-snackbar>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';
import axios from 'axios';

const authStore = useAuthStore();
const currentUserId = computed(() => authStore.user?.id);

const loading = ref(false);
const deleting = ref(false);
const users = ref([]);
const totalItems = ref(0);
const page = ref(1);
const itemsPerPage = ref(10);
const search = ref('');
const deleteDialog = ref(false);
const selectedUser = ref(null);

const roles = ['Admin', 'Manager', 'Receptionist', 'Lab Technician', 'Accountant'];
const filters = reactive({ role: null });
const snackbar = reactive({ show: false, message: '', color: 'success' });

const headers = [
    { title: 'User', key: 'name' },
    { title: 'Phone', key: 'phone' },
    { title: 'Role', key: 'role' },
    { title: 'Status', key: 'status' },
    { title: 'Created', key: 'created_at' },
    { title: 'Actions', key: 'actions', sortable: false },
];

const getInitials = (name) => name?.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2) || 'US';
const getRoleColor = (role) => ({ Admin: 'error', Manager: 'primary', Receptionist: 'info', 'Lab Technician': 'success', Accountant: 'warning' }[role] || 'grey');

let searchTimeout = null;
const debouncedSearch = () => { clearTimeout(searchTimeout); searchTimeout = setTimeout(() => { page.value = 1; fetchUsers(); }, 500); };

const fetchUsers = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/users', {
            params: { page: page.value, per_page: itemsPerPage.value, search: search.value, ...filters },
        });
        users.value = response.data.data || [];
        totalItems.value = response.data.total || 0;
    } catch (error) { console.error('Failed to fetch users:', error); }
    loading.value = false;
};

const confirmDelete = (user) => { selectedUser.value = user; deleteDialog.value = true; };

const deleteUser = async () => {
    deleting.value = true;
    try {
        await axios.delete(`/api/users/${selectedUser.value.id}`);
        snackbar.message = 'User deleted successfully'; snackbar.color = 'success'; snackbar.show = true;
        deleteDialog.value = false; fetchUsers();
    } catch (error) { snackbar.message = 'Failed to delete user'; snackbar.color = 'error'; snackbar.show = true; }
    deleting.value = false;
};

onMounted(() => fetchUsers());
</script>
