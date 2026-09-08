/**
 * Modules/BulkIdeationPromptStudio/resources/assets/js/bulk-ideation-prompt-studio.js
 * ─────────────────────────────────────────────────────────────────────
 * Entry point JS của module BulkIdeationPromptStudio.
 * Build: vite.config.backend.js → public/build/backend/assets/modules/bulk-ideation-prompt-studio.[hash].js
 *
 * Blade:
 *   @push('scripts')
 *       @vite(['Modules/BulkIdeationPromptStudio/resources/assets/js/bulk-ideation-prompt-studio.js'], 'build/backend')
 *   @endpush
 *
 * @shared/* → alias trong vite.config.backend.js → resources/js/shared/
 * window.*  → globals từ core bundle (app.js): Alpine, $, initFormValidation, TomSelect
 * ─────────────────────────────────────────────────────────────────────
 */

import './pages/ideation-form.js';
