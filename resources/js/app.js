import { createInertiaApp } from '@inertiajs/vue3'
import { createApp, h } from 'vue'
import '../css/app.css'

createInertiaApp({
    title: (title) => (title ? `${title} - Akunhaus` : 'Akunhaus'),
    resolve: (name) =>
        import.meta.glob('./Pages/**/*.vue', { eager: true })[`./Pages/${name}.vue`],
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el)
    },
    progress: { color: '#000000', showSpinner: false },
})

