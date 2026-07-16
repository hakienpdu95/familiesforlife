/**
 * vite.config.backend.js
 *
 * Laravel 13 | Vite 8 | Tailwind 4 | DaisyUI 5 | Alpine 3
 * ─────────────────────────────────────────────────────────────────────
 *
 * LỆNH:
 *   npm run dev    → vite --config vite.config.backend.js
 *   npm run build  → vite build --config vite.config.backend.js
 *
 * BLADE:
 *   Core  : @vite(['resources/css/app.css', 'resources/js/app.js'], 'build/backend')
 *   Widget: @vite(['resources/js/modules/tom-select.js'], 'build/backend')
 *   Module: @vite(['Modules/Lead/resources/assets/sass/lead.scss',
 *                  'Modules/Lead/resources/assets/js/lead.js'], 'build/backend')
 */

import { defineConfig } from 'vite';
import laravel          from 'laravel-vite-plugin';
import tailwindcss      from '@tailwindcss/vite';
import path             from 'path';

// ─── Output file name maps ────────────────────────────────────────────

/** JS entry chunks → output path */
const JS_OUTPUT = {
  // Core
  'app':        'assets/app.[hash].js',
  // Widget libs
  'echarts':    'assets/echarts.[hash].js',
  'toastify':   'assets/toastify.[hash].js',
  'tabulator':  'assets/tabulator.[hash].js',
  'filepond':   'assets/filepond.[hash].js',
  'flatpickr':  'assets/flatpickr.[hash].js',
  'jodit':      'assets/jodit.[hash].js',
  'tom-select': 'assets/tom-select.[hash].js',
  'swiper':     'assets/swiper.[hash].js',
  'qrcode':     'assets/qrcode.[hash].js',
  // Module JS — named [module] to avoid chunk name collision
  'lead':                 'assets/modules/lead.[hash].js',
  'user':                 'assets/modules/user.[hash].js',
  'organization':         'assets/modules/organization.[hash].js',
  'lead-pipeline-stage':  'assets/modules/lead-pipeline-stage.[hash].js',
  'lead-source':          'assets/modules/lead-source.[hash].js',
  'survey':               'assets/modules/survey.[hash].js',
  'assessment':           'assets/modules/assessment.[hash].js',
  'activity-log':           'assets/modules/activity-log.[hash].js',
  'workflow-automation':    'assets/modules/workflow-automation.[hash].js',
  'subscription':           'assets/modules/subscription.[hash].js',
  'report':                 'assets/modules/report.[hash].js',
  'post':                   'assets/modules/post.[hash].js',
  'product':                'assets/modules/product.[hash].js',
  'event':                  'assets/modules/event.[hash].js',
  'menu':                   'assets/modules/menu.[hash].js',
  'ocop':                   'assets/modules/ocop.[hash].js',
};

/** CSS asset name → output path.
 *  Key = original filename (Vite uses source filename as asset.name).
 *  SCSS entries compile to CSS; Vite uses the SCSS filename as asset.name.
 */
const CSS_OUTPUT = {
  // Core
  'app.css': 'assets/app.[hash].css',
  // Widget libs CSS
  'filepond.css':                  'assets/filepond.[hash].css',
  'flatpickr.css':                 'assets/flatpickr.[hash].css',
  'jodit.min.css':                 'assets/jodit.[hash].css',
  'tom-select.css':                'assets/tom-select.[hash].css',
  'swiper.css':                    'assets/swiper.[hash].css',
  // Module SCSS → CSS
  'lead.css':                 'assets/modules/lead.[hash].css',
  'user.css':                 'assets/modules/user.[hash].css',
  'organization.css':         'assets/modules/organization.[hash].css',
  'lead-pipeline-stage.css':  'assets/modules/lead-pipeline-stage.[hash].css',
  'lead-source.css':          'assets/modules/lead-source.[hash].css',
  'survey.css':               'assets/modules/survey.[hash].css',
  'assessment.css':           'assets/modules/assessment.[hash].css',
  'activity-log.css':           'assets/modules/activity-log.[hash].css',
  'workflow-automation.css':    'assets/modules/workflow-automation.[hash].css',
  'subscription.css':           'assets/modules/subscription.[hash].css',
  'ai-copilot.css':             'assets/modules/ai-copilot.[hash].css',
  'report.css':                 'assets/modules/report.[hash].css',
  'post.css':                   'assets/modules/post.[hash].css',
  'product.css':                'assets/modules/product.[hash].css',
  'event.css':                  'assets/modules/event.[hash].css',
  'menu.css':                   'assets/modules/menu.[hash].css',
  'ocop.css':                   'assets/modules/ocop.[hash].css',
};

