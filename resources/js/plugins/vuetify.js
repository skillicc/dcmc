import 'vuetify/styles';
import '@mdi/font/css/materialdesignicons.css';
import { createVuetify } from 'vuetify';
import * as components from 'vuetify/components';
import * as directives from 'vuetify/directives';

// Get saved theme from localStorage or default to 'light'
const savedTheme = localStorage.getItem('theme') || 'light';

export default createVuetify({
    components,
    directives,
    theme: {
        defaultTheme: savedTheme,
        themes: {
            light: {
                dark: false,
                colors: {
                    primary: '#00897B',
                    secondary: '#26A69A',
                    accent: '#4DB6AC',
                    success: '#4CAF50',
                    warning: '#FF9800',
                    error: '#F44336',
                    info: '#2196F3',
                    background: '#F5F5F5',
                    surface: '#FFFFFF',
                },
            },
            dark: {
                dark: true,
                colors: {
                    primary: '#26A69A',
                    secondary: '#4DB6AC',
                    accent: '#80CBC4',
                    success: '#66BB6A',
                    warning: '#FFA726',
                    error: '#EF5350',
                    info: '#42A5F5',
                    background: '#121212',
                    surface: '#1E1E1E',
                },
            },
        },
    },
    defaults: {
        VBtn: {
            rounded: 'lg',
            elevation: 0,
        },
        VCard: {
            rounded: 'lg',
            elevation: 1,
        },
        VTextField: {
            variant: 'outlined',
            density: 'comfortable',
        },
        VSelect: {
            variant: 'outlined',
            density: 'comfortable',
        },
        VDataTable: {
            hover: true,
        },
    },
});
