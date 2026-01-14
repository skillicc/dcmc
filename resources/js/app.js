import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import vuetify from './plugins/vuetify';
import { useAuthStore } from './stores/auth';

import '../css/app.css';
import './bootstrap';

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);
app.use(vuetify);

// Initialize auth (set token in axios headers if exists in localStorage)
const authStore = useAuthStore();
authStore.initializeAuth();

app.mount('#app');
