<template>
    <div>
        <!-- Top Stats Cards -->
        <v-row>
            <v-col cols="12" sm="6" lg="3" v-for="stat in stats" :key="stat.title">
                <v-card>
                    <v-card-text class="d-flex align-center">
                        <v-avatar :color="stat.color" size="56" class="mr-4">
                            <v-icon color="white" size="28">{{ stat.icon }}</v-icon>
                        </v-avatar>
                        <div>
                            <p class="text-body-2 text-grey mb-1">{{ stat.title }}</p>
                            <p class="text-h5 font-weight-bold mb-0">{{ stat.value }}</p>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <!-- Daily Earnings & Analytics Row -->
        <v-row class="mt-2">
            <!-- Daily Total Earnings -->
            <v-col cols="12" lg="4">
                <v-card height="100%">
                    <v-card-title class="d-flex align-center justify-space-between">
                        <div>
                            <v-icon class="mr-2" color="success">mdi-cash-multiple</v-icon>
                            Daily Earnings
                        </div>
                        <v-chip color="success" size="small">Today</v-chip>
                    </v-card-title>
                    <v-card-text>
                        <div class="text-center mb-4">
                            <p class="text-h3 font-weight-bold text-success mb-1">{{ formatNumber(dailyEarnings.today?.total_collected || 0) }}</p>
                            <p class="text-caption text-grey">Total Collection Today</p>
                        </div>

                        <v-divider class="mb-3" />

                        <!-- Payment Method Breakdown -->
                        <div class="text-subtitle-2 mb-2">Payment Methods</div>
                        <div v-for="method in dailyEarnings.today?.by_method || []" :key="method.payment_method" class="d-flex justify-space-between align-center mb-2">
                            <div class="d-flex align-center">
                                <v-icon size="small" :color="getMethodColor(method.payment_method)" class="mr-2">{{ getMethodIcon(method.payment_method) }}</v-icon>
                                <span class="text-body-2">{{ method.payment_method }}</span>
                            </div>
                            <div class="text-end">
                                <span class="font-weight-bold">{{ formatNumber(method.total) }}</span>
                                <span class="text-caption text-grey ml-1">({{ method.count }})</span>
                            </div>
                        </div>

                        <v-divider class="my-3" />

                        <!-- Invoice Summary -->
                        <v-row dense>
                            <v-col cols="6">
                                <div class="text-center pa-2 bg-blue-lighten-5 rounded">
                                    <div class="text-h6 text-primary">{{ dailyEarnings.today?.invoices?.total_invoices || 0 }}</div>
                                    <div class="text-caption">Invoices</div>
                                </div>
                            </v-col>
                            <v-col cols="6">
                                <div class="text-center pa-2 bg-orange-lighten-5 rounded">
                                    <div class="text-h6 text-warning">{{ formatNumber(dailyEarnings.today?.invoices?.total_due || 0) }}</div>
                                    <div class="text-caption">Due</div>
                                </div>
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>
            </v-col>

            <!-- Weekly Collection Chart -->
            <v-col cols="12" lg="5">
                <v-card height="100%">
                    <v-card-title class="d-flex align-center">
                        <v-icon class="mr-2" color="primary">mdi-chart-line</v-icon>
                        Weekly Collection Trend
                    </v-card-title>
                    <v-card-text>
                        <div class="chart-container">
                            <div class="d-flex align-end justify-space-around" style="height: 180px;">
                                <div v-for="day in weeklyData" :key="day.date" class="text-center" style="flex: 1; max-width: 60px;">
                                    <div
                                        class="bg-primary rounded-t mx-auto transition-all"
                                        :style="{ height: getBarHeight(day.total) + 'px', width: '35px' }"
                                    ></div>
                                    <div class="text-caption mt-1 font-weight-medium">{{ formatWeekDay(day.date) }}</div>
                                    <div class="text-caption text-grey">{{ formatShort(day.total) }}</div>
                                </div>
                            </div>
                        </div>
                        <v-divider class="my-3" />
                        <div class="d-flex justify-space-between">
                            <div class="text-center">
                                <div class="text-h6 text-primary">{{ formatNumber(dailyEarnings.week?.total || 0) }}</div>
                                <div class="text-caption">This Week</div>
                            </div>
                            <div class="text-center">
                                <div class="text-h6 text-success">{{ formatNumber(dailyEarnings.month_total || 0) }}</div>
                                <div class="text-caption">This Month</div>
                            </div>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>

            <!-- Today's Summary -->
            <v-col cols="12" lg="3">
                <v-card height="100%">
                    <v-card-title class="d-flex align-center">
                        <v-icon class="mr-2" color="info">mdi-calendar-today</v-icon>
                        Today's Summary
                    </v-card-title>
                    <v-card-text class="pa-2">
                        <v-list density="compact">
                            <v-list-item v-for="item in todaySummary" :key="item.label" class="px-2">
                                <template v-slot:prepend>
                                    <v-avatar :color="item.color" size="32" class="mr-2">
                                        <v-icon color="white" size="18">{{ item.icon }}</v-icon>
                                    </v-avatar>
                                </template>
                                <v-list-item-title class="text-body-2">{{ item.label }}</v-list-item-title>
                                <template v-slot:append>
                                    <span class="text-h6 font-weight-bold">{{ item.value }}</span>
                                </template>
                            </v-list-item>
                        </v-list>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <!-- Patient Statistics & Quick Actions -->
        <v-row class="mt-2">
            <!-- Patient Count Widget -->
            <v-col cols="12" lg="3">
                <v-card>
                    <v-card-title class="d-flex align-center">
                        <v-icon class="mr-2" color="primary">mdi-account-group</v-icon>
                        Patient Statistics
                    </v-card-title>
                    <v-card-text>
                        <div class="text-center mb-3">
                            <v-progress-circular
                                :model-value="patientStats.todayPercentage"
                                :size="120"
                                :width="12"
                                color="primary"
                            >
                                <div>
                                    <div class="text-h4 font-weight-bold">{{ patientStats.today }}</div>
                                    <div class="text-caption">Today</div>
                                </div>
                            </v-progress-circular>
                        </div>
                        <v-row dense class="text-center">
                            <v-col cols="6">
                                <div class="pa-2 bg-green-lighten-5 rounded">
                                    <div class="text-subtitle-1 font-weight-bold text-success">{{ patientStats.week }}</div>
                                    <div class="text-caption">This Week</div>
                                </div>
                            </v-col>
                            <v-col cols="6">
                                <div class="pa-2 bg-blue-lighten-5 rounded">
                                    <div class="text-subtitle-1 font-weight-bold text-primary">{{ patientStats.month }}</div>
                                    <div class="text-caption">This Month</div>
                                </div>
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>
            </v-col>

            <!-- Quick Actions -->
            <v-col cols="12" lg="6">
                <v-card height="100%">
                    <v-card-title class="d-flex align-center">
                        <v-icon class="mr-2">mdi-lightning-bolt</v-icon>
                        Quick Actions
                    </v-card-title>
                    <v-card-text>
                        <v-row>
                            <v-col cols="6" md="3" v-for="action in quickActions" :key="action.title">
                                <v-card
                                    :color="action.color"
                                    variant="tonal"
                                    class="text-center pa-4 cursor-pointer"
                                    :to="action.to"
                                    hover
                                >
                                    <v-icon size="32" class="mb-2">{{ action.icon }}</v-icon>
                                    <p class="text-body-2 mb-0">{{ action.title }}</p>
                                </v-card>
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>
            </v-col>

            <!-- Hourly Activity -->
            <v-col cols="12" lg="3">
                <v-card height="100%">
                    <v-card-title class="d-flex align-center">
                        <v-icon class="mr-2" color="warning">mdi-clock-outline</v-icon>
                        Hourly Activity
                    </v-card-title>
                    <v-card-text>
                        <div class="d-flex align-end justify-space-around" style="height: 100px;">
                            <div v-for="h in hourlyActivity" :key="h.hour" class="text-center" style="flex: 1;">
                                <div
                                    class="bg-warning rounded-t mx-auto"
                                    :style="{ height: getHourlyHeight(h.total) + 'px', width: '12px' }"
                                ></div>
                                <div class="text-caption" style="font-size: 9px;">{{ h.hour }}</div>
                            </div>
                        </div>
                        <div class="text-center mt-2">
                            <span class="text-caption text-grey">Collection by hour today</span>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <!-- Recent Activities -->
        <v-row class="mt-2">
            <v-col cols="12" lg="6">
                <v-card>
                    <v-card-title class="d-flex align-center justify-space-between">
                        <div>
                            <v-icon class="mr-2">mdi-account-group</v-icon>
                            Recent Patients
                        </div>
                        <v-btn variant="text" size="small" :to="{ name: 'patients.index' }">
                            View All
                        </v-btn>
                    </v-card-title>
                    <v-card-text class="pa-0">
                        <v-table density="compact">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Phone</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="patient in recentPatients" :key="patient.id">
                                    <td>{{ patient.name }}</td>
                                    <td>{{ patient.phone }}</td>
                                    <td>{{ patient.date }}</td>
                                </tr>
                                <tr v-if="recentPatients.length === 0">
                                    <td colspan="3" class="text-center text-grey">No recent patients</td>
                                </tr>
                            </tbody>
                        </v-table>
                    </v-card-text>
                </v-card>
            </v-col>

            <v-col cols="12" lg="6">
                <v-card>
                    <v-card-title class="d-flex align-center justify-space-between">
                        <div>
                            <v-icon class="mr-2">mdi-calendar-clock</v-icon>
                            Today's Appointments
                        </div>
                        <v-btn variant="text" size="small" :to="{ name: 'appointments.index' }">
                            View All
                        </v-btn>
                    </v-card-title>
                    <v-card-text class="pa-0">
                        <v-table density="compact">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Doctor</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="appt in todayAppointments" :key="appt.id">
                                    <td>{{ appt.patient }}</td>
                                    <td>{{ appt.doctor }}</td>
                                    <td>{{ appt.time }}</td>
                                    <td>
                                        <v-chip :color="appt.statusColor" size="x-small">
                                            {{ appt.status }}
                                        </v-chip>
                                    </td>
                                </tr>
                                <tr v-if="todayAppointments.length === 0">
                                    <td colspan="4" class="text-center text-grey">No appointments today</td>
                                </tr>
                            </tbody>
                        </v-table>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <!-- Pending Lab Reports & Balance Summary -->
        <v-row class="mt-2">
            <v-col cols="12" lg="8">
                <v-card>
                    <v-card-title class="d-flex align-center justify-space-between">
                        <div>
                            <v-icon class="mr-2">mdi-file-clock</v-icon>
                            Pending Lab Reports
                        </div>
                        <v-btn variant="text" size="small" :to="{ name: 'lab-reports.index' }">
                            View All
                        </v-btn>
                    </v-card-title>
                    <v-card-text class="pa-0">
                        <v-table density="compact">
                            <thead>
                                <tr>
                                    <th>Report ID</th>
                                    <th>Patient</th>
                                    <th>Test</th>
                                    <th>Sample Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="report in pendingReports" :key="report.id">
                                    <td>{{ report.id }}</td>
                                    <td>{{ report.patient }}</td>
                                    <td>{{ report.test }}</td>
                                    <td>{{ report.date }}</td>
                                    <td>
                                        <v-chip :color="report.statusColor" size="x-small">
                                            {{ report.status }}
                                        </v-chip>
                                    </td>
                                    <td>
                                        <v-btn
                                            variant="text"
                                            color="primary"
                                            size="small"
                                            :to="{ name: 'lab-reports.show', params: { id: report.id } }"
                                        >
                                            Enter Result
                                        </v-btn>
                                    </td>
                                </tr>
                                <tr v-if="pendingReports.length === 0">
                                    <td colspan="6" class="text-center text-grey">No pending reports</td>
                                </tr>
                            </tbody>
                        </v-table>
                    </v-card-text>
                </v-card>
            </v-col>

            <!-- Balance Summary -->
            <v-col cols="12" lg="4">
                <v-card height="100%">
                    <v-card-title class="d-flex align-center">
                        <v-icon class="mr-2" color="error">mdi-wallet</v-icon>
                        Balance Summary
                    </v-card-title>
                    <v-card-text>
                        <div class="mb-3">
                            <div class="d-flex justify-space-between mb-1">
                                <span class="text-body-2">Total Billed</span>
                                <span class="font-weight-bold">{{ formatNumber(dailyEarnings.today?.invoices?.total_billed || 0) }}</span>
                            </div>
                            <v-progress-linear color="primary" :model-value="100" height="6" rounded></v-progress-linear>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-space-between mb-1">
                                <span class="text-body-2">Collected</span>
                                <span class="font-weight-bold text-success">{{ formatNumber(dailyEarnings.today?.invoices?.total_collected || 0) }}</span>
                            </div>
                            <v-progress-linear color="success" :model-value="collectionPercentage" height="6" rounded></v-progress-linear>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-space-between mb-1">
                                <span class="text-body-2">Due Amount</span>
                                <span class="font-weight-bold text-error">{{ formatNumber(dailyEarnings.today?.invoices?.total_due || 0) }}</span>
                            </div>
                            <v-progress-linear color="error" :model-value="duePercentage" height="6" rounded></v-progress-linear>
                        </div>
                        <div>
                            <div class="d-flex justify-space-between mb-1">
                                <span class="text-body-2">Discount Given</span>
                                <span class="font-weight-bold text-warning">{{ formatNumber(dailyEarnings.today?.invoices?.total_discount || 0) }}</span>
                            </div>
                            <v-progress-linear color="warning" :model-value="discountPercentage" height="6" rounded></v-progress-linear>
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const loading = ref(false);

