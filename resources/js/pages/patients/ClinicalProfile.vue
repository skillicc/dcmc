<template>
    <div>
        <v-card :loading="loading">
            <v-card-title class="d-flex align-center justify-space-between">
                <div class="d-flex align-center">
                    <v-btn icon variant="text" :to="{ name: 'patients.show', params: { id: route.params.id } }" class="mr-2">
                        <v-icon>mdi-arrow-left</v-icon>
                    </v-btn>
                    <v-icon class="mr-2">mdi-clipboard-pulse</v-icon>
                    Clinical Profile - {{ patient.name }}
                </div>
                <v-chip v-if="patient.patient_id" color="primary" size="small">
                    {{ patient.patient_id }}
                </v-chip>
            </v-card-title>

            <!-- Chronic Disease Alerts -->
            <v-alert
                v-if="alerts.length"
                type="warning"
                variant="tonal"
                class="mx-4 mb-4"
                closable
            >
                <template v-slot:title>
                    <v-icon class="mr-2">mdi-alert</v-icon>
                    Chronic Disease Alerts
                </template>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <v-chip
                        v-for="alert in alerts"
                        :key="alert.id"
                        :color="alert.severity_color"
                        size="small"
                    >
                        {{ alert.disease_name }} ({{ alert.severity }})
                    </v-chip>
                </div>
            </v-alert>

            <v-card-text>
                <!-- Summary Stats -->
                <v-row class="mb-4">
                    <v-col cols="6" sm="4" md="2">
                        <v-card variant="tonal" color="error" class="text-center pa-3">
                            <div class="text-h5 font-weight-bold">{{ summary.chronic_diseases }}</div>
                            <div class="text-caption">Chronic Diseases</div>
                        </v-card>
                    </v-col>
                    <v-col cols="6" sm="4" md="2">
                        <v-card variant="tonal" color="primary" class="text-center pa-3">
                            <div class="text-h5 font-weight-bold">{{ summary.active_medications }}</div>
                            <div class="text-caption">Active Medications</div>
                        </v-card>
                    </v-col>
                    <v-col cols="6" sm="4" md="2">
                        <v-card variant="tonal" color="success" class="text-center pa-3">
                            <div class="text-h5 font-weight-bold">{{ summary.immunizations }}</div>
                            <div class="text-caption">Immunizations</div>
                        </v-card>
                    </v-col>
                    <v-col cols="6" sm="4" md="2">
                        <v-card variant="tonal" color="warning" class="text-center pa-3">
                            <div class="text-h5 font-weight-bold">{{ summary.family_diseases }}</div>
                            <div class="text-caption">Family History</div>
                        </v-card>
                    </v-col>
                    <v-col cols="6" sm="4" md="2">
                        <v-card variant="tonal" color="info" class="text-center pa-3">
                            <div class="text-h5 font-weight-bold">{{ summary.clinical_records }}</div>
                            <div class="text-caption">Clinical Records</div>
                        </v-card>
                    </v-col>
                    <v-col cols="6" sm="4" md="2">
                        <v-card
                            variant="tonal"
                            color="secondary"
                            class="text-center pa-3"
                            style="cursor: pointer;"
                            @click="showDrugInteractionDialog = true"
                        >
                            <v-icon size="32">mdi-pill-multiple</v-icon>
                            <div class="text-caption">Check Interaction</div>
                        </v-card>
                    </v-col>
                </v-row>

                <!-- Main Tabs -->
                <v-tabs v-model="activeTab" color="primary" class="mb-4">
                    <v-tab value="chronic">Chronic Diseases</v-tab>
                    <v-tab value="medications">Current Medications</v-tab>
                    <v-tab value="immunizations">Immunizations</v-tab>
                    <v-tab value="family">Family History</v-tab>
                    <v-tab value="clinical">Clinical Data</v-tab>
                    <v-tab value="trends">Trends & Charts</v-tab>
                </v-tabs>

                <v-tabs-window v-model="activeTab">
                    <!-- Chronic Diseases Tab -->
                    <v-tabs-window-item value="chronic">
                        <div class="d-flex justify-space-between align-center mb-4">
                            <h3 class="text-subtitle-1 font-weight-bold">Chronic Diseases</h3>
                            <v-btn color="primary" size="small" @click="openChronicDiseaseDialog()">
                                <v-icon start>mdi-plus</v-icon>
                                Add Disease
                            </v-btn>
                        </div>

                        <v-data-table
                            :headers="chronicDiseaseHeaders"
                            :items="chronicDiseases"
                            :items-per-page="10"
                            density="compact"
                        >
                            <template v-slot:item.disease_name="{ item }">
                                <div class="font-weight-medium">{{ item.disease_name }}</div>
                                <div class="text-caption text-grey" v-if="item.icd_code">ICD: {{ item.icd_code }}</div>
                            </template>
                            <template v-slot:item.severity="{ item }">
                                <v-chip :color="item.severity_color" size="x-small">
                                    {{ item.severity || 'N/A' }}
                                </v-chip>
                            </template>
                            <template v-slot:item.status="{ item }">
                                <v-chip :color="getStatusColor(item.status)" size="x-small">
                                    {{ item.status }}
                                </v-chip>
                            </template>
                            <template v-slot:item.show_alert="{ item }">
                                <v-icon v-if="item.show_alert" color="warning">mdi-bell</v-icon>
                            </template>
                            <template v-slot:item.actions="{ item }">
                                <v-btn icon variant="text" size="x-small" @click="openChronicDiseaseDialog(item)">
                                    <v-icon>mdi-pencil</v-icon>
                                </v-btn>
                                <v-btn icon variant="text" size="x-small" color="error" @click="deleteChronicDisease(item)">
                                    <v-icon>mdi-delete</v-icon>
                                </v-btn>
                            </template>
                        </v-data-table>
                    </v-tabs-window-item>

                    <!-- Current Medications Tab -->
                    <v-tabs-window-item value="medications">
                        <div class="d-flex justify-space-between align-center mb-4">
                            <h3 class="text-subtitle-1 font-weight-bold">Current Medications</h3>
                            <v-btn color="primary" size="small" @click="openMedicationDialog()">
                                <v-icon start>mdi-plus</v-icon>
                                Add Medication
                            </v-btn>
                        </div>

                        <v-data-table
                            :headers="medicationHeaders"
                            :items="medications"
                            :items-per-page="10"
                            density="compact"
                        >
                            <template v-slot:item.medication_name="{ item }">
                                <div class="font-weight-medium">{{ item.medication_name }}</div>
                                <div class="text-caption text-grey" v-if="item.generic_name">{{ item.generic_name }}</div>
                            </template>
                            <template v-slot:item.dosage="{ item }">
                                {{ item.dosage }} - {{ item.frequency }}
                            </template>
                            <template v-slot:item.status="{ item }">
                                <v-chip :color="item.status_color" size="x-small">
                                    {{ item.status }}
                                </v-chip>
                            </template>
                            <template v-slot:item.self="{ item }">
                                <v-icon v-if="item.is_self_prescribed" size="small" color="warning">mdi-account</v-icon>
                            </template>
                            <template v-slot:item.actions="{ item }">
                                <v-btn icon variant="text" size="x-small" @click="openMedicationDialog(item)">
                                    <v-icon>mdi-pencil</v-icon>
                                </v-btn>
                                <v-btn icon variant="text" size="x-small" color="error" @click="deleteMedication(item)">
                                    <v-icon>mdi-delete</v-icon>
                                </v-btn>
                            </template>
                        </v-data-table>
                    </v-tabs-window-item>

                    <!-- Immunizations Tab -->
                    <v-tabs-window-item value="immunizations">
                        <div class="d-flex justify-space-between align-center mb-4">
                            <h3 class="text-subtitle-1 font-weight-bold">Immunization History</h3>
                            <v-btn color="primary" size="small" @click="openImmunizationDialog()">
                                <v-icon start>mdi-plus</v-icon>
                                Add Immunization
                            </v-btn>
                        </div>

                        <v-data-table
                            :headers="immunizationHeaders"
                            :items="immunizations"
                            :items-per-page="10"
                            density="compact"
                        >
                            <template v-slot:item.vaccine_name="{ item }">
                                <div class="font-weight-medium">{{ item.vaccine_name }}</div>
                                <div class="text-caption text-grey" v-if="item.manufacturer">{{ item.manufacturer }}</div>
                            </template>
                            <template v-slot:item.dose="{ item }">
                                {{ item.dose_number || '-' }} / {{ item.total_doses || '-' }}
                            </template>
                            <template v-slot:item.administration_date="{ item }">
                                {{ formatDate(item.administration_date) }}
                            </template>
                            <template v-slot:item.next_dose_date="{ item }">
                                <span :class="{ 'text-error': item.is_overdue }">
                                    {{ formatDate(item.next_dose_date) || '-' }}
                                </span>
                            </template>
                            <template v-slot:item.status="{ item }">
                                <v-chip :color="item.status_color" size="x-small">
                                    {{ item.status }}
                                </v-chip>
                            </template>
                            <template v-slot:item.actions="{ item }">
                                <v-btn icon variant="text" size="x-small" @click="openImmunizationDialog(item)">
                                    <v-icon>mdi-pencil</v-icon>
                                </v-btn>
                                <v-btn icon variant="text" size="x-small" color="error" @click="deleteImmunization(item)">
                                    <v-icon>mdi-delete</v-icon>
                                </v-btn>
                            </template>
                        </v-data-table>
                    </v-tabs-window-item>

                    <!-- Family History Tab -->
                    <v-tabs-window-item value="family">
                        <div class="d-flex justify-space-between align-center mb-4">
                            <h3 class="text-subtitle-1 font-weight-bold">Family Disease History</h3>
                            <v-btn color="primary" size="small" @click="openFamilyDiseaseDialog()">
                                <v-icon start>mdi-plus</v-icon>
                                Add Family History
                            </v-btn>
                        </div>

                        <v-data-table
                            :headers="familyDiseaseHeaders"
                            :items="familyDiseases"
                            :items-per-page="10"
                            density="compact"
                        >
                            <template v-slot:item.disease_name="{ item }">
                                <div class="font-weight-medium">{{ item.disease_name }}</div>
                                <div class="text-caption text-grey" v-if="item.icd_code">ICD: {{ item.icd_code }}</div>
                            </template>
                            <template v-slot:item.relative="{ item }">
                                <div>{{ item.relationship }}</div>
                                <div class="text-caption text-grey" v-if="item.relative_name">{{ item.relative_name }}</div>
                            </template>
                            <template v-slot:item.is_alive="{ item }">
                                <v-icon :color="item.is_alive ? 'success' : 'error'" size="small">
                                    {{ item.is_alive ? 'mdi-heart' : 'mdi-heart-broken' }}
                                </v-icon>
                            </template>
                            <template v-slot:item.actions="{ item }">
                                <v-btn icon variant="text" size="x-small" @click="openFamilyDiseaseDialog(item)">
                                    <v-icon>mdi-pencil</v-icon>
                                </v-btn>
                                <v-btn icon variant="text" size="x-small" color="error" @click="deleteFamilyDisease(item)">
                                    <v-icon>mdi-delete</v-icon>
                                </v-btn>
                            </template>
                        </v-data-table>
                    </v-tabs-window-item>

                    <!-- Clinical Data Tab -->
                    <v-tabs-window-item value="clinical">
                        <div class="d-flex justify-space-between align-center mb-4">
                            <h3 class="text-subtitle-1 font-weight-bold">Clinical Data Records</h3>
                            <v-btn color="primary" size="small" @click="openClinicalDataDialog()">
                                <v-icon start>mdi-plus</v-icon>
                                Add Record
                            </v-btn>
                        </div>

                        <v-data-table
                            :headers="clinicalDataHeaders"
                            :items="clinicalData"
                            :items-per-page="10"
                            density="compact"
                        >
                            <template v-slot:item.record_date="{ item }">
                                {{ formatDate(item.record_date) }}
                            </template>
                            <template v-slot:item.title="{ item }">
                                <div class="font-weight-medium">{{ item.title }}</div>
                                <div class="text-caption text-grey">{{ item.category }}</div>
                            </template>
                            <template v-slot:item.severity="{ item }">
                                <v-chip v-if="item.severity" :color="getSeverityColor(item.severity)" size="x-small">
                                    {{ item.severity }}
                                </v-chip>
                            </template>
                            <template v-slot:item.status="{ item }">
                                <v-chip :color="getClinicalStatusColor(item.status)" size="x-small">
                                    {{ item.status }}
                                </v-chip>
                            </template>
                            <template v-slot:item.actions="{ item }">
                                <v-btn icon variant="text" size="x-small" @click="viewClinicalData(item)">
                                    <v-icon>mdi-eye</v-icon>
                                </v-btn>
                                <v-btn icon variant="text" size="x-small" @click="openClinicalDataDialog(item)">
                                    <v-icon>mdi-pencil</v-icon>
                                </v-btn>
                                <v-btn icon variant="text" size="x-small" color="error" @click="deleteClinicalData(item)">
                                    <v-icon>mdi-delete</v-icon>
                                </v-btn>
                            </template>
                        </v-data-table>
                    </v-tabs-window-item>

                    <!-- Trends & Charts Tab -->
                    <v-tabs-window-item value="trends">
                        <v-row>
                            <v-col cols="12">
                                <v-card variant="outlined">
                                    <v-card-title class="text-subtitle-1">
                                        <v-icon class="mr-2">mdi-chart-line</v-icon>
                                        Vital Signs Trends (Last {{ trendDays }} days)
                                        <v-spacer></v-spacer>
                                        <v-btn-toggle v-model="trendDays" density="compact" mandatory>
                                            <v-btn :value="7">7d</v-btn>
                                            <v-btn :value="30">30d</v-btn>
                                            <v-btn :value="90">90d</v-btn>
                                        </v-btn-toggle>
                                    </v-card-title>
                                    <v-card-text>
                                        <v-row>
                                            <v-col cols="12" md="6">
                                                <h4 class="text-subtitle-2 mb-2">Blood Pressure</h4>
                                                <div v-if="Object.keys(vitalTrends).length" class="trend-chart">
                                                    <div v-for="(data, date) in vitalTrends" :key="date" class="trend-item">
                                                        <span class="date">{{ formatShortDate(date) }}</span>
                                                        <span class="value">
                                                            {{ Math.round(data.blood_pressure_systolic) }}/{{ Math.round(data.blood_pressure_diastolic) }}
                                                        </span>
                                                        <v-progress-linear
                                                            :model-value="(data.blood_pressure_systolic / 200) * 100"
                                                            :color="getBpColor(data.blood_pressure_systolic)"
                                                            height="10"
                                                            class="mt-1"
                                                        ></v-progress-linear>
                                                    </div>
                                                </div>
                                                <p v-else class="text-grey text-center">No data available</p>
                                            </v-col>
                                            <v-col cols="12" md="6">
                                                <h4 class="text-subtitle-2 mb-2">Pulse Rate</h4>
                                                <div v-if="Object.keys(vitalTrends).length" class="trend-chart">
                                                    <div v-for="(data, date) in vitalTrends" :key="date" class="trend-item">
                                                        <span class="date">{{ formatShortDate(date) }}</span>
                                                        <span class="value">{{ Math.round(data.pulse) }} bpm</span>
                                                        <v-progress-linear
                                                            :model-value="(data.pulse / 150) * 100"
                                                            :color="getPulseColor(data.pulse)"
                                                            height="10"
                                                            class="mt-1"
                                                        ></v-progress-linear>
                                                    </div>
                                                </div>
                                                <p v-else class="text-grey text-center">No data available</p>
                                            </v-col>
                                            <v-col cols="12" md="6">
                                                <h4 class="text-subtitle-2 mb-2">Temperature</h4>
                                                <div v-if="Object.keys(vitalTrends).length" class="trend-chart">
                                                    <div v-for="(data, date) in vitalTrends" :key="date" class="trend-item">
                                                        <span class="date">{{ formatShortDate(date) }}</span>
                                                        <span class="value">{{ data.temperature?.toFixed(1) }}°F</span>
                                                        <v-progress-linear
                                                            :model-value="((data.temperature - 95) / 10) * 100"
                                                            :color="getTempColor(data.temperature)"
                                                            height="10"
                                                            class="mt-1"
                                                        ></v-progress-linear>
                                                    </div>
                                                </div>
                                                <p v-else class="text-grey text-center">No data available</p>
                                            </v-col>
                                            <v-col cols="12" md="6">
                                                <h4 class="text-subtitle-2 mb-2">Blood Sugar</h4>
                                                <div v-if="Object.keys(vitalTrends).length" class="trend-chart">
                                                    <div v-for="(data, date) in vitalTrends" :key="date" class="trend-item">
                                                        <span class="date">{{ formatShortDate(date) }}</span>
                                                        <span class="value">{{ data.blood_sugar || '-' }} mg/dL</span>
                                                        <v-progress-linear
                                                            :model-value="(data.blood_sugar / 300) * 100"
                                                            :color="getSugarColor(data.blood_sugar)"
                                                            height="10"
                                                            class="mt-1"
                                                        ></v-progress-linear>
                                                    </div>
                                                </div>
                                                <p v-else class="text-grey text-center">No data available</p>
                                            </v-col>
                                        </v-row>
                                    </v-card-text>
                                </v-card>
                            </v-col>
                        </v-row>
                    </v-tabs-window-item>
                </v-tabs-window>
            </v-card-text>
        </v-card>

        <!-- Chronic Disease Dialog -->
        <v-dialog v-model="chronicDiseaseDialog" max-width="600">
            <v-card>
                <v-card-title>{{ editingChronicDisease.id ? 'Edit' : 'Add' }} Chronic Disease</v-card-title>
                <v-card-text>
                    <v-form ref="chronicDiseaseForm">
                        <v-autocomplete
                            v-model="editingChronicDisease.disease_name"
                            :items="commonChronicDiseases"
                            label="Disease Name *"
                            :rules="[v => !!v || 'Required']"
                            density="compact"
                            class="mb-3"
                        ></v-autocomplete>
                        <v-text-field
                            v-model="editingChronicDisease.icd_code"
                            label="ICD Code"
                            density="compact"
                            class="mb-3"
                        ></v-text-field>
                        <v-row>
                            <v-col cols="6">
                                <v-text-field
                                    v-model="editingChronicDisease.diagnosed_date"
                                    label="Diagnosed Date"
                                    type="date"
                                    density="compact"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="6">
                                <v-select
                                    v-model="editingChronicDisease.severity"
                                    :items="['Mild', 'Moderate', 'Severe', 'Critical']"
                                    label="Severity"
                                    density="compact"
                                ></v-select>
                            </v-col>
                        </v-row>
                        <v-select
                            v-model="editingChronicDisease.status"
                            :items="['Active', 'Controlled', 'In Remission', 'Resolved']"
                            label="Status"
                            density="compact"
                            class="mb-3"
                        ></v-select>
                        <v-textarea
                            v-model="editingChronicDisease.current_treatment"
                            label="Current Treatment"
                            rows="2"
                            density="compact"
                            class="mb-3"
                        ></v-textarea>
                        <v-textarea
                            v-model="editingChronicDisease.notes"
                            label="Notes"
                            rows="2"
                            density="compact"
                            class="mb-3"
                        ></v-textarea>
                        <v-switch
                            v-model="editingChronicDisease.show_alert"
                            label="Show Alert"
                            color="warning"
                            density="compact"
                        ></v-switch>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="chronicDiseaseDialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="saveChronicDisease" :loading="saving">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Medication Dialog -->
        <v-dialog v-model="medicationDialog" max-width="600">
            <v-card>
                <v-card-title>{{ editingMedication.id ? 'Edit' : 'Add' }} Current Medication</v-card-title>
                <v-card-text>
                    <v-form ref="medicationForm">
                        <v-text-field
                            v-model="editingMedication.medication_name"
                            label="Medication Name *"
                            :rules="[v => !!v || 'Required']"
                            density="compact"
                            class="mb-3"
                        ></v-text-field>
                        <v-text-field
                            v-model="editingMedication.generic_name"
                            label="Generic Name"
                            density="compact"
                            class="mb-3"
                        ></v-text-field>
                        <v-row>
                            <v-col cols="6">
                                <v-text-field
                                    v-model="editingMedication.dosage"
                                    label="Dosage"
                                    density="compact"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="6">
                                <v-autocomplete
                                    v-model="editingMedication.frequency"
                                    :items="medicationOptions.frequencies"
                                    label="Frequency"
                                    density="compact"
                                ></v-autocomplete>
                            </v-col>
                        </v-row>
                        <v-row>
                            <v-col cols="6">
                                <v-autocomplete
                                    v-model="editingMedication.route"
                                    :items="medicationOptions.routes"
                                    label="Route"
                                    density="compact"
                                ></v-autocomplete>
                            </v-col>
                            <v-col cols="6">
                                <v-select
                                    v-model="editingMedication.status"
                                    :items="['Active', 'Discontinued', 'Completed', 'On Hold']"
                                    label="Status"
                                    density="compact"
                                ></v-select>
                            </v-col>
                        </v-row>
                        <v-row>
                            <v-col cols="6">
                                <v-text-field
                                    v-model="editingMedication.start_date"
                                    label="Start Date"
                                    type="date"
                                    density="compact"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="6">
                                <v-text-field
                                    v-model="editingMedication.end_date"
                                    label="End Date"
                                    type="date"
                                    density="compact"
                                ></v-text-field>
                            </v-col>
                        </v-row>
                        <v-textarea
                            v-model="editingMedication.reason"
                            label="Reason / Indication"
                            rows="2"
                            density="compact"
                            class="mb-3"
                        ></v-textarea>
                        <v-switch
                            v-model="editingMedication.is_self_prescribed"
                            label="Self Prescribed"
                            color="warning"
                            density="compact"
                        ></v-switch>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="medicationDialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="saveMedication" :loading="saving">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Immunization Dialog -->
        <v-dialog v-model="immunizationDialog" max-width="600">
            <v-card>
                <v-card-title>{{ editingImmunization.id ? 'Edit' : 'Add' }} Immunization</v-card-title>
                <v-card-text>
                    <v-form ref="immunizationForm">
                        <v-autocomplete
                            v-model="editingImmunization.vaccine_name"
                            :items="vaccineOptions.vaccines"
                            label="Vaccine Name *"
                            :rules="[v => !!v || 'Required']"
                            density="compact"
                            class="mb-3"
                        ></v-autocomplete>
                        <v-row>
                            <v-col cols="6">
                                <v-text-field
                                    v-model="editingImmunization.dose_number"
                                    label="Dose Number"
                                    type="number"
                                    density="compact"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="6">
                                <v-text-field
                                    v-model="editingImmunization.total_doses"
                                    label="Total Doses"
                                    type="number"
                                    density="compact"
                                ></v-text-field>
                            </v-col>
                        </v-row>
                        <v-row>
                            <v-col cols="6">
                                <v-text-field
                                    v-model="editingImmunization.administration_date"
                                    label="Administration Date *"
                                    type="date"
                                    :rules="[v => !!v || 'Required']"
                                    density="compact"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="6">
                                <v-text-field
                                    v-model="editingImmunization.next_dose_date"
                                    label="Next Dose Date"
                                    type="date"
                                    density="compact"
                                ></v-text-field>
                            </v-col>
                        </v-row>
                        <v-row>
                            <v-col cols="6">
                                <v-autocomplete
                                    v-model="editingImmunization.site"
                                    :items="vaccineOptions.sites"
                                    label="Injection Site"
                                    density="compact"
                                ></v-autocomplete>
                            </v-col>
                            <v-col cols="6">
                                <v-autocomplete
                                    v-model="editingImmunization.route"
                                    :items="vaccineOptions.routes"
                                    label="Route"
                                    density="compact"
                                ></v-autocomplete>
                            </v-col>
                        </v-row>
                        <v-row>
                            <v-col cols="6">
                                <v-text-field
                                    v-model="editingImmunization.lot_number"
                                    label="Lot Number"
                                    density="compact"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="6">
                                <v-text-field
                                    v-model="editingImmunization.manufacturer"
                                    label="Manufacturer"
                                    density="compact"
                                ></v-text-field>
                            </v-col>
                        </v-row>
                        <v-select
                            v-model="editingImmunization.status"
                            :items="['Completed', 'In Progress', 'Not Started']"
                            label="Status"
                            density="compact"
                            class="mb-3"
                        ></v-select>
                        <v-textarea
                            v-model="editingImmunization.side_effects"
                            label="Side Effects (if any)"
                            rows="2"
                            density="compact"
                        ></v-textarea>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="immunizationDialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="saveImmunization" :loading="saving">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Family Disease Dialog -->
        <v-dialog v-model="familyDiseaseDialog" max-width="600">
            <v-card>
                <v-card-title>{{ editingFamilyDisease.id ? 'Edit' : 'Add' }} Family Disease History</v-card-title>
                <v-card-text>
                    <v-form ref="familyDiseaseForm">
                        <v-autocomplete
                            v-model="editingFamilyDisease.disease_name"
                            :items="familyDiseaseOptions.diseases"
                            label="Disease Name *"
                            :rules="[v => !!v || 'Required']"
                            density="compact"
                            class="mb-3"
                        ></v-autocomplete>
                        <v-row>
                            <v-col cols="6">
                                <v-autocomplete
                                    v-model="editingFamilyDisease.relationship"
                                    :items="familyDiseaseOptions.relationships"
                                    label="Relationship *"
                                    :rules="[v => !!v || 'Required']"
                                    density="compact"
                                ></v-autocomplete>
                            </v-col>
                            <v-col cols="6">
                                <v-text-field
                                    v-model="editingFamilyDisease.relative_name"
                                    label="Relative Name"
                                    density="compact"
                                ></v-text-field>
                            </v-col>
                        </v-row>
                        <v-row>
                            <v-col cols="6">
                                <v-text-field
                                    v-model="editingFamilyDisease.onset_age"
                                    label="Onset Age"
                                    type="number"
                                    density="compact"
                                ></v-text-field>
                            </v-col>
                            <v-col cols="6">
                                <v-switch
                                    v-model="editingFamilyDisease.is_alive"
                                    label="Is Alive"
                                    color="success"
                                    density="compact"
                                ></v-switch>
                            </v-col>
                        </v-row>
                        <v-text-field
                            v-if="!editingFamilyDisease.is_alive"
                            v-model="editingFamilyDisease.cause_of_death"
                            label="Cause of Death"
                            density="compact"
                            class="mb-3"
                        ></v-text-field>
                        <v-textarea
                            v-model="editingFamilyDisease.notes"
                            label="Notes"
                            rows="2"
                            density="compact"
                        ></v-textarea>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="familyDiseaseDialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="saveFamilyDisease" :loading="saving">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Clinical Data Dialog -->
        <v-dialog v-model="clinicalDataDialog" max-width="600">
            <v-card>
                <v-card-title>{{ editingClinicalData.id ? 'Edit' : 'Add' }} Clinical Data</v-card-title>
                <v-card-text>
                    <v-form ref="clinicalDataForm">
                        <v-row>
                            <v-col cols="6">
                                <v-autocomplete
                                    v-model="editingClinicalData.category"
                                    :items="clinicalDataCategories"
                                    label="Category *"
                                    :rules="[v => !!v || 'Required']"
                                    density="compact"
                                ></v-autocomplete>
                            </v-col>
                            <v-col cols="6">
                                <v-text-field
                                    v-model="editingClinicalData.record_date"
                                    label="Date *"
                                    type="date"
                                    :rules="[v => !!v || 'Required']"
                                    density="compact"
                                ></v-text-field>
                            </v-col>
                        </v-row>
                        <v-text-field
                            v-model="editingClinicalData.title"
                            label="Title *"
                            :rules="[v => !!v || 'Required']"
                            density="compact"
                            class="mb-3"
                        ></v-text-field>
                        <v-textarea
                            v-model="editingClinicalData.description"
                            label="Description"
                            rows="3"
                            density="compact"
                            class="mb-3"
                        ></v-textarea>
                        <v-row>
                            <v-col cols="6">
                                <v-select
                                    v-model="editingClinicalData.severity"
                                    :items="['Mild', 'Moderate', 'Severe', 'Critical']"
                                    label="Severity"
                                    density="compact"
                                    clearable
                                ></v-select>
                            </v-col>
                            <v-col cols="6">
                                <v-select
                                    v-model="editingClinicalData.status"
                                    :items="['Active', 'Resolved', 'Chronic', 'Monitoring']"
                                    label="Status"
                                    density="compact"
                                ></v-select>
                            </v-col>
                        </v-row>
                        <v-textarea
                            v-model="editingClinicalData.notes"
                            label="Notes"
                            rows="2"
                            density="compact"
                        ></v-textarea>
                    </v-form>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="clinicalDataDialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="saveClinicalData" :loading="saving">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Clinical Data View Dialog -->
        <v-dialog v-model="viewClinicalDataDialog" max-width="500">
            <v-card>
                <v-card-title>{{ viewingClinicalData.title }}</v-card-title>
                <v-card-text>
                    <v-list density="compact">
                        <v-list-item>
                            <template v-slot:prepend><v-icon>mdi-folder</v-icon></template>
                            <v-list-item-title>{{ viewingClinicalData.category }}</v-list-item-title>
                            <v-list-item-subtitle>Category</v-list-item-subtitle>
                        </v-list-item>
                        <v-list-item>
                            <template v-slot:prepend><v-icon>mdi-calendar</v-icon></template>
                            <v-list-item-title>{{ formatDate(viewingClinicalData.record_date) }}</v-list-item-title>
                            <v-list-item-subtitle>Date</v-list-item-subtitle>
                        </v-list-item>
                        <v-list-item v-if="viewingClinicalData.severity">
                            <template v-slot:prepend><v-icon>mdi-alert-circle</v-icon></template>
                            <v-list-item-title>{{ viewingClinicalData.severity }}</v-list-item-title>
                            <v-list-item-subtitle>Severity</v-list-item-subtitle>
                        </v-list-item>
                        <v-list-item>
                            <template v-slot:prepend><v-icon>mdi-checkbox-marked-circle</v-icon></template>
                            <v-list-item-title>{{ viewingClinicalData.status }}</v-list-item-title>
                            <v-list-item-subtitle>Status</v-list-item-subtitle>
                        </v-list-item>
                    </v-list>
                    <v-divider class="my-3"></v-divider>
                    <h4 class="text-subtitle-2 mb-2">Description</h4>
                    <p>{{ viewingClinicalData.description || 'No description' }}</p>
                    <h4 class="text-subtitle-2 mb-2 mt-3">Notes</h4>
                    <p>{{ viewingClinicalData.notes || 'No notes' }}</p>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="viewClinicalDataDialog = false">Close</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>

        <!-- Drug Interaction Check Dialog -->
        <v-dialog v-model="showDrugInteractionDialog" max-width="600">
            <v-card>
                <v-card-title>
                    <v-icon class="mr-2">mdi-pill-multiple</v-icon>
                    Check Drug Interactions
                </v-card-title>
                <v-card-text>
                    <v-text-field
                        v-model="drugToCheck"
                        label="Enter Drug Name"
                        hint="Check if this drug has any interactions with patient's conditions or current medications"
                        density="compact"
                        class="mb-4"
                        @keyup.enter="checkDrugInteractions"
                    ></v-text-field>

                    <v-btn
                        color="primary"
                        block
                        @click="checkDrugInteractions"
                        :loading="checkingInteractions"
                    >
                        Check Interactions
                    </v-btn>

                    <div v-if="interactionResults.length" class="mt-4">
                        <v-alert
                            v-for="(result, index) in interactionResults"
                            :key="index"
                            :type="result.interaction.severity === 'Major' || result.interaction.severity === 'Critical' ? 'error' : 'warning'"
                            variant="tonal"
                            class="mb-2"
                        >
                            <div class="font-weight-bold">
                                {{ result.type === 'drug' ? 'Drug-Drug Interaction' : 'Disease-Drug Interaction' }}
                            </div>
                            <div v-if="result.type === 'drug'">
                                {{ result.interaction.drug_1_name }} + {{ result.interaction.drug_2_name }}
                            </div>
                            <div v-else>
                                {{ result.interaction.disease_name }} - {{ result.interaction.drug_name }}
                            </div>
                            <div class="text-caption mt-1">{{ result.interaction.description }}</div>
                        </v-alert>
                    </div>
                    <v-alert v-else-if="interactionChecked && !interactionResults.length" type="success" variant="tonal" class="mt-4">
                        No interactions found. This drug appears safe to use with patient's current conditions and medications.
                    </v-alert>
                </v-card-text>
                <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn @click="showDrugInteractionDialog = false">Close</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

