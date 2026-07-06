/**
 * Modules/AiCopilot/resources/assets/js/ai-copilot.js
 * Entry point JS module AiCopilot.
 * Build: vite.config.backend.js → public/build/backend/assets/modules/ai-copilot.[hash].js
 *
 * Blade:
 *   @push('scripts')
 *       @vite(['Modules/AiCopilot/resources/assets/js/ai-copilot.js'], 'build/backend')
 *   @endpush
 *
 * Globals từ core bundle (không cần import):
 *   window.Alpine, window.$, window.initFormValidation
 *   window.TomSelect, window.initTomSelect (tom-select.js)
 *   window.Toast (toastify.js)
 */

import './pages/ai-agent-form.js';
import './pages/ai-prompt-form.js';