const stats = ref([
    { title: 'Total Patients', value: '0', icon: 'mdi-account-group', color: 'primary' },
    { title: "Today's Appointments", value: '0', icon: 'mdi-calendar-check', color: 'success' },
    { title: 'Pending Reports', value: '0', icon: 'mdi-file-clock', color: 'warning' },
    { title: "Today's Revenue", value: '0', icon: 'mdi-currency-bdt', color: 'info' },
]);

const quickActions = [
    { title: 'New Patient', icon: 'mdi-account-plus', color: 'primary', to: { name: 'patients.create' } },
    { title: 'New Appointment', icon: 'mdi-calendar-plus', color: 'success', to: { name: 'appointments.create' } },
    { title: 'New Invoice', icon: 'mdi-receipt-text-plus', color: 'warning', to: { name: 'invoices.create' } },
    { title: 'New Lab Report', icon: 'mdi-file-plus', color: 'info', to: { name: 'lab-reports.create' } },
    { title: 'Queue', icon: 'mdi-account-clock', color: 'secondary', to: { name: 'queue.index' } },
    { title: 'Prescription', icon: 'mdi-prescription', color: 'error', to: { name: 'prescriptions.create' } },
    { title: 'Reports', icon: 'mdi-chart-bar', color: 'teal', to: { name: 'reports.index' } },
    { title: 'Due Collection', icon: 'mdi-cash-clock', color: 'purple', to: { name: 'billing.due-collection' } },
];