const route = useRoute();
const loading = ref(true);
const saving = ref(false);
const activeTab = ref('chronic');
const trendDays = ref(30);

// Patient data
const patient = reactive({});
const summary = reactive({
    chronic_diseases: 0,
    active_medications: 0,
    immunizations: 0,
    family_diseases: 0,
    clinical_records: 0,
});
const alerts = ref([]);

// Data lists
const chronicDiseases = ref([]);
const medications = ref([]);
const immunizations = ref([]);
const familyDiseases = ref([]);
const clinicalData = ref([]);
const vitalTrends = ref({});

// Options data
const commonChronicDiseases = ref([]);
const medicationOptions = reactive({ frequencies: [], routes: [] });
const vaccineOptions = reactive({ vaccines: [], routes: [], sites: [] });
const familyDiseaseOptions = reactive({ diseases: [], relationships: [] });
const clinicalDataCategories = ref([]);

// Dialogs
const chronicDiseaseDialog = ref(false);
const medicationDialog = ref(false);
const immunizationDialog = ref(false);
const familyDiseaseDialog = ref(false);
const clinicalDataDialog = ref(false);
const viewClinicalDataDialog = ref(false);
const showDrugInteractionDialog = ref(false);

// Editing items
const editingChronicDisease = reactive({});
const editingMedication = reactive({});
const editingImmunization = reactive({});
const editingFamilyDisease = reactive({ is_alive: true });
const editingClinicalData = reactive({});
const viewingClinicalData = reactive({});

