<template>
    <v-container fluid class="fill-height" :class="isDark ? 'bg-background' : 'bg-grey-lighten-3'">
        <!-- Theme Toggle -->
        <v-btn
            icon
            variant="text"
            @click="toggleTheme"
            class="position-absolute"
            style="top: 16px; right: 16px;"
        >
            <v-icon>{{ isDark ? 'mdi-weather-sunny' : 'mdi-weather-night' }}</v-icon>
            <v-tooltip activator="parent" location="bottom">
                {{ isDark ? 'Light Mode' : 'Dark Mode' }}
            </v-tooltip>
        </v-btn>

        <v-row align="center" justify="center">
            <v-col cols="12" sm="8" md="5" lg="4" xl="3">
                <v-card class="pa-4" elevation="2">
                    <v-card-text class="text-center pb-0">
                        <v-avatar color="primary" size="64" class="mb-4">
                            <v-icon size="36" color="white">mdi-hospital-building</v-icon>
                        </v-avatar>
                        <h1 class="text-h5 font-weight-bold text-primary mb-1">DCMS</h1>
                        <p class="text-body-2 text-grey">Diagnostic Centre Management System</p>
                    </v-card-text>

                    <v-card-text>
                        <v-form @submit.prevent="handleLogin" ref="form">
                            <v-text-field
                                v-model="credentials.email"
                                label="Email"
                                type="email"
                                prepend-inner-icon="mdi-email-outline"
                                :rules="[rules.required, rules.email]"
                                :error-messages="errors.email"
                                class="mb-2"
                            />

                            <v-text-field
                                v-model="credentials.password"
                                label="Password"
                                :type="showPassword ? 'text' : 'password'"
                                prepend-inner-icon="mdi-lock-outline"
                                :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
                                @click:append-inner="showPassword = !showPassword"
                                :rules="[rules.required]"
                                :error-messages="errors.password"
                                class="mb-2"
                            />

                            <v-checkbox
                                v-model="credentials.remember"
                                label="Remember me"
                                color="primary"
                                density="compact"
                                hide-details
                                class="mb-4"
                            />

                            <v-btn
                                type="submit"
                                color="primary"
                                size="large"
                                block
                                :loading="loading"
                            >
                                Login
                            </v-btn>
                        </v-form>
                    </v-card-text>

                    <v-alert
                        v-if="errorMessage"
                        type="error"
                        variant="tonal"
                        class="mx-4 mb-4"
                        closable
                        @click:close="errorMessage = ''"
                    >
                        {{ errorMessage }}
                    </v-alert>
                </v-card>

                <p class="text-center text-caption text-grey mt-4">
                    &copy; {{ new Date().getFullYear() }} Diagnostic Centre Management System
                </p>
            </v-col>
        </v-row>
    </v-container>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useTheme } from 'vuetify';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const authStore = useAuthStore();
const theme = useTheme();

// Theme
const isDark = computed(() => theme.global.name.value === 'dark');
const toggleTheme = () => {
    const newTheme = theme.global.name.value === 'dark' ? 'light' : 'dark';
    theme.global.name.value = newTheme;
    localStorage.setItem('theme', newTheme);
};

const form = ref(null);
const loading = ref(false);
const showPassword = ref(false);
const errorMessage = ref('');

const credentials = reactive({
    email: '',
    password: '',
    remember: false,
});

const errors = reactive({
    email: '',
    password: '',
});

const rules = {
    required: (v) => !!v || 'This field is required',
    email: (v) => /.+@.+\..+/.test(v) || 'Invalid email address',
};

const handleLogin = async () => {
    const { valid } = await form.value.validate();
    if (!valid) return;

    loading.value = true;
    errorMessage.value = '';
    errors.email = '';
    errors.password = '';

    const result = await authStore.login(credentials);

    if (result.success) {
        router.push({ name: 'dashboard' });
    } else {
        errorMessage.value = result.message;
    }

    loading.value = false;
};
</script>
