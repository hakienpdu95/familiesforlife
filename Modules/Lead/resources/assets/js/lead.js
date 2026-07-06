/**
 * Modules/Lead/resources/assets/js/lead.js
 * ─────────────────────────────────────────────────────────────────────
 * Entry point JS của module Lead.
 * Build: vite.config.backend.js → public/build/backend/assets/modules/lead.[hash].js
 *
 * Blade:
 *   @push('scripts')
 *       @vite(['Modules/Lead/resources/assets/js/lead.js'], 'build/backend')
 *   @endpush
 *
 * @shared/* → alias trong vite.config.backend.js → resources/js/shared/
 * window.*  → globals từ core bundle (app.js): Alpine, $, initFormValidation,
 *             initTomSelect, initOrgAddress, TomSelect, initJoditAll
 * ─────────────────────────────────────────────────────────────────────
 */

import './pages/lead-form.js';
import './pages/lead-index.js';
import './pages/lead-show.js';