// Drug interaction check
const drugToCheck = ref('');
const checkingInteractions = ref(false);
const interactionResults = ref([]);
const interactionChecked = ref(false);

// Table headers
const chronicDiseaseHeaders = [
    { title: 'Disease', key: 'disease_name' },
    { title: 'Severity', key: 'severity' },
    { title: 'Status', key: 'status' },
    { title: 'Diagnosed', key: 'diagnosed_date' },
    { title: 'Alert', key: 'show_alert' },
    { title: 'Actions', key: 'actions', sortable: false },
];

const medicationHeaders = [
    { title: 'Medication', key: 'medication_name' },
    { title: 'Dosage & Frequency', key: 'dosage' },
    { title: 'Start Date', key: 'start_date' },
    { title: 'Status', key: 'status' },
    { title: 'Self', key: 'self' },
    { title: 'Actions', key: 'actions', sortable: false },
];

const immunizationHeaders = [
    { title: 'Vaccine', key: 'vaccine_name' },
    { title: 'Dose', key: 'dose' },
    { title: 'Given Date', key: 'administration_date' },
    { title: 'Next Dose', key: 'next_dose_date' },
    { title: 'Status', key: 'status' },
    { title: 'Actions', key: 'actions', sortable: false },
];

const familyDiseaseHeaders = [
    { title: 'Disease', key: 'disease_name' },
    { title: 'Relative', key: 'relative' },
    { title: 'Onset Age', key: 'onset_age' },
    { title: 'Alive', key: 'is_alive' },
    { title: 'Actions', key: 'actions', sortable: false },
];

