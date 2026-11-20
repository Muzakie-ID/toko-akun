import { createInertiaApp } from '@inertiajs/vue3'
import { createApp, h } from 'vue'
// Build offline: ikon dari bundle (lihat plugin offlineIcons di vite.config.js), tidak memanggil API Iconify
import { Icon, addCollection } from '@iconify/vue/offline'
import iconCollections from 'virtual:icons'
import '../css/app.css'

iconCollections.forEach((collection) => addCollection(collection))

createInertiaApp({
    title: (title) => (title ? `${title} - Akunhaus` : 'Akunhaus'),
    resolve: (name) =>
        import.meta.glob('./Pages/**/*.vue', { eager: true })[`./Pages/${name}.vue`],
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .component('Icon', Icon)
            .mount(el)
    },
    progress: { color: '#000000', showSpinner: false },
})