// ─── Module input entries ─────────────────────────────────────────────
// Thêm module mới vào đây khi module có views cần custom SCSS hoặc JS.
// Quy tắc đặt tên: [module-name].scss / [module-name].js (không phải app.scss/app.js)
// để tránh chunk name collision trong rollup output.

const MODULE_ENTRIES = [
  // Lead
  'Modules/Lead/resources/assets/sass/lead.scss',
  'Modules/Lead/resources/assets/js/lead.js',
  // User
  'Modules/User/resources/assets/sass/user.scss',
  'Modules/User/resources/assets/js/user.js',
  // Organization
  'Modules/Organization/resources/assets/sass/organization.scss',
  'Modules/Organization/resources/assets/js/organization.js',
  // LeadPipelineStage
  'Modules/LeadPipelineStage/resources/assets/sass/lead-pipeline-stage.scss',
  'Modules/LeadPipelineStage/resources/assets/js/lead-pipeline-stage.js',
  // LeadSource
  'Modules/LeadSource/resources/assets/sass/lead-source.scss',
  'Modules/LeadSource/resources/assets/js/lead-source.js',
  // Survey
  'Modules/Survey/resources/assets/sass/survey.scss',
  'Modules/Survey/resources/assets/js/survey.js',
  // Assessment
  'Modules/Assessment/resources/assets/sass/assessment.scss',
  'Modules/Assessment/resources/assets/js/assessment.js',
  // ActivityLog
  'Modules/ActivityLog/resources/assets/sass/activity-log.scss',
  'Modules/ActivityLog/resources/assets/js/activity-log.js',
  // WorkflowAutomation
  'Modules/WorkflowAutomation/resources/assets/sass/workflow-automation.scss',
  'Modules/WorkflowAutomation/resources/assets/js/workflow-automation.js',
  // Subscription
  'Modules/Subscription/resources/assets/sass/subscription.scss',
  'Modules/Subscription/resources/assets/js/subscription.js',
  // Customer
  'Modules/Customer/resources/assets/sass/customer.scss',
  'Modules/Customer/resources/assets/js/customer.js',
  // Report
  'Modules/Report/resources/assets/sass/report.scss',
  'Modules/Report/resources/assets/js/report.js',
  // Post
  'Modules/Post/resources/assets/sass/post.scss',
  'Modules/Post/resources/assets/js/post.js',
  // Product
  'Modules/Product/resources/assets/sass/product.scss',
  'Modules/Product/resources/assets/js/product.js',
  // Event
  'Modules/Event/resources/assets/sass/event.scss',
  'Modules/Event/resources/assets/js/event.js',
  // Menu
  'Modules/Menu/resources/assets/sass/menu.scss',
  'Modules/Menu/resources/assets/js/menu.js',
  // Banner
  'Modules/Banner/resources/assets/js/banner.js',
  // Ocop
  'Modules/Ocop/resources/assets/sass/ocop.scss',
  'Modules/Ocop/resources/assets/js/ocop.js',
];

// ─────────────────────────────────────────────────────────────────────