const clinicalDataHeaders = [
    { title: 'Date', key: 'record_date' },
    { title: 'Title', key: 'title' },
    { title: 'Severity', key: 'severity' },
    { title: 'Status', key: 'status' },
    { title: 'Actions', key: 'actions', sortable: false },
];

// Helper functions
const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
};

const formatShortDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short' });
};

const getStatusColor = (status) => {
    const colors = { Active: 'error', Controlled: 'success', 'In Remission': 'info', Resolved: 'grey' };
    return colors[status] || 'grey';
};

const getSeverityColor = (severity) => {
    const colors = { Mild: 'info', Moderate: 'warning', Severe: 'error', Critical: 'error' };
    return colors[severity] || 'grey';
};

const getClinicalStatusColor = (status) => {
    const colors = { Active: 'error', Resolved: 'success', Chronic: 'warning', Monitoring: 'info' };
    return colors[status] || 'grey';
};

const getBpColor = (systolic) => {
    if (systolic < 90) return 'info';
    if (systolic < 120) return 'success';
    if (systolic < 140) return 'warning';
    return 'error';
};

const getPulseColor = (pulse) => {
    if (pulse < 60) return 'info';
    if (pulse < 100) return 'success';
    return 'error';
};

const getTempColor = (temp) => {
    if (!temp) return 'grey';
    if (temp < 97) return 'info';
    if (temp < 99.5) return 'success';
    return 'error';
};

