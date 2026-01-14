<template>
    <v-layout class="rounded rounded-md">
        <!-- Navigation Drawer -->
        <v-navigation-drawer
            v-model="drawer"
            :rail="rail && !mobile"
            :temporary="mobile"
            :permanent="!mobile"
            width="280"
        >
            <!-- Header -->
            <div class="drawer-header pa-4" :class="{ 'pa-2': rail && !mobile }">
                <div class="d-flex align-center" v-if="!rail || mobile">
                    <v-avatar color="primary" size="45" class="mr-3">
                        <v-icon color="white" size="28">mdi-hospital-building</v-icon>
                    </v-avatar>
                    <div>
                        <div class="text-h6 font-weight-bold text-primary">DCMS</div>
                        <div class="text-caption text-grey">Diagnostic Centre</div>
                    </div>
                </div>
                <v-avatar v-else color="primary" size="40" class="mx-auto d-block">
                    <v-icon color="white" size="24">mdi-hospital-building</v-icon>
                </v-avatar>
            </div>

            <v-divider />

            <!-- Menu Groups -->
            <v-list density="compact" nav class="pa-2">
                <!-- Main -->
                <v-list-subheader v-if="!rail || mobile" class="text-uppercase text-caption font-weight-bold">Main</v-list-subheader>
                <v-list-item
                    v-for="item in mainMenuItems"
                    :key="item.title"
                    :prepend-icon="item.icon"
                    :title="rail && !mobile ? '' : item.title"
                    :to="item.to"
                    rounded="lg"
                    color="primary"
                    class="mb-1"
                />

                <v-divider class="my-3" />

                <!-- Patient Management -->
                <v-list-subheader v-if="!rail || mobile" class="text-uppercase text-caption font-weight-bold">Patient</v-list-subheader>
                <v-list-item
                    v-for="item in patientMenuItems"
                    :key="item.title"
                    :prepend-icon="item.icon"
                    :title="rail && !mobile ? '' : item.title"
                    :to="item.to"
                    rounded="lg"
                    color="primary"
                    class="mb-1"
                />

                <v-divider class="my-3" />

                <!-- Doctor & Prescription -->
                <v-list-subheader v-if="!rail || mobile" class="text-uppercase text-caption font-weight-bold">Doctor & Rx</v-list-subheader>
                <v-list-item
                    v-for="item in doctorMenuItems"
                    :key="item.title"
                    :prepend-icon="item.icon"
                    :title="rail && !mobile ? '' : item.title"
                    :to="item.to"
                    rounded="lg"
                    color="primary"
                    class="mb-1"
                />

                <v-divider class="my-3" />

                <!-- Laboratory -->
                <v-list-subheader v-if="!rail || mobile" class="text-uppercase text-caption font-weight-bold">Laboratory</v-list-subheader>
                <v-list-item
                    v-for="item in labMenuItems"
                    :key="item.title"
                    :prepend-icon="item.icon"
                    :title="rail && !mobile ? '' : item.title"
                    :to="item.to"
                    rounded="lg"
                    color="primary"
                    class="mb-1"
                />

                <v-divider class="my-3" />

                <!-- Billing -->
                <v-list-subheader v-if="!rail || mobile" class="text-uppercase text-caption font-weight-bold">Billing</v-list-subheader>
                <v-list-item
                    v-for="item in billingMenuItems"
                    :key="item.title"
                    :prepend-icon="item.icon"
                    :title="rail && !mobile ? '' : item.title"
                    :to="item.to"
                    rounded="lg"
                    color="primary"
                    class="mb-1"
                />

                <v-divider class="my-3" />

                <!-- Reports & Admin -->
                <v-list-subheader v-if="!rail || mobile" class="text-uppercase text-caption font-weight-bold">Reports & Admin</v-list-subheader>
                <v-list-item
                    v-for="item in adminMenuItems"
                    :key="item.title"
                    :prepend-icon="item.icon"
                    :title="rail && !mobile ? '' : item.title"
                    :to="item.to"
                    rounded="lg"
                    color="primary"
                    class="mb-1"
                />
            </v-list>

            <!-- Footer -->
            <template v-slot:append>
                <v-divider />
                <div class="pa-2">
                    <v-btn
                        block
                        :icon="rail && !mobile"
                        variant="text"
                        color="grey-darken-1"
                        @click="rail = !rail"
                        class="mb-2"
                        v-if="!mobile"
                    >
                        <v-icon>{{ rail ? 'mdi-chevron-right' : 'mdi-chevron-left' }}</v-icon>
                        <span v-if="!rail" class="ml-2">Collapse</span>
                    </v-btn>
                    <v-btn
                        block
                        :icon="rail && !mobile"
                        variant="tonal"
                        color="error"
                        @click="handleLogout"
                    >
                        <v-icon>mdi-logout</v-icon>
                        <span v-if="!rail || mobile" class="ml-2">Logout</span>
                    </v-btn>
                </div>
            </template>
        </v-navigation-drawer>

        <!-- App Bar -->
        <v-app-bar flat :color="isDark ? 'surface' : 'white'" elevation="1">
            <v-app-bar-nav-icon @click="drawer = !drawer" />

            <v-app-bar-title class="text-body-1 font-weight-medium">
                {{ pageTitle }}
            </v-app-bar-title>

            <v-spacer />

            <!-- Theme Toggle -->
            <v-btn icon variant="text" @click="toggleTheme" class="mr-1">
                <v-icon>{{ isDark ? 'mdi-weather-sunny' : 'mdi-weather-night' }}</v-icon>
                <v-tooltip activator="parent" location="bottom">
                    {{ isDark ? 'Light Mode' : 'Dark Mode' }}
                </v-tooltip>
            </v-btn>

            <v-menu max-width="350" :close-on-content-click="false">
                <template v-slot:activator="{ props }">
                    <v-btn icon variant="text" class="mr-2" v-bind="props">
                        <v-badge :content="notifications.length" :model-value="notifications.length > 0" color="error" floating>
                            <v-icon>mdi-bell-outline</v-icon>
                        </v-badge>
                    </v-btn>
                </template>
                <v-card>
                    <v-card-title class="d-flex align-center justify-space-between py-2">
                        <span class="text-subtitle-1">Notifications</span>
                        <v-btn v-if="notifications.length" variant="text" size="x-small" color="primary" @click="clearNotifications">
                            Clear All
                        </v-btn>
                    </v-card-title>
                    <v-divider />
                    <v-list density="compact" max-height="300" class="overflow-y-auto">
                        <template v-if="notifications.length">
                            <v-list-item
                                v-for="(notif, index) in notifications"
                                :key="index"
                                :prepend-icon="notif.icon"
                                class="py-2"
                            >
                                <v-list-item-title class="text-body-2">{{ notif.title }}</v-list-item-title>
                                <v-list-item-subtitle class="text-caption">{{ notif.time }}</v-list-item-subtitle>
                                <template v-slot:append>
                                    <v-btn icon size="x-small" variant="text" @click="removeNotification(index)">
                                        <v-icon size="small">mdi-close</v-icon>
                                    </v-btn>
                                </template>
                            </v-list-item>
                        </template>
                        <v-list-item v-else>
                            <v-list-item-title class="text-center text-grey py-4">No notifications</v-list-item-title>
                        </v-list-item>
                    </v-list>
                </v-card>
            </v-menu>

            <v-menu>
                <template v-slot:activator="{ props }">
                    <v-btn
                        v-bind="props"
                        variant="text"
                        class="text-none"
                    >
                        <v-avatar color="primary" size="32" class="mr-2">
                            <span class="text-white text-caption">
                                {{ userInitials }}
                            </span>
                        </v-avatar>
                        <span class="d-none d-sm-inline">{{ userName }}</span>
                        <v-icon end>mdi-chevron-down</v-icon>
                    </v-btn>
                </template>
                <v-list density="compact">
                    <v-list-item prepend-icon="mdi-account" title="Profile" />
                    <v-list-item prepend-icon="mdi-cog" title="Settings" :to="{ name: 'settings' }" />
                    <v-divider />
                    <v-list-item prepend-icon="mdi-logout" title="Logout" @click="handleLogout" />
                </v-list>
            </v-menu>
        </v-app-bar>

        <!-- Main Content -->
        <v-main :class="isDark ? 'bg-background' : 'bg-grey-lighten-4'">
            <v-container fluid class="pa-4 pa-md-6">
                <router-view />
            </v-container>
        </v-main>
    </v-layout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useDisplay, useTheme } from 'vuetify';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const theme = useTheme();
