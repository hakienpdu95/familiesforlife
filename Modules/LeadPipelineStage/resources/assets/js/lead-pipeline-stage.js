/**
 * Modules/LeadPipelineStage/resources/assets/js/lead-pipeline-stage.js
 * ─────────────────────────────────────────────────────────────────────
 * Entry point JS của module LeadPipelineStage.
 * Build: vite.config.backend.js → public/build/backend/assets/modules/lead-pipeline-stage.[hash].js
 *
 * Blade:
 *   @push('scripts')
 *       @vite(['Modules/LeadPipelineStage/resources/assets/js/lead-pipeline-stage.js'], 'build/backend')
 *   @endpush
 *
 * @shared/* → alias trong vite.config.backend.js → resources/js/shared/
 * window.*  → globals từ core bundle (app.js): Alpine, $, initFormValidation, TomSelect
 * ─────────────────────────────────────────────────────────────────────
 */

import './pages/stage-form.js';
