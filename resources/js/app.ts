import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { route } from './route-helper';
import { useTheme } from './composables/useTheme';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        // Initialize theme system
        const { initTheme } = useTheme();
        initTheme();

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mixin({
                methods: {
                    route,
                },
            })
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
