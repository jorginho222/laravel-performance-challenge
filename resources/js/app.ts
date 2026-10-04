import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createPinia } from 'pinia';
import { createApp, h, type DefineComponent } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import GuestLayout from '@/layouts/GuestLayout.vue';
import { useToastStore } from '@/stores/toasts';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} · ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    layout: (name) => (name.startsWith('Identity/') ? GuestLayout : AppLayout),
    setup({ el, App, props, plugin }) {
        const pinia = createPinia();

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(pinia)
            .mount(el);

        // Server-side notifications (Inertia::flash('toast', ...)) become toasts.
        const toasts = useToastStore(pinia);
        router.on('flash', (event) => {
            if (event.detail.flash.toast) {
                toasts.push(event.detail.flash.toast);
            }
        });
    },
    progress: { color: '#4f46e5' },
});
