/**
 * Modules/LeadSource/resources/assets/js/lead-source.js
 * ─────────────────────────────────────────────────────────────────────
 * Entry point JS của module LeadSource.
 * Build: vite.config.backend.js → public/build/backend/assets/modules/lead-source.[hash].js
 *
 * Blade:
 *   @push('scripts')
 *       @vite(['Modules/LeadSource/resources/assets/js/lead-source.js'], 'build/backend')
 *   @endpush
 *
 * @shared/* → alias trong vite.config.backend.js → resources/js/shared/
 * window.*  → globals từ core bundle (app.js): Alpine, $, initFormValidation, TomSelect
 * ─────────────────────────────────────────────────────────────────────
 */

import './pages/source-form.js';