const todaySummary = ref([
    { label: 'New Patients', value: '0', icon: 'mdi-account-plus', color: 'primary' },
    { label: 'Completed Appts', value: '0', icon: 'mdi-check-circle', color: 'success' },
    { label: 'Pending Appts', value: '0', icon: 'mdi-clock', color: 'warning' },
    { label: 'Lab Reports Done', value: '0', icon: 'mdi-file-check', color: 'info' },
    { label: 'Invoices', value: '0', icon: 'mdi-receipt', color: 'secondary' },
]);

const recentPatients = ref([]);
const todayAppointments = ref([]);
const pendingReports = ref([]);
const dailyEarnings = ref({});
const patientStats = ref({ today: 0, week: 0, month: 0, todayPercentage: 0 });

const weeklyData = computed(() => {
    const data = dailyEarnings.value.week?.daily || [];
    // Fill in missing days with 0
    const days = [];
    for (let i = 6; i >= 0; i--) {
        const date = new Date();
        date.setDate(date.getDate() - i);
        const dateStr = date.toISOString().substr(0, 10);
        const found = data.find(d => d.date === dateStr);
        days.push({
            date: dateStr,
            total: found ? parseFloat(found.total) : 0
        });
    }
    return days;
});

const hourlyActivity = computed(() => {
    const hours = [];
    const data = dailyEarnings.value.hourly || [];
    for (let h = 8; h <= 20; h++) {
        const found = data.find(d => parseInt(d.hour) === h);
        hours.push({
            hour: h > 12 ? (h - 12) + 'pm' : h + 'am',
            total: found ? parseFloat(found.total) : 0
        });
    }
    return hours;
});

