<template>
    <div>
        <v-card :loading="loading">
            <v-card-title class="d-flex align-center justify-space-between">
                <div class="d-flex align-center">
                    <v-btn icon variant="text" :to="{ name: 'patients.index' }" class="mr-2">
                        <v-icon>mdi-arrow-left</v-icon>
                    </v-btn>
                    <v-icon class="mr-2">mdi-account</v-icon>
                    Patient Details
                </div>
                <div>
                    <v-btn
                        color="primary"
                        variant="outlined"
                        :to="{ name: 'patients.edit', params: { id: route.params.id } }"
                        class="mr-2"
                    >
                        <v-icon start>mdi-pencil</v-icon>
                        Edit
                    </v-btn>
                    <v-btn
                        color="info"
                        variant="outlined"
                        :to="{ name: 'patients.card', params: { id: route.params.id } }"
                        class="mr-2"
                    >
                        <v-icon start>mdi-card-account-details</v-icon>
                        Card
                    </v-btn>
                    <v-btn
                        color="purple"
                        variant="outlined"
                        :to="{ name: 'patients.clinical-profile', params: { id: route.params.id } }"
                        class="mr-2"
                    >
                        <v-icon start>mdi-clipboard-pulse</v-icon>
                        Clinical Profile
                    </v-btn>
                    <v-btn
                        color="warning"
                        variant="outlined"
                        :to="{ name: 'queue.create', query: { patient_id: patient.id } }"
                        class="mr-2"
                    >
                        <v-icon start>mdi-account-clock</v-icon>
                        Queue
                    </v-btn>
                    <v-btn color="success" @click="printPatient">
                        <v-icon start>mdi-printer</v-icon>
                        Print
                    </v-btn>
                </div>
            </v-card-title>

            <v-card-text>
                <v-row>
                    <!-- Patient Info Card -->
                    <v-col cols="12" md="4">
                        <v-card variant="outlined">
                            <v-card-text class="text-center">
                                <v-avatar color="primary" size="80" class="mb-3">
                                    <span class="text-h5 text-white">
                                        {{ getInitials(patient.name) }}
                                    </span>
                                </v-avatar>
                                <h2 class="text-h6 mb-1">{{ patient.name }}</h2>
                                <p class="text-body-2 text-grey mb-2">
                                    Patient ID: {{ patient.patient_id }}
                                </p>
                                <v-chip :color="patient.gender === 'Male' ? 'blue' : 'pink'" size="small" class="mr-1">
                                    {{ patient.gender }}
                                </v-chip>
                                <v-chip color="error" size="small" v-if="patient.blood_group">
                                    {{ patient.blood_group }}
                                </v-chip>
                            </v-card-text>
                        </v-card>
                    </v-col>

                    <!-- Details -->
                    <v-col cols="12" md="8">
                        <v-card variant="outlined" class="mb-4">
                            <v-card-title class="text-subtitle-1 bg-grey-lighten-4">
                                <v-icon class="mr-2" size="small">mdi-account</v-icon>
                                Personal Information
                            </v-card-title>
                            <v-card-text>
                                <v-row>
                                    <v-col cols="6" md="4">
                                        <p class="text-caption text-grey mb-1">Phone</p>
                                        <p class="font-weight-medium">{{ patient.phone || '-' }}</p>
                                    </v-col>
                                    <v-col cols="6" md="4">
                                        <p class="text-caption text-grey mb-1">Email</p>
                                        <p class="font-weight-medium">{{ patient.email || '-' }}</p>
                                    </v-col>
                                    <v-col cols="6" md="4">
                                        <p class="text-caption text-grey mb-1">Age</p>
                                        <p class="font-weight-medium">{{ patient.age }} years</p>
                                    </v-col>
                                    <v-col cols="6" md="4">
                                        <p class="text-caption text-grey mb-1">Date of Birth</p>
                                        <p class="font-weight-medium">{{ patient.date_of_birth || '-' }}</p>
                                    </v-col>
                                    <v-col cols="12" md="8">
                                        <p class="text-caption text-grey mb-1">Address</p>
                                        <p class="font-weight-medium">{{ patient.address || '-' }}</p>
                                    </v-col>
                                </v-row>
                            </v-card-text>
                        </v-card>

                        <v-card variant="outlined" class="mb-4">
                            <v-card-title class="text-subtitle-1 bg-grey-lighten-4">
                                <v-icon class="mr-2" size="small">mdi-phone-alert</v-icon>
                                Emergency Contact
                            </v-card-title>
                            <v-card-text>
                                <v-row>
                                    <v-col cols="6">
                                        <p class="text-caption text-grey mb-1">Name</p>
                                        <p class="font-weight-medium">{{ patient.emergency_contact_name || '-' }}</p>
                                    </v-col>
                                    <v-col cols="6">
                                        <p class="text-caption text-grey mb-1">Phone</p>
                                        <p class="font-weight-medium">{{ patient.emergency_contact_phone || '-' }}</p>
                                    </v-col>
                                </v-row>
                            </v-card-text>
                        </v-card>

                        <v-card variant="outlined">
                            <v-card-title class="text-subtitle-1 bg-grey-lighten-4">
                                <v-icon class="mr-2" size="small">mdi-medical-bag</v-icon>
                                Medical Information
                            </v-card-title>
                            <v-card-text>
                                <v-row>
                                    <v-col cols="12" md="6">
                                        <p class="text-caption text-grey mb-1">Known Allergies</p>
                                        <p class="font-weight-medium">{{ patient.allergies || 'None reported' }}</p>
                                    </v-col>
                                    <v-col cols="12" md="6">
                                        <p class="text-caption text-grey mb-1">Medical History</p>
                                        <p class="font-weight-medium">{{ patient.medical_history || 'None reported' }}</p>
                                    </v-col>
                                    <v-col cols="12">
                                        <p class="text-caption text-grey mb-1">Notes</p>
                                        <p class="font-weight-medium">{{ patient.notes || '-' }}</p>
                                    </v-col>
                                </v-row>
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>

                <!-- Quick Actions -->
                <v-row class="mt-4">
                    <v-col cols="12">
                        <h3 class="text-subtitle-1 font-weight-bold mb-3">Quick Actions</h3>
                        <v-row>
                            <v-col cols="6" md="3">
                                <v-card
                                    variant="tonal"
                                    color="primary"
                                    class="text-center pa-4"
                                    :to="{ name: 'appointments.create', query: { patient_id: patient.id } }"
                                    hover
                                >
                                    <v-icon size="32" class="mb-2">mdi-calendar-plus</v-icon>
                                    <p class="text-body-2 mb-0">New Appointment</p>
                                </v-card>
                            </v-col>
                            <v-col cols="6" md="3">
                                <v-card
                                    variant="tonal"
                                    color="success"
                                    class="text-center pa-4"
                                    :to="{ name: 'prescriptions.create', query: { patient_id: patient.id } }"
                                    hover
                                >
                                    <v-icon size="32" class="mb-2">mdi-prescription</v-icon>
                                    <p class="text-body-2 mb-0">New Prescription</p>
                                </v-card>
                            </v-col>
                            <v-col cols="6" md="3">
                                <v-card
                                    variant="tonal"
                                    color="warning"
                                    class="text-center pa-4"
                                    :to="{ name: 'invoices.create', query: { patient_id: patient.id } }"
                                    hover
                                >
                                    <v-icon size="32" class="mb-2">mdi-receipt-text-plus</v-icon>
                                    <p class="text-body-2 mb-0">New Invoice</p>
                                </v-card>
                            </v-col>
                            <v-col cols="6" md="3">
                                <v-card
                                    variant="tonal"
                                    color="info"
                                    class="text-center pa-4"
                                    :to="{ name: 'lab-reports.create', query: { patient_id: patient.id } }"
                                    hover
                                >
                                    <v-icon size="32" class="mb-2">mdi-file-plus</v-icon>
                                    <p class="text-body-2 mb-0">New Lab Report</p>
                                </v-card>
                            </v-col>
                        </v-row>
                    </v-col>
                </v-row>

                <!-- History Tabs -->
                <v-row class="mt-4">
                    <v-col cols="12">
                        <v-card variant="outlined">
                            <v-tabs v-model="activeTab" color="primary">
                                <v-tab value="appointments">Appointments</v-tab>
                                <v-tab value="prescriptions">Prescriptions</v-tab>
                                <v-tab value="lab-reports">Lab Reports</v-tab>
                                <v-tab value="invoices">Invoices</v-tab>
                                <v-tab value="vitals">Vitals</v-tab>
                            </v-tabs>

                            <v-card-text>
                                <v-tabs-window v-model="activeTab">
                                    <v-tabs-window-item value="appointments">
                                        <v-data-table
                                            v-if="emrData.appointments.length"
                                            :headers="appointmentHeaders"
                                            :items="emrData.appointments"
                                            :items-per-page="5"
                                            density="compact"
                                        >
                                            <template v-slot:item.date="{ item }">
                                                {{ formatDate(item.date) }}
                                            </template>
                                            <template v-slot:item.doctor="{ item }">
                                                {{ item.doctor?.name }}
                                            </template>
                                            <template v-slot:item.status="{ item }">
                                                <v-chip size="x-small" :color="getStatusColor(item.status)">
                                                    {{ item.status }}
                                                </v-chip>
                                            </template>
                                            <template v-slot:item.actions="{ item }">
                                                <v-btn
                                                    icon
                                                    variant="text"
                                                    size="x-small"
                                                    color="primary"
                                                    :to="{ name: 'appointments.show', params: { id: item.id } }"
                                                >
                                                    <v-icon size="small">mdi-eye</v-icon>
                                                </v-btn>
                                            </template>
                                        </v-data-table>
                                        <p v-else class="text-grey text-center py-4">No appointments found</p>
                                    </v-tabs-window-item>

                                    <v-tabs-window-item value="prescriptions">
                                        <v-data-table
                                            v-if="emrData.prescriptions.length"
                                            :headers="prescriptionHeaders"
                                            :items="emrData.prescriptions"
                                            :items-per-page="5"
                                            density="compact"
                                        >
                                            <template v-slot:item.date="{ item }">
                                                {{ formatDate(item.created_at) }}
                                            </template>
                                            <template v-slot:item.doctor="{ item }">
                                                {{ item.doctor?.name }}
                                            </template>
                                            <template v-slot:item.diagnosis="{ item }">
                                                {{ item.diagnosis || '-' }}
                                            </template>
                                            <template v-slot:item.actions="{ item }">
                                                <v-btn
                                                    icon
                                                    variant="text"
                                                    size="x-small"
                                                    color="primary"
                                                    :to="{ name: 'prescriptions.show', params: { id: item.id } }"
                                                >
                                                    <v-icon size="small">mdi-eye</v-icon>
                                                </v-btn>
                                                <v-btn
                                                    icon
                                                    variant="text"
                                                    size="x-small"
                                                    color="info"
                                                    @click="printPrescription(item.id)"
                                                >
                                                    <v-icon size="small">mdi-printer</v-icon>
                                                </v-btn>
                                            </template>
                                        </v-data-table>
                                        <p v-else class="text-grey text-center py-4">No prescriptions found</p>
                                    </v-tabs-window-item>

                                    <v-tabs-window-item value="lab-reports">
                                        <v-data-table
                                            v-if="emrData.labReports.length"
                                            :headers="labReportHeaders"
                                            :items="emrData.labReports"
                                            :items-per-page="5"
                                            density="compact"
                                        >
                                            <template v-slot:item.date="{ item }">
                                                {{ formatDate(item.report_date) }}
                                            </template>
                                            <template v-slot:item.test="{ item }">
                                                {{ item.test?.name }}
                                            </template>
                                            <template v-slot:item.status="{ item }">
                                                <v-chip size="x-small" :color="getLabStatusColor(item.status)">
                                                    {{ item.status }}
                                                </v-chip>
                                            </template>
                                            <template v-slot:item.actions="{ item }">
                                                <v-btn
                                                    icon
                                                    variant="text"
                                                    size="x-small"
                                                    color="primary"
                                                    :to="{ name: 'lab-reports.show', params: { id: item.id } }"
                                                >
                                                    <v-icon size="small">mdi-eye</v-icon>
                                                </v-btn>
                                            </template>
                                        </v-data-table>
                                        <p v-else class="text-grey text-center py-4">No lab reports found</p>
                                    </v-tabs-window-item>

                                    <v-tabs-window-item value="invoices">
                                        <v-data-table
                                            v-if="emrData.invoices.length"
                                            :headers="invoiceHeaders"
                                            :items="emrData.invoices"
                                            :items-per-page="5"
                                            density="compact"
                                        >
                                            <template v-slot:item.date="{ item }">
                                                {{ formatDate(item.invoice_date) }}
                                            </template>
                                            <template v-slot:item.invoice_number="{ item }">
                                                {{ item.invoice_number }}
                                            </template>
                                            <template v-slot:item.total="{ item }">
                                                {{ item.total_amount?.toLocaleString() }}
                                            </template>
                                            <template v-slot:item.paid="{ item }">
                                                {{ item.paid_amount?.toLocaleString() }}
                                            </template>
                                            <template v-slot:item.status="{ item }">
                                                <v-chip size="x-small" :color="getPaymentStatusColor(item.payment_status)">
                                                    {{ item.payment_status }}
                                                </v-chip>
                                            </template>
                                            <template v-slot:item.actions="{ item }">
                                                <v-btn
                                                    icon
                                                    variant="text"
                                                    size="x-small"
                                                    color="primary"
                                                    :to="{ name: 'invoices.show', params: { id: item.id } }"
                                                >
                                                    <v-icon size="small">mdi-eye</v-icon>
                                                </v-btn>
                                            </template>
                                        </v-data-table>
                                        <p v-else class="text-grey text-center py-4">No invoices found</p>
                                    </v-tabs-window-item>

                                    <v-tabs-window-item value="vitals">
                                        <v-data-table
                                            v-if="emrData.vitals.length"
                                            :headers="vitalHeaders"
                                            :items="emrData.vitals"
                                            :items-per-page="5"
                                            density="compact"
                                        >
                                            <template v-slot:item.date="{ item }">
                                                {{ formatDate(item.recorded_at) }}
                                            </template>
                                            <template v-slot:item.bp="{ item }">
                                                {{ item.blood_pressure_systolic }}/{{ item.blood_pressure_diastolic }}
                                            </template>
                                            <template v-slot:item.pulse="{ item }">
                                                {{ item.pulse_rate }} bpm
                                            </template>
                                            <template v-slot:item.temp="{ item }">
                                                {{ item.temperature }}°F
                                            </template>
                                            <template v-slot:item.bmi="{ item }">
                                                {{ item.bmi }}
                                            </template>
                                        </v-data-table>
                                        <p v-else class="text-grey text-center py-4">No vitals recorded</p>
                                    </v-tabs-window-item>
                                </v-tabs-window>
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>
            </v-card-text>
        </v-card>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const loading = ref(true);
