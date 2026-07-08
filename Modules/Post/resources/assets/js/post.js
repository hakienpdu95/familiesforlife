/**
 * Modules/Post/resources/assets/js/post.js
 * Entry point JS module Post.
 * Build: vite.config.backend.js → public/build/backend/assets/modules/post.[hash].js
 *
 * Blade:
 *   @push('scripts')
 *       @vite(['resources/js/modules/toastify.js',
 *              'resources/js/modules/tom-select.js',
 *              'resources/js/modules/jodit.js',
 *              'Modules/Post/resources/assets/js/post.js'], 'build/backend')
 *   @endpush
 */
import './pages/post-block-composer.js';
import './pages/article-form.js';
import './pages/category-form.js';