export default defineConfig(({ mode }) => {
  const isProd = mode === 'production';

  return {

    /* ── Base URL ────────────────────────────────────────────────── */
    base: isProd ? '/build/backend/' : '/',

    /* ── Plugins ─────────────────────────────────────────────────── */
    plugins: [
      // Tailwind CSS v4 — đặt TRƯỚC laravel()
      tailwindcss(),

      laravel({
        input: [
          /* ── CORE ─────────────────────────────────────────────── */
          'resources/css/app.css',
          'resources/js/app.js',

          /* ── WIDGET LIBS (lazy per-page) ──────────────────────── */
          'resources/js/modules/echarts.js',
          'resources/js/modules/toastify.js',
          'resources/js/modules/tabulator.js',
          'resources/js/modules/filepond.js',
          'resources/js/modules/flatpickr.js',
          'resources/js/modules/jodit.js',
          'resources/js/modules/tom-select.js',
          'resources/js/modules/swiper.js',
          'resources/js/modules/qrcode.js',

          /* ── MODULE ASSETS (lazy per-page) ────────────────────── */
          ...MODULE_ENTRIES,
        ],

        refresh: [
          /* Core views */
          'resources/views/**/*.blade.php',
          'resources/css/**/*.css',
          'resources/scss/**/*.scss',      // shared SCSS partials
          'resources/js/**/*.js',
          /* Module views + assets */
          'Modules/*/resources/views/**/*.blade.php',
          'Modules/*/resources/assets/sass/**/*.scss',
          'Modules/*/resources/assets/js/**/*.js',
          /* Routes */
          'routes/**/*.php',
          'Modules/*/routes/**/*.php',
        ],

        buildDirectory: 'build/backend',
        modulePreload:  { polyfill: true },
      }),

      // Jodit side-effects — tắt tree-shaking
      {
        name: 'no-treeshake-jodit',
        transform(_code, id) {
          if (id.includes('node_modules/jodit')) {
            return { moduleSideEffects: 'no-treeshake' };
          }
        },
      },
    ],

    /* ── Resolve aliases ─────────────────────────────────────────── */
    resolve: {
      alias: {
        '@':        path.resolve(__dirname, 'resources'),
        '@css':     path.resolve(__dirname, 'resources/css'),
        '@js':      path.resolve(__dirname, 'resources/js'),
        '@modules': path.resolve(__dirname, 'resources/js/modules'),
        '@shared':  path.resolve(__dirname, 'resources/js/shared'),   // shared JS utils
        '@fonts':   path.resolve(__dirname, 'resources/webfonts'),
      },
    },

    /* ── CSS / SCSS preprocessor ─────────────────────────────────── */
    css: {
      preprocessorOptions: {
        scss: {
          /*
           * loadPaths: cho phép module SCSS dùng @use 'tokens', @use 'mixins'...
           * mà không cần đường dẫn tương đối dài.
           *
           * Thứ tự resolve: Vite thử loadPaths theo thứ tự này:
           *  1. resources/scss/         → shared partials (_tokens, _mixins...)
           *  2. [module]/resources/assets/sass/  → được tự động thêm bởi Vite
           *                                        khi xử lý file trong thư mục đó
           *
           * Dùng trong module SCSS:
           *   @use 'tokens' as t;          → resources/scss/_tokens.scss
           *   @use 'form-patterns';        → resources/scss/_form-patterns.scss
           *   @use 'tom-select';           → resources/scss/_tom-select.scss
           *   @use 'lead-components' as l; → [module]/sass/_lead-components.scss
           */
          loadPaths: [
            path.resolve(__dirname, 'resources/scss'),
          ],
          /*
           * Modern Sass API — tránh legacy deprecation warnings từ sass 1.77+
           * Nếu gặp lỗi với sass cũ hơn, bỏ dòng này.
           */
          api: 'modern',
        },
      },
    },

    /* ── Build ───────────────────────────────────────────────────── */
    build: {
      outDir:               'public/build/backend',
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

          /* JS entry file names */
          entryFileNames: (chunk) =>
            JS_OUTPUT[chunk.name] ?? `assets/${chunk.name}.[hash].js`,

          /* Shared chunk names */
          chunkFileNames: (chunk) => {
            const map = {
              'shared-utils':      'assets/shared-utils.[hash].js',   // resources/js/shared/*
              'vendor-jquery':     'assets/vendor-jquery.[hash].js',
              'vendor-alpine':     'assets/vendor-alpine.[hash].js',
              'vendor-daisyui':    'assets/vendor-daisyui.[hash].js',
              'vendor-iconify':    'assets/vendor-iconify.[hash].js',
              'vendor-jodit':      'assets/vendor-jodit.[hash].js',
              'vendor-swiper':     'assets/vendor-swiper.[hash].js',
              'vendor-tabulator':  'assets/vendor-tabulator.[hash].js',
              'vendor-filepond':   'assets/vendor-filepond.[hash].js',
              'vendor-tom-select': 'assets/vendor-tom-select.[hash].js',
              'vendor-flatpickr':  'assets/vendor-flatpickr.[hash].js',
              'vendor-toastify':   'assets/vendor-toastify.[hash].js',
              'vendor-qrcode':     'assets/vendor-qrcode.[hash].js',
            };
            return map[chunk.name] ?? `assets/chunk-${chunk.name}.[hash].js`;
          },

          /* CSS + font + image names */
          assetFileNames: (asset) => {
            const name = asset.name ?? '';
            if (/\.(woff2?|ttf|eot)$/.test(name))
              return 'assets/fonts/[name].[hash].[ext]';
            if (/\.svg$/.test(name) && /font|icon/i.test(name))
              return 'assets/fonts/[name].[hash].[ext]';
            if (/\.(png|jpe?g|gif|webp|avif|ico)$/.test(name))
              return 'assets/images/[name].[hash].[ext]';
            return CSS_OUTPUT[name] ?? 'assets/[name].[hash].[ext]';
          },

          /*
           * manualChunks: tách vendor + shared utils → cache độc lập.
           * Thay đổi app code không làm re-download vendor chunk.
           */
          manualChunks(id) {
            // Shared JS utilities → 1 chunk chung tái dụng bởi mọi module
            if (id.includes('resources/js/shared/'))         return 'shared-utils';
            // Vendor libs
            if (id.includes('node_modules/alpinejs'))        return 'vendor-alpine';
            if (id.includes('node_modules/jquery'))          return 'vendor-jquery';
            if (id.includes('node_modules/daisyui'))         return 'vendor-daisyui';
            if (id.includes('node_modules/iconify-icon'))    return 'vendor-iconify';
            if (id.includes('node_modules/jodit'))           return 'vendor-jodit';
            if (id.includes('node_modules/swiper'))          return 'vendor-swiper';
            if (id.includes('node_modules/tabulator-tables'))return 'vendor-tabulator';
            if (id.includes('node_modules/filepond'))        return 'vendor-filepond';
            if (id.includes('node_modules/tom-select'))      return 'vendor-tom-select';
            if (id.includes('node_modules/flatpickr'))       return 'vendor-flatpickr';
            if (id.includes('node_modules/toastify-js'))     return 'vendor-toastify';
            if (id.includes('node_modules/qrcode-generator'))return 'vendor-qrcode';
          },
        },
      },
    },

    /* ── Dev server ──────────────────────────────────────────────── */
    server: {
      port:       5174,
      strictPort: true,
      hmr:   { host: 'localhost' },
      watch: { usePolling: false },
    },

    /* ── optimizeDeps (pre-bundle trong dev) ─────────────────────── */
    optimizeDeps: {
      include: [
        'jquery',
        'alpinejs',
        'toastify-js',
        'iconify-icon',
      ],
      exclude: [
        'jodit',
        'swiper',
        'tabulator-tables',
        'filepond',
        'filepond-plugin-image-preview',
        'filepond-plugin-file-validate-size',
        'filepond-plugin-file-rename',
        'filepond-plugin-image-exif-orientation',
        'flatpickr',
        'tom-select',
        'qrcode-generator',
      ],
    },

  };
});