const getSugarColor = (sugar) => {
    if (!sugar) return 'grey';
    if (sugar < 70) return 'info';
    if (sugar < 140) return 'success';
    if (sugar < 200) return 'warning';
    return 'error';
};

// API calls
const fetchClinicalProfile = async () => {
    loading.value = true;
    try {
        const response = await axios.get(`/api/patients/${route.params.id}/emr/clinical-profile`);
        Object.assign(patient, response.data.data.patient);
        Object.assign(summary, response.data.data.summary);
        alerts.value = response.data.data.alerts || [];
    } catch (error) {
        console.error('Failed to load clinical profile:', error);
    }
    loading.value = false;
};

const fetchChronicDiseases = async () => {
    try {
        const response = await axios.get(`/api/patients/${route.params.id}/emr/chronic-diseases`);
        chronicDiseases.value = response.data.data || [];
    } catch (error) {
        console.error('Failed to load chronic diseases:', error);
    }
};

const fetchMedications = async () => {
    try {
        const response = await axios.get(`/api/patients/${route.params.id}/emr/medications`);
        medications.value = response.data.data || [];
    } catch (error) {
        console.error('Failed to load medications:', error);
    }
};

const fetchImmunizations = async () => {
    try {
        const response = await axios.get(`/api/patients/${route.params.id}/emr/immunizations`);
        immunizations.value = response.data.data || [];
    } catch (error) {
        console.error('Failed to load immunizations:', error);
    }
};

