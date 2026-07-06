import './pages/candidate-form.js';
import './pages/interview-form.js';
import './pages/evaluation-form.js';

// Khởi tạo Jodit cho bất kỳ form nào trong module có .jodit-editor
// (application/create, offers/create không có page controller riêng)
document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('.jodit-editor') && typeof initJoditAll === 'function') {
        initJoditAll('.jodit-editor');
    }
});
