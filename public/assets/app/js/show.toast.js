// show.toast.js (SweetAlert2 Implementation)
// Replaces custom Bootstrap 5 Toasts with SweetAlert2 Toasts for consistency

(function () {
    'use strict';

    // Initialize SweetAlert2 Toast Mixin
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });

    // Helper wrappers
    function showSwalToast(icon, title) {
        if (typeof Swal === 'undefined') {
            console.error('SweetAlert2 is not loaded!');
            alert(title);
            return;
        }
        Toast.fire({
            icon: icon,
            title: title
        });
    }

    // Global exports for compatibility
    window.showSuccessToast = function (msg) {
        showSwalToast('success', msg);
    };

    window.showInfoToast = function (msg) {
        showSwalToast('info', msg);
    };

    window.showWarningToast = function (msg) {
        showSwalToast('warning', msg);
    };

    window.showDangerToast = function (msg) {
        showSwalToast('error', msg); // SweetAlert uses 'error' icon for danger
    };

    // absensi by ikasmedia Explicit Helper
    window.showCbtWarning = function (msg) {
        showSwalToast('warning', msg);
    };

    // Stub for resetToastPosition (no-op)
    window.resetToastPosition = function () { };

})();