const fetchFamilyDiseases = async () => {
    try {
        const response = await axios.get(`/api/patients/${route.params.id}/emr/family-diseases`);
        familyDiseases.value = response.data.data || [];
    } catch (error) {
        console.error('Failed to load family diseases:', error);
    }
};

const fetchClinicalData = async () => {
    try {
        const response = await axios.get(`/api/patients/${route.params.id}/emr/clinical-data`);
        clinicalData.value = response.data.data || [];
    } catch (error) {
        console.error('Failed to load clinical data:', error);
    }
};

const fetchVitalTrends = async () => {
    try {
        const response = await axios.get(`/api/patients/${route.params.id}/emr/vital-trends`, {
            params: { days: trendDays.value }
        });
        vitalTrends.value = response.data.data || {};
    } catch (error) {
        console.error('Failed to load vital trends:', error);
    }
};

const fetchOptions = async () => {
    try {
        const [chronic, vaccines, family, medications, categories] = await Promise.all([
            axios.get('/api/emr/common-chronic-diseases'),
            axios.get('/api/emr/common-vaccines'),
            axios.get('/api/emr/family-disease-options'),
            axios.get('/api/emr/medication-options'),
            axios.get('/api/emr/clinical-data-categories'),
        ]);
        commonChronicDiseases.value = chronic.data.data || [];
        Object.assign(vaccineOptions, vaccines.data.data || {});
        Object.assign(familyDiseaseOptions, family.data.data || {});
        Object.assign(medicationOptions, medications.data.data || {});
        clinicalDataCategories.value = categories.data.data || [];
    } catch (error) {
        console.error('Failed to load options:', error);
    }
};

