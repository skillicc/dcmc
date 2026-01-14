<template>
    <div>
        <v-card :loading="loading">
            <v-card-title class="d-flex align-center">
                <v-btn icon variant="text" @click="$router.back()" class="mr-2">
                    <v-icon>mdi-arrow-left</v-icon>
                </v-btn>
                <v-icon class="mr-2">mdi-calendar-clock</v-icon>
                Doctor Schedule - {{ doctor.name }}
                <v-spacer />
                <v-btn color="primary" @click="saveSchedules" :loading="saving">
                    <v-icon start>mdi-content-save</v-icon>
                    Save Schedule
                </v-btn>
            </v-card-title>

            <v-card-text>
                <v-alert type="info" variant="tonal" class="mb-4">
                    Set the weekly schedule for this doctor. Enable/disable days and set working hours.
                </v-alert>

                <v-table>
                    <thead>
                        <tr>
                            <th width="120">Day</th>
                            <th width="100">Active</th>
                            <th width="150">Start Time</th>
                            <th width="150">End Time</th>
                            <th width="120">Max Patients</th>
                            <th>Slot Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="schedule in schedules" :key="schedule.day">
                            <td class="font-weight-medium">{{ schedule.day }}</td>
                            <td>
                                <v-switch
                                    v-model="schedule.is_active"
                                    color="primary"
                                    hide-details
                                    density="compact"
                                />
                            </td>
                            <td>
                                <v-text-field
                                    v-model="schedule.start_time"
                                    type="time"
                                    density="compact"
                                    hide-details
                                    variant="outlined"
                                    :disabled="!schedule.is_active"
                                />
                            </td>
                            <td>
                                <v-text-field
                                    v-model="schedule.end_time"
                                    type="time"
                                    density="compact"
                                    hide-details
                                    variant="outlined"
                                    :disabled="!schedule.is_active"
                                />
                            </td>
                            <td>
                                <v-text-field
                                    v-model.number="schedule.max_patients"
                                    type="number"
                                    density="compact"
                                    hide-details
                                    variant="outlined"
                                    :disabled="!schedule.is_active"
                                />
                            </td>
                            <td>
                                <v-select
                                    v-model="schedule.slot_duration"
                                    :items="slotDurations"
                                    density="compact"
                                    hide-details
                                    variant="outlined"
                                    :disabled="!schedule.is_active"
                                />
                            </td>
                        </tr>
                    </tbody>
                </v-table>
            </v-card-text>
        </v-card>

        <v-snackbar v-model="snackbar.show" :color="snackbar.color">
            {{ snackbar.message }}
        </v-snackbar>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const loading = ref(true);
const saving = ref(false);

const doctor = reactive({
    id: '',
    name: '',
});

const days = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

const slotDurations = [
    { title: '10 min', value: 10 },
    { title: '15 min', value: 15 },
    { title: '20 min', value: 20 },
    { title: '30 min', value: 30 },
];

const schedules = ref(days.map(day => ({
    day,
    is_active: false,
    start_time: '09:00',
    end_time: '17:00',
    max_patients: 20,
    slot_duration: 15,
})));

const snackbar = reactive({
    show: false,
    message: '',
    color: 'success',
});

const fetchDoctor = async () => {
    try {
        const response = await axios.get(`/api/doctors/${route.params.id}`);
        Object.assign(doctor, response.data.data);
    } catch (error) {
        console.error('Failed to load doctor:', error);
    }
};

const fetchSchedules = async () => {
    loading.value = true;
    try {
        const response = await axios.get(`/api/doctors/${route.params.id}/schedules`);
        const existingSchedules = response.data.data;

        schedules.value = days.map(day => {
            const existing = existingSchedules.find(s => s.day === day);
            if (existing) {
                return {
                    day,
                    is_active: existing.is_active,
                    start_time: existing.start_time?.substring(0, 5) || '09:00',
                    end_time: existing.end_time?.substring(0, 5) || '17:00',
                    max_patients: existing.max_patients,
                    slot_duration: existing.slot_duration,
                };
            }
            return {
                day,
                is_active: false,
                start_time: '09:00',
                end_time: '17:00',
                max_patients: 20,
                slot_duration: 15,
            };
        });
    } catch (error) {
        console.error('Failed to load schedules:', error);
    }
    loading.value = false;
};

const saveSchedules = async () => {
    saving.value = true;
    try {
        const activeSchedules = schedules.value.filter(s => s.is_active);
        await axios.post(`/api/doctors/${route.params.id}/schedules/bulk`, {
            schedules: activeSchedules,
        });
        snackbar.message = 'Schedule saved successfully';
        snackbar.color = 'success';
        snackbar.show = true;
    } catch (error) {
        snackbar.message = error.response?.data?.message || 'Failed to save schedule';
        snackbar.color = 'error';
        snackbar.show = true;
    }
    saving.value = false;
};

onMounted(() => {
    fetchDoctor();
    fetchSchedules();
});
</script>
