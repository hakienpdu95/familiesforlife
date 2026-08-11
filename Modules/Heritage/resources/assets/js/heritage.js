/**
 * Modules/Heritage/resources/assets/js/heritage.js
 * Entry point JS module Heritage.
 * Build: vite.config.backend.js → public/build/backend/assets/modules/heritage.[hash].js
 *
 * Blade:
 *   @push('scripts')
 *       @vite(['Modules/Heritage/resources/assets/js/heritage.js'], 'build/backend')
 *   @endpush
 *
 * Globals từ core bundle (không cần import):
 *   window.Alpine, window.$, window.initFormValidation
 *   window.TomSelect, window.initTomSelect, window.initOrgAddress
 */

import './pages/heritage-site-form.js';
import './pages/heritage-site-index.js';