// Dialog openers
const openChronicDiseaseDialog = (item = null) => {
    if (item) {
        Object.assign(editingChronicDisease, { ...item });
    } else {
        Object.keys(editingChronicDisease).forEach(k => delete editingChronicDisease[k]);
        editingChronicDisease.status = 'Active';
        editingChronicDisease.show_alert = false;
    }
    chronicDiseaseDialog.value = true;
};

const openMedicationDialog = (item = null) => {
    if (item) {
        Object.assign(editingMedication, { ...item });
    } else {
        Object.keys(editingMedication).forEach(k => delete editingMedication[k]);
        editingMedication.status = 'Active';
        editingMedication.is_self_prescribed = false;
    }
    medicationDialog.value = true;
};

const openImmunizationDialog = (item = null) => {
    if (item) {
        Object.assign(editingImmunization, { ...item });
    } else {
        Object.keys(editingImmunization).forEach(k => delete editingImmunization[k]);
        editingImmunization.status = 'In Progress';
    }
    immunizationDialog.value = true;
};

const openFamilyDiseaseDialog = (item = null) => {
    if (item) {
        Object.assign(editingFamilyDisease, { ...item });
    } else {
        Object.keys(editingFamilyDisease).forEach(k => delete editingFamilyDisease[k]);
        editingFamilyDisease.is_alive = true;
    }
    familyDiseaseDialog.value = true;
};

const openClinicalDataDialog = (item = null) => {
    if (item) {
        Object.assign(editingClinicalData, { ...item });
    } else {
        Object.keys(editingClinicalData).forEach(k => delete editingClinicalData[k]);
        editingClinicalData.status = 'Active';
        editingClinicalData.record_date = new Date().toISOString().split('T')[0];
    }
    clinicalDataDialog.value = true;
};

const viewClinicalData = (item) => {
    Object.assign(viewingClinicalData, item);
    viewClinicalDataDialog.value = true;
};

