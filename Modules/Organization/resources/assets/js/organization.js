/**
 * Modules/Organization/resources/assets/js/organization.js
 * Entry point JS module Organization.
 * Build: vite.config.backend.js → public/build/backend/assets/modules/organization.[hash].js
 *
 * Blade:
 *   @push('scripts')
 *       @vite(['Modules/Organization/resources/assets/js/organization.js'], 'build/backend')
 *   @endpush
 *
 * Globals từ core bundle (không cần import):
 *   window.Alpine, window.$, window.initFormValidation
 *   window.TomSelect, window.initTomSelect, window.initOrgAddress
 *   window.initJoditAll (khi jodit.js đã load)
 *   window.initDateRangePicker (khi flatpickr.js đã load)
 *   window.Tabulator (khi tabulator.js đã load)
 */

import './pages/organization-form.js';
import './pages/organization-index.js';
