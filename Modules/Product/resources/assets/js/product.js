/**
 * Modules/Product/resources/assets/js/product.js
 * ─────────────────────────────────────────────────────────────────────
 * Entry point JS của module Product.
 * Build: vite.config.backend.js → public/build/backend/assets/modules/product.[hash].js
 *
 * Blade:
 *   @push('scripts')
 *       @vite(['Modules/Product/resources/assets/js/product.js'], 'build/backend')
 *   @endpush
 *
 * @shared/* → alias trong vite.config.backend.js → resources/js/shared/
 * window.*  → globals từ core bundle (app.js): Alpine, $, initFormValidation, TomSelect
 * ─────────────────────────────────────────────────────────────────────
 */

import './pages/product-form.js';
import './pages/category-form.js';
