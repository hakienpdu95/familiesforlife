/**
 * Modules/Event/resources/assets/js/event.js
 * ─────────────────────────────────────────────────────────────────────
 * Entry point JS của module Event.
 * Build: vite.config.backend.js → public/build/backend/assets/modules/event.[hash].js
 *
 * Blade:
 *   @push('scripts')
 *       @vite(['Modules/Event/resources/assets/js/event.js'], 'build/backend')
 *   @endpush
 * ─────────────────────────────────────────────────────────────────────
 */

import './pages/category-form.js';
import './pages/event-form.js';