const collectionPercentage = computed(() => {
    const billed = dailyEarnings.value.today?.invoices?.total_billed || 0;
    const collected = dailyEarnings.value.today?.invoices?.total_collected || 0;
    return billed > 0 ? (collected / billed * 100) : 0;
});

const duePercentage = computed(() => {
    const billed = dailyEarnings.value.today?.invoices?.total_billed || 0;
    const due = dailyEarnings.value.today?.invoices?.total_due || 0;
    return billed > 0 ? (due / billed * 100) : 0;
});

const discountPercentage = computed(() => {
    const billed = dailyEarnings.value.today?.invoices?.total_billed || 0;
    const discount = dailyEarnings.value.today?.invoices?.total_discount || 0;
    return billed > 0 ? Math.min((discount / billed * 100), 100) : 0;
});

const formatNumber = (num) => Number(num || 0).toLocaleString('en-BD');

const formatShort = (num) => {
    if (num >= 1000) return (num / 1000).toFixed(1) + 'k';
    return num.toString();
};

const formatWeekDay = (dateStr) => {
    const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    return days[new Date(dateStr).getDay()];
};

const getMethodColor = (method) => ({
    'Cash': 'success',
    'Card': 'primary',
    'Mobile Banking': 'info',
    'Bank Transfer': 'warning',
    'Cheque': 'secondary',
}[method] || 'grey');

