import { ref } from 'vue';

type Theme = 'light' | 'dark' | 'system';

const theme = ref<Theme>('system');

export function useTheme() {
    const applyTheme = (t: Theme) => {
        const root = document.documentElement;
        const isDark =
            t === 'dark' ||
            (t === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);

        if (isDark) {
            root.classList.add('dark');
        } else {
            root.classList.remove('dark');
        }
    };

    const setTheme = (newTheme: Theme) => {
        theme.value = newTheme;
        localStorage.setItem('theme', newTheme);
        applyTheme(newTheme);
    };

    const initTheme = () => {
        const savedTheme = localStorage.getItem('theme') as Theme | null;
        if (savedTheme && ['light', 'dark', 'system'].includes(savedTheme)) {
            theme.value = savedTheme;
        } else {
            theme.value = 'system';
        }
        applyTheme(theme.value);

        // Listen for system changes if in system mode
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            if (theme.value === 'system') {
                applyTheme('system');
            }
        });
    };

    return {
        theme,
        setTheme,
        initTheme,
    };
}