const { mobile } = useDisplay();

// Theme toggle - directly use Vuetify's useTheme
const isDark = computed(() => theme.global.current.value.dark);
const toggleTheme = () => {
    const newTheme = isDark.value ? 'light' : 'dark';
    theme.global.name.value = newTheme;
    localStorage.setItem('theme', newTheme);
};

const drawer = ref(true);
const rail = ref(false);

// Notifications
const notifications = ref([
    { icon: 'mdi-test-tube', title: 'New lab report ready for approval', time: '2 minutes ago' },
    { icon: 'mdi-calendar-check', title: 'Appointment confirmed for Patient #1234', time: '15 minutes ago' },
    { icon: 'mdi-cash-clock', title: 'Payment due reminder: Invoice #5678', time: '1 hour ago' },
]);

const removeNotification = (index) => {
    notifications.value.splice(index, 1);
};

const clearNotifications = () => {
    notifications.value = [];
};

// Menu Groups
const mainMenuItems = [
    { title: 'Dashboard', icon: 'mdi-view-dashboard', to: { name: 'dashboard' } },
];

const patientMenuItems = [
    { title: 'Patients', icon: 'mdi-account-group', to: { name: 'patients.index' } },
    { title: 'Patient Queue', icon: 'mdi-account-clock', to: { name: 'queue.index' } },
    { title: 'Vital Entry', icon: 'mdi-heart-pulse', to: { name: 'vitals.index' } },
    { title: 'Appointments', icon: 'mdi-calendar-clock', to: { name: 'appointments.index' } },
];

