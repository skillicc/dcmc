import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const routes = [
    {
        path: '/login',
        name: 'login',
        component: () => import('@/pages/auth/Login.vue'),
        meta: { guest: true },
    },
    {
        path: '/',
        component: () => import('@/layouts/AdminLayout.vue'),
        meta: { requiresAuth: true },
        children: [
            {
                path: '',
                name: 'dashboard',
                component: () => import('@/pages/Dashboard.vue'),
            },
            // Patients
            {
                path: 'patients',
                name: 'patients.index',
                component: () => import('@/pages/patients/Index.vue'),
            },
            {
                path: 'patients/create',
                name: 'patients.create',
                component: () => import('@/pages/patients/Create.vue'),
            },
            {
                path: 'patients/:id',
                name: 'patients.show',
                component: () => import('@/pages/patients/Show.vue'),
            },
            {
                path: 'patients/:id/edit',
                name: 'patients.edit',
                component: () => import('@/pages/patients/Edit.vue'),
            },
            {
                path: 'patients/:id/card',
                name: 'patients.card',
                component: () => import('@/pages/patients/Card.vue'),
            },
            {
                path: 'patients/:id/clinical-profile',
                name: 'patients.clinical-profile',
                component: () => import('@/pages/patients/ClinicalProfile.vue'),
            },
            // Doctors
            {
                path: 'doctors',
                name: 'doctors.index',
                component: () => import('@/pages/doctors/Index.vue'),
            },
            {
                path: 'doctors/create',
                name: 'doctors.create',
                component: () => import('@/pages/doctors/Create.vue'),
            },
            {
                path: 'doctors/:id/edit',
                name: 'doctors.edit',
                component: () => import('@/pages/doctors/Edit.vue'),
            },
            {
                path: 'doctors/:id/schedule',
                name: 'doctors.schedule',
                component: () => import('@/pages/doctors/Schedule.vue'),
            },
            // Tests
            {
                path: 'tests',
                name: 'tests.index',
                component: () => import('@/pages/tests/Index.vue'),
            },
            {
                path: 'tests/create',
                name: 'tests.create',
                component: () => import('@/pages/tests/Create.vue'),
            },
            {
                path: 'tests/:id/edit',
                name: 'tests.edit',
                component: () => import('@/pages/tests/Edit.vue'),
            },
            // Test Categories
            {
                path: 'test-categories',
                name: 'test-categories.index',
                component: () => import('@/pages/test-categories/Index.vue'),
            },
            // Appointments
            {
                path: 'appointments',
                name: 'appointments.index',
                component: () => import('@/pages/appointments/Index.vue'),
            },
            {
                path: 'appointments/create',
                name: 'appointments.create',
                component: () => import('@/pages/appointments/Create.vue'),
            },
            // Prescriptions
            {
                path: 'prescriptions',
                name: 'prescriptions.index',
                component: () => import('@/pages/prescriptions/Index.vue'),
            },
            {
                path: 'prescriptions/create',
                name: 'prescriptions.create',
                component: () => import('@/pages/prescriptions/Create.vue'),
            },
            {
                path: 'prescriptions/:id',
                name: 'prescriptions.show',
                component: () => import('@/pages/prescriptions/Show.vue'),
            },
            // Lab Reports
            {
                path: 'lab-reports',
                name: 'lab-reports.index',
                component: () => import('@/pages/lab-reports/Index.vue'),
            },
            {
                path: 'lab-reports/create',
                name: 'lab-reports.create',
                component: () => import('@/pages/lab-reports/Create.vue'),
            },
            {
                path: 'lab-reports/:id',
                name: 'lab-reports.show',
                component: () => import('@/pages/lab-reports/Show.vue'),
            },
            // Billing
            {
                path: 'invoices',
                name: 'invoices.index',
                component: () => import('@/pages/invoices/Index.vue'),
            },
            {
                path: 'invoices/create',
                name: 'invoices.create',
                component: () => import('@/pages/invoices/Create.vue'),
            },
            {
                path: 'invoices/:id',
                name: 'invoices.show',
                component: () => import('@/pages/invoices/Show.vue'),
            },
            // Reports
            {
                path: 'reports',
                name: 'reports.index',
                component: () => import('@/pages/reports/Index.vue'),
            },
            {
                path: 'reports/financial',
                name: 'reports.financial',
                component: () => import('@/pages/reports/Financial.vue'),
            },
            // Settings
            {
                path: 'settings',
                name: 'settings',
                component: () => import('@/pages/settings/Index.vue'),
            },
            // Patient Queue
            {
                path: 'queue',
                name: 'queue.index',
                component: () => import('@/pages/queue/Index.vue'),
            },
            {
                path: 'queue/create',
                name: 'queue.create',
                component: () => import('@/pages/queue/Create.vue'),
            },
            // Vitals
            {
                path: 'vitals',
                name: 'vitals.index',
                component: () => import('@/pages/vitals/Index.vue'),
            },
            {
                path: 'vitals/create',
                name: 'vitals.create',
                component: () => import('@/pages/vitals/Create.vue'),
            },
            // Users
            {
                path: 'users',
                name: 'users.index',
                component: () => import('@/pages/users/Index.vue'),
            },
            {
                path: 'users/create',
                name: 'users.create',
                component: () => import('@/pages/users/Create.vue'),
            },
            {
                path: 'users/:id/edit',
                name: 'users.edit',
                component: () => import('@/pages/users/Edit.vue'),
            },
            // Prescription Templates
            {
                path: 'prescription-templates',
                name: 'prescription-templates.index',
                component: () => import('@/pages/prescription-templates/Index.vue'),
            },
            // Medicine Favorites
            {
                path: 'medicine-favorites',
                name: 'medicine-favorites.index',
                component: () => import('@/pages/medicine-favorites/Index.vue'),
            },
            // Billing Module
            {
                path: 'billing/referrals',
                name: 'billing.referrals',
                component: () => import('@/pages/billing/Referrals.vue'),
            },
            {
                path: 'billing/commissions',
                name: 'billing.commissions',
                component: () => import('@/pages/billing/Commissions.vue'),
            },
            {
                path: 'billing/due-collection',
                name: 'billing.due-collection',
                component: () => import('@/pages/billing/DueCollection.vue'),
            },
            {
                path: 'billing/transactions',
                name: 'billing.transactions',
                component: () => import('@/pages/billing/Transactions.vue'),
            },
            {
                path: 'billing/reports',
                name: 'billing.reports',
                component: () => import('@/pages/billing/Reports.vue'),
            },
        ],
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to, from, next) => {
    const authStore = useAuthStore();

    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        next({ name: 'login' });
    } else if (to.meta.guest && authStore.isAuthenticated) {
        next({ name: 'dashboard' });
    } else {
        next();
    }
});

export default router;
