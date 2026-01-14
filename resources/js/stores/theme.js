import { defineStore } from 'pinia';

export const useThemeStore = defineStore('theme', {
    state: () => ({
        isDark: localStorage.getItem('theme') === 'dark',
    }),

    getters: {
        currentTheme: (state) => (state.isDark ? 'dark' : 'light'),
    },

    actions: {
        toggleTheme() {
            this.isDark = !this.isDark;
            const themeName = this.isDark ? 'dark' : 'light';
            localStorage.setItem('theme', themeName);
        },

        setTheme(isDark) {
            this.isDark = isDark;
            const themeName = isDark ? 'dark' : 'light';
            localStorage.setItem('theme', themeName);
        },

        initializeTheme() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme) {
                this.isDark = savedTheme === 'dark';
            }
        },
    },
});
