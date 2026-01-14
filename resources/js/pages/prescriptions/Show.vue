<template>
    <div>
        <v-card :loading="loading">
            <v-card-title class="d-flex align-center justify-space-between">
                <div class="d-flex align-center">
                    <v-btn icon variant="text" :to="{ name: 'prescriptions.index' }" class="mr-2"><v-icon>mdi-arrow-left</v-icon></v-btn>
                    <v-icon class="mr-2">mdi-prescription</v-icon>
                    Prescription Details
                </div>
                <v-btn color="success" @click="printPrescription">
                    <v-icon start>mdi-printer</v-icon>Print
                </v-btn>
            </v-card-title>

            <v-card-text>
                <!-- Header Info -->
                <v-row>
                    <v-col cols="12" md="6">
                        <v-card variant="outlined">
                            <v-card-title class="text-subtitle-1 bg-grey-lighten-4">Patient Information</v-card-title>
                            <v-card-text>
                                <p><strong>Name:</strong> {{ prescription.patient?.name }}</p>
                                <p><strong>Age:</strong> {{ prescription.patient?.age }} | <strong>Gender:</strong> {{ prescription.patient?.gender }}</p>
                                <p><strong>Phone:</strong> {{ prescription.patient?.phone }}</p>
                            </v-card-text>
                        </v-card>
                    </v-col>
                    <v-col cols="12" md="6">
                        <v-card variant="outlined">
                            <v-card-title class="text-subtitle-1 bg-grey-lighten-4">Prescription Info</v-card-title>
                            <v-card-text>
                                <p><strong>Prescription ID:</strong> {{ prescription.prescription_id }}</p>
                                <p><strong>Date:</strong> {{ prescription.date }}</p>
                                <p><strong>Doctor:</strong> {{ prescription.doctor?.name }}</p>
                            </v-card-text>
                        </v-card>
                    </v-col>
                </v-row>

                <!-- Vitals -->
                <v-card variant="outlined" class="mt-4" v-if="hasVitals">
                    <v-card-title class="text-subtitle-1 bg-grey-lighten-4">
                        <v-icon class="mr-2" size="small">mdi-heart-pulse</v-icon>Vitals
                    </v-card-title>
                    <v-card-text>
                        <v-row>
                            <v-col cols="4" md="2" v-if="prescription.vitals_bp"><strong>BP:</strong> {{ prescription.vitals_bp }}</v-col>
                            <v-col cols="4" md="2" v-if="prescription.vitals_pulse"><strong>Pulse:</strong> {{ prescription.vitals_pulse }}</v-col>
                            <v-col cols="4" md="2" v-if="prescription.vitals_temp"><strong>Temp:</strong> {{ prescription.vitals_temp }}</v-col>
                            <v-col cols="4" md="2" v-if="prescription.vitals_weight"><strong>Weight:</strong> {{ prescription.vitals_weight }} kg</v-col>
                            <v-col cols="4" md="2" v-if="prescription.vitals_height"><strong>Height:</strong> {{ prescription.vitals_height }} cm</v-col>
                            <v-col cols="4" md="2" v-if="prescription.vitals_spo2"><strong>SpO2:</strong> {{ prescription.vitals_spo2 }}</v-col>
                        </v-row>
                    </v-card-text>
                </v-card>

                <!-- Chief Complaints & Diagnosis -->
                <v-row class="mt-4">
                    <v-col cols="12" md="6" v-if="prescription.chief_complaints">
                        <v-card variant="outlined">
                            <v-card-title class="text-subtitle-1 bg-grey-lighten-4">Chief Complaints</v-card-title>
                            <v-card-text>{{ prescription.chief_complaints }}</v-card-text>
                        </v-card>
                    </v-col>
                    <v-col cols="12" md="6" v-if="prescription.diagnosis">
                        <v-card variant="outlined">
                            <v-card-title class="text-subtitle-1 bg-grey-lighten-4">Diagnosis</v-card-title>
                            <v-card-text>{{ prescription.diagnosis }}</v-card-text>
                        </v-card>
                    </v-col>
                </v-row>

                <!-- Medicines -->
                <v-card variant="outlined" class="mt-4" v-if="prescription.medicines?.length">
                    <v-card-title class="text-subtitle-1 bg-grey-lighten-4">
                        <v-icon class="mr-2" size="small">mdi-pill</v-icon>Prescribed Medicines
                    </v-card-title>
                    <v-card-text class="pa-0">
                        <v-table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Medicine</th>
                                    <th>Dosage</th>
                                    <th>Frequency</th>
                                    <th>Duration</th>
                                    <th>Instructions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(med, index) in prescription.medicines" :key="index">
                                    <td>{{ index + 1 }}</td>
                                    <td class="font-weight-medium">{{ med.name }}</td>
                                    <td>{{ med.dosage }}</td>
                                    <td>{{ med.frequency }}</td>
                                    <td>{{ med.duration }}</td>
                                    <td>{{ med.instructions }}</td>
                                </tr>
                            </tbody>
                        </v-table>
                    </v-card-text>
                </v-card>

                <!-- Tests Advised -->
                <v-card variant="outlined" class="mt-4" v-if="prescription.tests_advised?.length">
                    <v-card-title class="text-subtitle-1 bg-grey-lighten-4">
                        <v-icon class="mr-2" size="small">mdi-test-tube</v-icon>Tests Advised
                    </v-card-title>
                    <v-card-text>
                        <v-chip v-for="test in prescription.tests_advised" :key="test.id" class="mr-2 mb-2" color="primary" variant="outlined">
                            {{ test.test_name }}
                        </v-chip>
                    </v-card-text>
                </v-card>

                <!-- Advice -->
                <v-card variant="outlined" class="mt-4" v-if="prescription.advice">
                    <v-card-title class="text-subtitle-1 bg-grey-lighten-4">Advice</v-card-title>
                    <v-card-text>{{ prescription.advice }}</v-card-text>
                </v-card>

                <!-- Follow Up -->
                <v-alert type="info" variant="tonal" class="mt-4" v-if="prescription.follow_up_date">
                    <strong>Follow Up:</strong> {{ prescription.follow_up_date }}
                </v-alert>
            </v-card-text>
        </v-card>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const loading = ref(true);

const prescription = reactive({
    id: '', prescription_id: '', patient: {}, doctor: {}, date: '',
    vitals_bp: '', vitals_pulse: '', vitals_temp: '', vitals_weight: '', vitals_height: '', vitals_spo2: '',
    chief_complaints: '', diagnosis: '',
    medicines: [], tests_advised: [], advice: '', follow_up_date: '',
});

const hasVitals = computed(() =>
    prescription.vitals_bp || prescription.vitals_pulse || prescription.vitals_temp ||
    prescription.vitals_weight || prescription.vitals_height || prescription.vitals_spo2
);

const fetchPrescription = async () => {
    loading.value = true;
    try {
        const response = await axios.get(`/api/prescriptions/${route.params.id}`);
        Object.assign(prescription, response.data.data);
    } catch (error) { console.error('Failed to load prescription:', error); }
    loading.value = false;
};

const printPrescription = () => {
    window.open(`/prescriptions/${prescription.id}/print`, '_blank');
};

onMounted(() => fetchPrescription());
</script>
