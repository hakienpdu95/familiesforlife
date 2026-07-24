/**
 * Modules/RealEstate/resources/assets/js/realestate.js
 * Entry point JS module RealEstate.
 * Build: vite.config.backend.js → public/build/backend/assets/modules/realestate.[hash].js
 *
 * Blade:
 *   @push('scripts')
 *       @vite(['Modules/RealEstate/resources/assets/js/realestate.js'], 'build/backend')
 *   @endpush
 *
 * Globals từ core bundle (không cần import):
 *   window.Alpine, window.$, window.initFormValidation
 *   window.TomSelect, window.initOrgAddress
 *   window.Tabulator (khi tabulator.js đã load)
 */

import './pages/listing-form.js';
import './pages/listing-index.js';
