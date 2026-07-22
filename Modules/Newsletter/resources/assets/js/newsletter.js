/**
 * Modules/Newsletter/resources/assets/js/newsletter.js
 * Entry point JS module Newsletter.
 * Build: vite.config.backend.js → public/build/backend/assets/modules/newsletter.[hash].js
 *
 * Blade:
 *   @push('scripts')
 *       @vite(['Modules/Newsletter/resources/assets/js/newsletter.js'], 'build/backend')
 *   @endpush
 */
import './pages/subscriber-index.js';
import './pages/broadcast-log-index.js';
