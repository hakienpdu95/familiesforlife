/**
 * Modules/Post/resources/assets/js/post.js
 * Entry point JS module Post.
 * Build: vite.config.backend.js → public/build/backend/assets/modules/post.[hash].js
 *
 * Blade:
 *   @push('scripts')
 *       @vite(['resources/js/modules/jodit.js',
 *              'Modules/Post/resources/assets/js/post.js'], 'build/backend')
 *   @endpush
 */
import './pages/post-block-composer.js';