// Save functions
const saveChronicDisease = async () => {
    saving.value = true;
    try {
        if (editingChronicDisease.id) {
            await axios.put(`/api/patients/${route.params.id}/emr/chronic-diseases/${editingChronicDisease.id}`, editingChronicDisease);
        } else {
            await axios.post(`/api/patients/${route.params.id}/emr/chronic-diseases`, editingChronicDisease);
        }
        chronicDiseaseDialog.value = false;
        fetchChronicDiseases();
        fetchClinicalProfile();
    } catch (error) {
        console.error('Failed to save chronic disease:', error);
    }
    saving.value = false;
};

const saveMedication = async () => {
    saving.value = true;
    try {
        if (editingMedication.id) {
            await axios.put(`/api/patients/${route.params.id}/emr/medications/${editingMedication.id}`, editingMedication);
        } else {
            await axios.post(`/api/patients/${route.params.id}/emr/medications`, editingMedication);
        }
        medicationDialog.value = false;
        fetchMedications();
        fetchClinicalProfile();
    } catch (error) {
        console.error('Failed to save medication:', error);
    }
    saving.value = false;
};

const saveImmunization = async () => {
    saving.value = true;
    try {
        if (editingImmunization.id) {
            await axios.put(`/api/patients/${route.params.id}/emr/immunizations/${editingImmunization.id}`, editingImmunization);
        } else {
            await axios.post(`/api/patients/${route.params.id}/emr/immunizations`, editingImmunization);
        }
        immunizationDialog.value = false;
        fetchImmunizations();
        fetchClinicalProfile();
    } catch (error) {
        console.error('Failed to save immunization:', error);
    }
    saving.value = false;
};

const saveFamilyDisease = async () => {
    saving.value = true;
    try {
        if (editingFamilyDisease.id) {
            await axios.put(`/api/patients/${route.params.id}/emr/family-diseases/${editingFamilyDisease.id}`, editingFamilyDisease);
        } else {
            await axios.post(`/api/patients/${route.params.id}/emr/family-diseases`, editingFamilyDisease);
        }
        familyDiseaseDialog.value = false;
        fetchFamilyDiseases();
        fetchClinicalProfile();
    } catch (error) {
        console.error('Failed to save family disease:', error);
    }
    saving.value = false;
};

const saveClinicalData = async () => {
    saving.value = true;
    try {
        if (editingClinicalData.id) {
            await axios.put(`/api/patients/${route.params.id}/emr/clinical-data/${editingClinicalData.id}`, editingClinicalData);
        } else {
            await axios.post(`/api/patients/${route.params.id}/emr/clinical-data`, editingClinicalData);
        }
        clinicalDataDialog.value = false;
        fetchClinicalData();
        fetchClinicalProfile();
    } catch (error) {
        console.error('Failed to save clinical data:', error);
    }
    saving.value = false;
};

// Delete functions
const deleteChronicDisease = async (item) => {
    if (!confirm('Are you sure you want to delete this chronic disease record?')) return;
    try {
        await axios.delete(`/api/patients/${route.params.id}/emr/chronic-diseases/${item.id}`);
        fetchChronicDiseases();
        fetchClinicalProfile();
    } catch (error) {
        console.error('Failed to delete:', error);
    }
};

const deleteMedication = async (item) => {
    if (!confirm('Are you sure you want to delete this medication record?')) return;
    try {
        await axios.delete(`/api/patients/${route.params.id}/emr/medications/${item.id}`);
        fetchMedications();
        fetchClinicalProfile();
    } catch (error) {
        console.error('Failed to delete:', error);
    }
};

const deleteImmunization = async (item) => {
    if (!confirm('Are you sure you want to delete this immunization record?')) return;
    try {
        await axios.delete(`/api/patients/${route.params.id}/emr/immunizations/${item.id}`);
        fetchImmunizations();
        fetchClinicalProfile();
    } catch (error) {
        console.error('Failed to delete:', error);
    }
};

const deleteFamilyDisease = async (item) => {
    if (!confirm('Are you sure you want to delete this family disease record?')) return;
    try {
        await axios.delete(`/api/patients/${route.params.id}/emr/family-diseases/${item.id}`);
        fetchFamilyDiseases();
        fetchClinicalProfile();
    } catch (error) {
        console.error('Failed to delete:', error);
    }
};

const deleteClinicalData = async (item) => {
    if (!confirm('Are you sure you want to delete this clinical data record?')) return;
    try {
        await axios.delete(`/api/patients/${route.params.id}/emr/clinical-data/${item.id}`);
        fetchClinicalData();
        fetchClinicalProfile();
    } catch (error) {
        console.error('Failed to delete:', error);
    }
};

// Drug interaction check
const checkDrugInteractions = async () => {
    if (!drugToCheck.value) return;
    checkingInteractions.value = true;
    interactionChecked.value = false;
    try {
        const response = await axios.post(`/api/patients/${route.params.id}/emr/check-drug-interactions`, {
            drug_name: drugToCheck.value
        });
        interactionResults.value = response.data.data || [];
        interactionChecked.value = true;
    } catch (error) {
        console.error('Failed to check drug interactions:', error);
    }
    checkingInteractions.value = false;
};

// Watch for tab changes
watch(activeTab, (newTab) => {
    if (newTab === 'chronic') fetchChronicDiseases();
    else if (newTab === 'medications') fetchMedications();
    else if (newTab === 'immunizations') fetchImmunizations();
    else if (newTab === 'family') fetchFamilyDiseases();
    else if (newTab === 'clinical') fetchClinicalData();
    else if (newTab === 'trends') fetchVitalTrends();
});

watch(trendDays, () => {
    fetchVitalTrends();
});

onMounted(() => {
    fetchClinicalProfile();
    fetchOptions();
    fetchChronicDiseases();
});
</script>

<style scoped>
.trend-chart {
    max-height: 300px;
    overflow-y: auto;
}

.trend-item {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    padding: 4px 0;
    border-bottom: 1px solid #eee;
}

.trend-item .date {
    width: 60px;
    font-size: 12px;
    color: #666;
}

.trend-item .value {
    width: 80px;
    font-weight: 500;
    font-size: 13px;
}

.trend-item .v-progress-linear {
    flex: 1;
}
</style>
