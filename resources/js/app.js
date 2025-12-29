import './bootstrap';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import ElementPlus from 'element-plus';
import 'element-plus/dist/index.css';
import * as ElementPlusIconsVue from '@element-plus/icons-vue';
import fr from 'element-plus/es/locale/lang/fr';

const appName = import.meta.env.VITE_APP_NAME || 'Hôpital de Ménontin';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });

        app.use(plugin);
        app.use(ElementPlus, {
            locale: fr,
        });

        // Register all Element Plus icons globally
        for (const [key, component] of Object.entries(ElementPlusIconsVue)) {
            app.component(key, component);
        }

        app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
