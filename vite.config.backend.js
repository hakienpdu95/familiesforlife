/**
 * vite.config.backend.js
 *
 * Laravel 13 | Vite 8 | Tailwind 4 | DaisyUI 5 | Alpine 3
 * ─────────────────────────────────────────────────────────────────────
 *
 * CHIẾN LƯỢC BUNDLE
 * ┌────────────────────────────────────────────────────────────────────┐
 * │ CORE  — tải trên MỌI trang backend                                 │
 * │  · app.css  → Tailwind 4 + DaisyUI 5 + admin shell layout          │
 * │  · app.js   → jQuery, Alpine 3, Iconify, admin-shell, form-valid.  │
 * ├────────────────────────────────────────────────────────────────────┤
 * │ WIDGET LIBS  — tải lazy theo trang (@vite trong blade)             │
 * │  · toastify    toast notification  (nhẹ, nhiều trang)              │
 * │  · tabulator   Tabulator v6        (bảng nâng cao)                 │
 * │  · filepond    FilePond + plugins  (trang upload)                  │
 * │  · flatpickr   date/time picker    (form có date)                  │
 * │  · jodit       rich-text editor    (~500 KB, lazy)                 │
 * │  · tom-select  select/autocomplete (form có select nâng cao)       │
 * │  · swiper      carousel/slider                                     │
 * │  · qrcode      QR code generator                                   │
 * ├────────────────────────────────────────────────────────────────────┤
 * │ MODULE ASSETS  — SCSS + JS riêng mỗi module, tải per-page          │
 * │                                                                    │
 * │  SCSS — @use shared partials từ resources/scss/:                   │
 * │    @use 'tokens'        → DaisyUI CSS vars → SCSS vars             │
 * │    @use 'mixins'        → mixin tái dụng                           │
 * │    @use 'form-patterns' → .color-picker-combo, .field-readonly...  │
 * │    @use 'tom-select'    → TomSelect dark/light theme               │
 * │                                                                    │
 * │  JS — import shared utils từ resources/js/shared/:                 │
 * │    import { makeFormController }   from '@shared/form-controller'  │
 * │    import { makeWizardController } from '@shared/wizard-controller'│
 * │    import { createTs }             from '@shared/tom-select-factory'│
 * └────────────────────────────────────────────────────────────────────┘
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
  'branch':                 'assets/modules/branch.[hash].js',
  'department':             'assets/modules/department.[hash].js',
  'job-title':              'assets/modules/job-title.[hash].js',
  'employee':               'assets/modules/employee.[hash].js',
  'performance-review':     'assets/modules/performance-review.[hash].js',
  'project':                'assets/modules/project.[hash].js',
  'org-chart':              'assets/modules/org-chart.[hash].js',
  'role-scope':             'assets/modules/role-scope.[hash].js',
  'kc-category':            'assets/modules/kc-category.[hash].js',
  'kc-item':                'assets/modules/kc-item.[hash].js',
  'sop':                    'assets/modules/sop.[hash].js',
  'job-posting':            'assets/modules/job-posting.[hash].js',
  'leave':                  'assets/modules/leave.[hash].js',
  'kpi-goal':               'assets/modules/kpi-goal.[hash].js',
  'marketplace':            'assets/modules/marketplace.[hash].js',
  'recruitment':            'assets/modules/recruitment.[hash].js',
  'task':                   'assets/modules/task.[hash].js',
  'subscription':           'assets/modules/subscription.[hash].js',
  'ai-copilot':             'assets/modules/ai-copilot.[hash].js',
  'report':                 'assets/modules/report.[hash].js',
  'deployment':             'assets/modules/deployment.[hash].js',
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
  // asset.name là tên sau khi compile: 'lead.css', không phải 'lead.scss'
  'lead.css':                 'assets/modules/lead.[hash].css',
  'user.css':                 'assets/modules/user.[hash].css',
  'organization.css':         'assets/modules/organization.[hash].css',
  'lead-pipeline-stage.css':  'assets/modules/lead-pipeline-stage.[hash].css',
  'lead-source.css':          'assets/modules/lead-source.[hash].css',
  'survey.css':               'assets/modules/survey.[hash].css',
  'assessment.css':           'assets/modules/assessment.[hash].css',
  'activity-log.css':           'assets/modules/activity-log.[hash].css',
  'workflow-automation.css':    'assets/modules/workflow-automation.[hash].css',
  'branch.css':                 'assets/modules/branch.[hash].css',
  'department.css':             'assets/modules/department.[hash].css',
  'job-title.css':              'assets/modules/job-title.[hash].css',
  'employee.css':               'assets/modules/employee.[hash].css',
  'performance-review.css':     'assets/modules/performance-review.[hash].css',
  'project.css':                'assets/modules/project.[hash].css',
  'org-chart.css':              'assets/modules/org-chart.[hash].css',
  'role-scope.css':             'assets/modules/role-scope.[hash].css',
  'kc-category.css':            'assets/modules/kc-category.[hash].css',
  'kc-item.css':                'assets/modules/kc-item.[hash].css',
  'sop.css':                    'assets/modules/sop.[hash].css',
  'job-posting.css':            'assets/modules/job-posting.[hash].css',
  'leave.css':                  'assets/modules/leave.[hash].css',
  'kpi-goal.css':               'assets/modules/kpi-goal.[hash].css',
  'marketplace.css':            'assets/modules/marketplace.[hash].css',
  'recruitment.css':            'assets/modules/recruitment.[hash].css',
  'task.css':                   'assets/modules/task.[hash].css',
  'subscription.css':           'assets/modules/subscription.[hash].css',
  'ai-copilot.css':             'assets/modules/ai-copilot.[hash].css',
  'report.css':                 'assets/modules/report.[hash].css',
  'deployment.css':             'assets/modules/deployment.[hash].css',
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
  // Branch
  'Modules/Branch/resources/assets/sass/branch.scss',
  'Modules/Branch/resources/assets/js/branch.js',
  // Department
  'Modules/Department/resources/assets/sass/department.scss',
  'Modules/Department/resources/assets/js/department.js',
  // JobTitle
  'Modules/JobTitle/resources/assets/sass/job-title.scss',
  'Modules/JobTitle/resources/assets/js/job-title.js',
  // Employee
  'Modules/Employee/resources/assets/sass/employee.scss',
  'Modules/Employee/resources/assets/js/employee.js',
  // PerformanceReview
  'Modules/PerformanceReview/resources/assets/sass/performance-review.scss',
  'Modules/PerformanceReview/resources/assets/js/performance-review.js',
  // Project
  'Modules/Project/resources/assets/sass/project.scss',
  'Modules/Project/resources/assets/js/project.js',
  // OrgChart
  'Modules/OrgChart/resources/assets/sass/org-chart.scss',
  'Modules/OrgChart/resources/assets/js/org-chart.js',
  // RoleScope
  'Modules/RoleScope/resources/assets/sass/role-scope.scss',
  'Modules/RoleScope/resources/assets/js/role-scope.js',
  // KcCategory
  'Modules/KcCategory/resources/assets/sass/kc-category.scss',
  'Modules/KcCategory/resources/assets/js/kc-category.js',
  // KcItem
  'Modules/KcItem/resources/assets/sass/kc-item.scss',
  'Modules/KcItem/resources/assets/js/kc-item.js',
  // Sop
  'Modules/Sop/resources/assets/sass/sop.scss',
  'Modules/Sop/resources/assets/js/sop.js',
  // JobPosting
  'Modules/JobPosting/resources/assets/sass/job-posting.scss',
  'Modules/JobPosting/resources/assets/js/job-posting.js',
  // Leave
  'Modules/Leave/resources/assets/sass/leave.scss',
  'Modules/Leave/resources/assets/js/leave.js',
  // KpiGoal
  'Modules/KpiGoal/resources/assets/sass/kpi-goal.scss',
  'Modules/KpiGoal/resources/assets/js/kpi-goal.js',
  // Marketplace
  'Modules/Marketplace/resources/assets/sass/marketplace.scss',
  'Modules/Marketplace/resources/assets/js/marketplace.js',
  // Recruitment
  'Modules/Recruitment/resources/assets/sass/recruitment.scss',
  'Modules/Recruitment/resources/assets/js/recruitment.js',
  // Task
  'Modules/Task/resources/assets/sass/task.scss',
  'Modules/Task/resources/assets/js/task.js',
  // Subscription
  'Modules/Subscription/resources/assets/sass/subscription.scss',
  'Modules/Subscription/resources/assets/js/subscription.js',
  // Customer
  'Modules/Customer/resources/assets/sass/customer.scss',
  'Modules/Customer/resources/assets/js/customer.js',
  // AiCopilot
  'Modules/AiCopilot/resources/assets/sass/ai-copilot.scss',
  'Modules/AiCopilot/resources/assets/js/ai-copilot.js',
  // Report
  'Modules/Report/resources/assets/sass/report.scss',
  'Modules/Report/resources/assets/js/report.js',
  // Deployment
  'Modules/Deployment/resources/assets/sass/deployment.scss',
  'Modules/Deployment/resources/assets/js/deployment.js',
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
