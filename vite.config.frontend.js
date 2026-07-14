/**
 * vite.config.frontend.js
 *
 * Laravel 13 | Vite 8 | Tailwind 4 | DaisyUI 5 | Alpine 3
 * ─────────────────────────────────────────────────────────────────────
 * Bundle CỔNG THÔNG TIN công khai (Post::public.* — trang chủ, danh mục,
 * bài viết). Tách khỏi vite.config.backend.js: portal không cần sidebar
 * admin, jQuery, echarts, jodit, tabulator... — bundle riêng nhỏ gọn hơn
 * nhiều, tối ưu cho khách vãng lai (SEO / Core Web Vitals).
 *
 * LỆNH:
 *   npm run dev:frontend    → vite --config vite.config.frontend.js
 *   npm run build:frontend  → vite build --config vite.config.frontend.js
 *
 * BLADE:
 *   @vite(['resources/css/frontend.css', 'resources/js/frontend.js'], 'build/frontend')
 */

import { defineConfig } from 'vite';
import laravel          from 'laravel-vite-plugin';
import tailwindcss      from '@tailwindcss/vite';
import path             from 'path';

export default defineConfig(({ mode }) => {
  const isProd = mode === 'production';

  return {
    base: isProd ? '/build/frontend/' : '/',

    plugins: [
      tailwindcss(),

      laravel({
        input: [
          'resources/css/frontend.css',
          'resources/js/frontend.js',

          // Widget libs — lazy per-page (spec/Event_Management_Technical_Specification.md
          // §10.6: chỉ load ở trang submit-event, không load site-wide).
          'resources/js/modules/tom-select.js',
          'resources/js/modules/flatpickr.js',

          // Event public submission form
          'Modules/Event/resources/assets/js/event-public.js',
        ],

        refresh: [
          'Modules/Post/resources/views/public/**/*.blade.php',
          'Modules/Event/resources/views/public/**/*.blade.php',
          'resources/views/layouts/frontend.blade.php',
          'resources/views/layouts/partials/frontend-*.blade.php',
          'resources/views/components/frontend/**/*.blade.php',
          'resources/css/frontend.css',
          'resources/js/frontend.js',
          'Modules/Post/routes/**/*.php',
          'Modules/Event/routes/**/*.php',
        ],

        buildDirectory: 'build/frontend',
        modulePreload:  { polyfill: true },
      }),
    ],

    resolve: {
      alias: {
        '@':        path.resolve(__dirname, 'resources'),
        '@css':     path.resolve(__dirname, 'resources/css'),
        '@js':      path.resolve(__dirname, 'resources/js'),
        '@shared':  path.resolve(__dirname, 'resources/js/shared'),
      },
    },

    build: {
      outDir:               'public/build/frontend',
      manifest:             'manifest.json',
      emptyOutDir:          true,
      sourcemap:            false,
      reportCompressedSize: true,
      chunkSizeWarningLimit: 500,
      minify:    'oxc',
      cssMinify: 'oxc',
      cssCodeSplit: true,

      rollupOptions: {
        output: {
          entryFileNames: 'assets/[name].[hash].js',
          chunkFileNames: (chunk) =>
            chunk.name === 'vendor-alpine'
              ? 'assets/vendor-alpine.[hash].js'
              : 'assets/chunk-[name].[hash].js',
          assetFileNames: (asset) => {
            const name = asset.name ?? '';
            if (/\.(woff2?|ttf|eot)$/.test(name)) return 'assets/fonts/[name].[hash].[ext]';
            if (/\.(png|jpe?g|gif|webp|avif|ico|svg)$/.test(name)) return 'assets/images/[name].[hash].[ext]';
            return 'assets/[name].[hash].[ext]';
          },
          manualChunks(id) {
            if (id.includes('node_modules/alpinejs')) return 'vendor-alpine';
          },
        },
      },
    },

    server: {
      port:       5175,
      strictPort: true,
      hmr:   { host: 'localhost' },
      watch: { usePolling: false },
    },

    optimizeDeps: {
      include: ['alpinejs'],
    },
  };
});
