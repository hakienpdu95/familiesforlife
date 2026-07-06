/**
 * Modules/RoleScope/resources/assets/js/role-scope.js
 * Entry point cho module RoleScope.
 */

import './pages/role-scope-form.js';

// Khởi tạo Jodit cho trường note (create + edit)
document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('.jodit-editor') && typeof initJoditAll === 'function') {
        initJoditAll('.jodit-editor');
    }
});
