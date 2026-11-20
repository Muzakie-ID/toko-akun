import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import { readFileSync, readdirSync, statSync } from 'node:fs';
import { join } from 'node:path';
import { getIcons } from '@iconify/utils';
import heroicons from '@iconify-json/heroicons/icons.json' with { type: 'json' };
import logos from '@iconify-json/logos/icons.json' with { type: 'json' };

/**
 * Ikon dibundel offline: kode Vue/JS/PHP dipindai untuk nama "heroicons:..." / "logos:...",
 * lalu hanya ikon itu yang dimasukkan ke modul virtual "virtual:icons". Tanpa panggilan ke API Iconify.
 * Nama ikon yang tidak ada di koleksi membuat build gagal (salah ketik ketahuan lebih awal).
 */
function offlineIcons() {
    const collections = { heroicons, logos };
    const pattern = new RegExp(`\\b(${Object.keys(collections).join('|')}):([a-z0-9]+(?:-[a-z0-9]+)*)`, 'g');
    const roots = ['resources/js', 'app'];
    const virtualId = 'virtual:icons';
    const resolvedId = '\0' + virtualId;

    const files = (dir) =>
        readdirSync(dir).flatMap((name) => {
            const path = join(dir, name);
            return statSync(path).isDirectory() ? files(path) : /\.(vue|js|php)$/.test(name) ? [path] : [];
        });

    const collect = () => {
        const used = {};
        for (const file of roots.flatMap(files)) {
            for (const [, prefix, name] of readFileSync(file, 'utf8').matchAll(pattern)) {
                (used[prefix] ??= new Set()).add(name);
            }
        }

        return Object.entries(used).map(([prefix, names]) => {
            const subset = getIcons(collections[prefix], [...names]);
            const missing = [...names].filter((name) => !subset?.icons[name] && !subset?.aliases?.[name]);
            if (missing.length) {
                throw new Error(`Ikon tidak ditemukan di koleksi "${prefix}": ${missing.join(', ')}`);
            }
            return subset;
        });
    };

    return {
        name: 'offline-icons',
        resolveId: (id) => (id === virtualId ? resolvedId : null),
        load: (id) => (id === resolvedId ? `export default ${JSON.stringify(collect())}` : null),
        handleHotUpdate({ file, server }) {
            if (/\.(vue|js|php)$/.test(file)) {
                const mod = server.moduleGraph.getModuleById(resolvedId);
                if (mod) server.moduleGraph.invalidateModule(mod);
            }
        },
    };
}

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
        vue(),
        offlineIcons(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
