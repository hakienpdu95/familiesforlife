/**
 * Modules/Ocop/resources/assets/js/ocop.js
 * Entry point JS module Ocop.
 * Build: vite.config.backend.js → public/build/backend/assets/modules/ocop.[hash].js
 *
 * Blade:
 *   @push('scripts')
 *       @vite(['Modules/Ocop/resources/assets/js/ocop.js'], 'build/backend')
 *   @endpush
 *
 * Globals từ core bundle (không cần import):
 *   window.Alpine, window.$, window.initFormValidation
 *   window.TomSelect, window.initTomSelect, window.initOrgAddress
 */

import './pages/ocop-product-form.js';
import './pages/ocop-product-index.js';