const getMethodIcon = (method) => ({
    'Cash': 'mdi-cash',
    'Card': 'mdi-credit-card',
    'Mobile Banking': 'mdi-cellphone',
    'Bank Transfer': 'mdi-bank',
    'Cheque': 'mdi-checkbook',
}[method] || 'mdi-cash');

const getBarHeight = (value) => {
    const maxValue = Math.max(...weeklyData.value.map(d => d.total));
    if (maxValue === 0) return 10;
    return Math.max(10, (value / maxValue) * 150);
};

const getHourlyHeight = (value) => {
    const maxValue = Math.max(...hourlyActivity.value.map(h => h.total));
    if (maxValue === 0) return 5;
    return Math.max(5, (value / maxValue) * 80);
};

const fetchDashboardData = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/dashboard');
        if (response.data) {
            stats.value = response.data.stats || stats.value;
            todaySummary.value = response.data.todaySummary || todaySummary.value;
            recentPatients.value = response.data.recentPatients || [];
            todayAppointments.value = response.data.todayAppointments || [];
            pendingReports.value = response.data.pendingReports || [];
            dailyEarnings.value = response.data.dailyEarnings || {};

            // Patient stats from API
            if (response.data.patientStats) {
                patientStats.value = response.data.patientStats;
            }
        }
    } catch (error) {
        console.error('Failed to fetch dashboard data:', error);
    }
    loading.value = false;
};

onMounted(() => {
    fetchDashboardData();
});
</script>

<style scoped>
.cursor-pointer {
    cursor: pointer;
}
.transition-all {
    transition: all 0.3s ease;
}
.chart-container {
    padding: 10px 0;
}
</style>