const activeTab = ref('appointments');

const patient = reactive({
    id: '',
    patient_id: '',
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

const emrData = reactive({
    appointments: [],
    prescriptions: [],
    labReports: [],
    invoices: [],
    vitals: [],
});

// Table headers
const appointmentHeaders = [
    { title: 'Date', key: 'date' },
    { title: 'Doctor', key: 'doctor' },
    { title: 'Time', key: 'time_slot' },
    { title: 'Status', key: 'status' },
    { title: 'Actions', key: 'actions', sortable: false },
];

const prescriptionHeaders = [
    { title: 'Date', key: 'date' },
    { title: 'Doctor', key: 'doctor' },
    { title: 'Diagnosis', key: 'diagnosis' },
    { title: 'Actions', key: 'actions', sortable: false },
];

const labReportHeaders = [
    { title: 'Date', key: 'date' },
    { title: 'Test', key: 'test' },
    { title: 'Status', key: 'status' },
    { title: 'Actions', key: 'actions', sortable: false },
];

const invoiceHeaders = [
    { title: 'Date', key: 'date' },
    { title: 'Invoice #', key: 'invoice_number' },
    { title: 'Total', key: 'total' },
    { title: 'Paid', key: 'paid' },
    { title: 'Status', key: 'status' },
    { title: 'Actions', key: 'actions', sortable: false },
];

const vitalHeaders = [
    { title: 'Date', key: 'date' },
    { title: 'BP (mmHg)', key: 'bp' },
    { title: 'Pulse', key: 'pulse' },
    { title: 'Temp', key: 'temp' },
    { title: 'BMI', key: 'bmi' },
];

const getInitials = (name) => {
    return name?.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2) || 'PA';
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
};

const getStatusColor = (status) => {
    const colors = {
        Pending: 'warning',
        Confirmed: 'info',
        Completed: 'success',
        Cancelled: 'error',
    };
    return colors[status] || 'grey';
};

const getLabStatusColor = (status) => {
    const colors = {
        Pending: 'warning',
        'In Progress': 'info',
        Completed: 'success',
    };
    return colors[status] || 'grey';
};

const getPaymentStatusColor = (status) => {
    const colors = {
        Unpaid: 'error',
        Partial: 'warning',
        Paid: 'success',
    };
    return colors[status] || 'grey';
};

const fetchPatient = async () => {
    loading.value = true;
    try {
        const response = await axios.get(`/api/patients/${route.params.id}`);
        Object.assign(patient, response.data.data);
    } catch (error) {
        console.error('Failed to load patient:', error);
    }
    loading.value = false;
};

const fetchEmrData = async (type) => {
    if (!patient.id) return;

    const endpoints = {
        appointments: 'appointments',
        prescriptions: 'prescriptions',
        'lab-reports': 'lab-reports',
        invoices: 'invoices',
        vitals: 'vitals',
    };

    const dataKeys = {
        appointments: 'appointments',
        prescriptions: 'prescriptions',
        'lab-reports': 'labReports',
        invoices: 'invoices',
        vitals: 'vitals',
    };

    try {
        const response = await axios.get(`/api/patients/${patient.id}/emr/${endpoints[type]}`);
        emrData[dataKeys[type]] = response.data.data || [];
    } catch (error) {
        console.error(`Failed to fetch ${type}:`, error);
    }
};

const printPrescription = (id) => {
    window.open(`/prescriptions/${id}/print`, '_blank');
};

const printPatient = () => {
    window.print();
};

// Watch for tab changes and fetch data
watch(activeTab, (newTab) => {
    fetchEmrData(newTab);
});

// Initial fetch
watch(() => patient.id, (id) => {
    if (id) {
        fetchEmrData('appointments');
    }
});

onMounted(() => {
    fetchPatient();
});
</script>
