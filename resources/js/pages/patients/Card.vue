<template>
    <div>
        <v-card class="mb-4 d-print-none">
            <v-card-title class="d-flex align-center">
                <v-btn icon variant="text" @click="$router.back()" class="mr-2">
                    <v-icon>mdi-arrow-left</v-icon>
                </v-btn>
                Patient Registration Card
                <v-spacer />
                <v-btn color="primary" @click="printCard">
                    <v-icon start>mdi-printer</v-icon>
                    Print Card
                </v-btn>
            </v-card-title>
        </v-card>

        <!-- Printable Card -->
        <div ref="printArea" class="print-area">
            <v-card class="registration-card mx-auto" max-width="450" elevation="4">
                <v-card-text class="pa-0">
                    <!-- Header -->
                    <div class="card-header text-center py-3 bg-primary">
                        <h3 class="text-white text-h6 mb-1">{{ settings.clinic_name || 'DCMS Diagnostic Centre' }}</h3>
                        <p class="text-white text-caption mb-0">{{ settings.clinic_address || 'Dhaka, Bangladesh' }}</p>
                        <p class="text-white text-caption mb-0">{{ settings.clinic_phone }}</p>
                    </div>

                    <!-- Patient Info -->
                    <div class="pa-4">
                        <div class="d-flex align-start">
                            <v-avatar color="primary" size="70" class="mr-4">
                                <span class="text-h5 text-white">{{ getInitials(patient.name) }}</span>
                            </v-avatar>
                            <div class="flex-grow-1">
                                <h4 class="text-h6 mb-1">{{ patient.name }}</h4>
                                <v-chip color="primary" size="small" class="mb-2">
                                    {{ patient.patient_id }}
                                </v-chip>
                                <div class="text-body-2">
                                    <div class="d-flex mb-1">
                                        <span class="text-grey mr-2" style="width: 60px;">Gender:</span>
                                        <strong>{{ patient.gender }}</strong>
                                    </div>
                                    <div class="d-flex mb-1">
                                        <span class="text-grey mr-2" style="width: 60px;">Age:</span>
                                        <strong>{{ patient.age }} years</strong>
                                    </div>
                                    <div class="d-flex mb-1" v-if="patient.blood_group">
                                        <span class="text-grey mr-2" style="width: 60px;">Blood:</span>
                                        <v-chip color="error" size="x-small">{{ patient.blood_group }}</v-chip>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <v-divider class="my-3" />

                        <div class="text-body-2">
                            <div class="d-flex mb-1">
                                <v-icon size="small" class="mr-2">mdi-phone</v-icon>
                                <span>{{ patient.phone }}</span>
                            </div>
                            <div class="d-flex mb-1" v-if="patient.email">
                                <v-icon size="small" class="mr-2">mdi-email</v-icon>
                                <span>{{ patient.email }}</span>
                            </div>
                            <div class="d-flex" v-if="patient.address">
                                <v-icon size="small" class="mr-2">mdi-map-marker</v-icon>
                                <span>{{ patient.address }}</span>
                            </div>
                        </div>

                        <v-divider class="my-3" />

                        <!-- Emergency Contact -->
                        <div class="text-body-2" v-if="patient.emergency_contact_name">
                            <p class="text-caption text-grey mb-1">Emergency Contact</p>
                            <div class="d-flex">
                                <v-icon size="small" class="mr-2">mdi-phone-alert</v-icon>
                                <span>{{ patient.emergency_contact_name }} - {{ patient.emergency_contact_phone }}</span>
                            </div>
                        </div>

                        <!-- Registration Date -->
                        <div class="text-center mt-3">
                            <p class="text-caption text-grey mb-0">Registered on: {{ formatDate(patient.created_at) }}</p>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="card-footer text-center py-2 bg-grey-lighten-3">
                        <p class="text-caption mb-0">Please bring this card on every visit</p>
                    </div>
                </v-card-text>
            </v-card>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const printArea = ref(null);

const patient = reactive({
    id: '',
    patient_id: '',
    name: '',
    phone: '',
    email: '',
    age: '',
    gender: '',
    blood_group: '',
    address: '',
    emergency_contact_name: '',
    emergency_contact_phone: '',
    created_at: '',
});

const settings = reactive({
    clinic_name: '',
    clinic_address: '',
    clinic_phone: '',
});

const getInitials = (name) => {
    return name?.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2) || 'PA';
};

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};

const fetchPatient = async () => {
    try {
        const response = await axios.get(`/api/patients/${route.params.id}`);
        Object.assign(patient, response.data.data);
    } catch (error) {
        console.error('Failed to load patient:', error);
    }
};

const fetchSettings = async () => {
    try {
        const response = await axios.get('/api/settings');
        Object.assign(settings, response.data);
    } catch (error) {
        console.error('Failed to load settings:', error);
    }
};

const printCard = () => {
    window.print();
};

onMounted(() => {
    fetchPatient();
    fetchSettings();
});
</script>

<style scoped>
.registration-card {
    border: 2px solid #00897B;
    border-radius: 12px;
    overflow: hidden;
}

.card-header {
    background: linear-gradient(135deg, #00897B 0%, #00695C 100%);
}

@media print {
    .d-print-none {
        display: none !important;
    }

    .print-area {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
    }

    .registration-card {
        box-shadow: none !important;
        margin: 20px auto !important;
    }
}
</style>