const doctorMenuItems = [
    { title: 'Doctors', icon: 'mdi-doctor', to: { name: 'doctors.index' } },
    { title: 'Prescriptions', icon: 'mdi-prescription', to: { name: 'prescriptions.index' } },
    { title: 'Rx Templates', icon: 'mdi-file-document-multiple', to: { name: 'prescription-templates.index' } },
    { title: 'Rx Favorites', icon: 'mdi-star', to: { name: 'medicine-favorites.index' } },
];

const labMenuItems = [
    { title: 'Test Categories', icon: 'mdi-folder-multiple', to: { name: 'test-categories.index' } },
    { title: 'Tests', icon: 'mdi-test-tube', to: { name: 'tests.index' } },
    { title: 'Lab Reports', icon: 'mdi-file-document', to: { name: 'lab-reports.index' } },
];

const billingMenuItems = [
    { title: 'Invoices', icon: 'mdi-receipt', to: { name: 'invoices.index' } },
    { title: 'Referrals', icon: 'mdi-account-multiple-plus', to: { name: 'billing.referrals' } },
    { title: 'Commissions', icon: 'mdi-cash-multiple', to: { name: 'billing.commissions' } },
    { title: 'Due Collection', icon: 'mdi-cash-clock', to: { name: 'billing.due-collection' } },
    { title: 'Transactions', icon: 'mdi-swap-horizontal', to: { name: 'billing.transactions' } },
];

const adminMenuItems = [
    { title: 'Reports', icon: 'mdi-file-chart', to: { name: 'reports.index' } },
    { title: 'Billing Reports', icon: 'mdi-chart-bar', to: { name: 'billing.reports' } },
    { title: 'Users', icon: 'mdi-account-cog', to: { name: 'users.index' } },
    { title: 'Settings', icon: 'mdi-cog', to: { name: 'settings' } },
];

const pageTitles = {
    dashboard: 'Dashboard',
    'patients.index': 'Patients',
    'patients.create': 'Add Patient',
    'patients.edit': 'Edit Patient',
    'patients.show': 'Patient Details',
    'patients.card': 'Patient Card',
    'patients.clinical-profile': 'Clinical Profile',
    'queue.index': 'Patient Queue',
    'queue.create': 'Add to Queue',
    'vitals.index': 'Vital Entry',
    'vitals.create': 'Record Vitals',
    'doctors.index': 'Doctors',
    'doctors.create': 'Add Doctor',
    'doctors.schedule': 'Doctor Schedule',
    'appointments.index': 'Appointments',
    'prescriptions.index': 'Prescriptions',
    'prescriptions.create': 'New Prescription',
    'prescriptions.show': 'Prescription Details',
    'prescription-templates.index': 'Prescription Templates',
    'medicine-favorites.index': 'Medicine Favorites',
    'tests.index': 'Tests',
    'lab-reports.index': 'Lab Reports',
    'invoices.index': 'Invoices',
    'billing.referrals': 'Referrals',
    'billing.commissions': 'Commissions',
    'billing.due-collection': 'Due Collection',
    'billing.transactions': 'Transactions',
    'billing.reports': 'Billing Reports',
    'reports.index': 'Financial Reports',
    'reports.financial': 'Financial Analytics',
    settings: 'Settings',
};

const pageTitle = computed(() => pageTitles[route.name] || 'DCMS');

const userName = computed(() => authStore.user?.name || 'Admin');
const userInitials = computed(() => {
    const name = authStore.user?.name || 'A';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
});

const handleLogout = async () => {
    await authStore.logout();
    router.push({ name: 'login' });
};
</script>
