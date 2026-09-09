/**
 * assets/js/script.js
 * -----------------------------------------------------------
 * Client-side behaviour for the Student Performance Predictor.
 * IMPORTANT: this file only improves user experience. All real
 * validation and security checks are repeated on the server in PHP.
 * -----------------------------------------------------------
 */

document.addEventListener('DOMContentLoaded', function () {
    initSidebarToggle();
    initAutoDismissAlerts();
    initDeleteConfirmations();
    initPredictFormValidation();
    initStudentFormValidation();
});

/* ---------------- Mobile sidebar toggle ---------------- */
function initSidebarToggle() {
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (!toggleBtn || !sidebar || !overlay) return;

    function openSidebar() {
        sidebar.classList.add('open');
        overlay.classList.add('show');
    }
    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
    }

    toggleBtn.addEventListener('click', function () {
        sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
    });
    overlay.addEventListener('click', closeSidebar);
}

/* ---------------- Auto-dismiss flash alerts ---------------- */
function initAutoDismissAlerts() {
    const alertBox = document.getElementById('flashAlert');
    if (!alertBox) return;
    setTimeout(function () {
        alertBox.style.transition = 'opacity 0.4s ease';
        alertBox.style.opacity = '0';
        setTimeout(function () { alertBox.remove(); }, 400);
    }, 4000);
}

/* ---------------- Delete confirmation ---------------- */
function initDeleteConfirmations() {
    document.querySelectorAll('.js-confirm-delete').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            const name = form.getAttribute('data-name') || 'this record';
            const ok = confirm('Are you sure you want to delete ' + name + '? This cannot be undone.');
            if (!ok) {
                e.preventDefault();
            }
        });
    });
}

/* ---------------- Predict form validation ---------------- */
function initPredictFormValidation() {
    const form = document.getElementById('predictForm');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        let valid = true;
        const fields = [
            { id: 'student_id', min: null, max: null, label: 'Student' },
            { id: 'semester', min: 1, max: 12, label: 'Semester' },
            { id: 'study_hours_per_day', min: 0, max: 24, label: 'Study hours' },
            { id: 'attendance', min: 0, max: 100, label: 'Attendance' },
            { id: 'previous_marks', min: 0, max: 100, label: 'Previous marks' },
            { id: 'assignment_marks', min: 0, max: 100, label: 'Assignment marks' },
            { id: 'internal_marks', min: 0, max: 100, label: 'Internal marks' },
        ];

        fields.forEach(function (f) {
            const input = document.getElementById(f.id);
            const errorEl = document.getElementById(f.id + '_error');
            if (!input) return;

            let message = '';
            const value = input.value.trim();

            if (value === '') {
                message = f.label + ' is required.';
            } else if (f.min !== null && f.max !== null) {
                const num = parseFloat(value);
                if (isNaN(num) || num < f.min || num > f.max) {
                    message = f.label + ' must be between ' + f.min + ' and ' + f.max + '.';
                }
            }

            if (errorEl) errorEl.textContent = message;
            if (message) valid = false;
        });

        if (!valid) {
            e.preventDefault();
        }
    });

    // Live preview of the prediction as the user types (optional UX helper)
    const previewEl = document.getElementById('livePreview');
    if (previewEl) {
        const watchIds = ['study_hours_per_day', 'attendance', 'previous_marks', 'assignment_marks', 'internal_marks'];
        watchIds.forEach(function (id) {
            const el = document.getElementById(id);
            if (el) el.addEventListener('input', updateLivePreview);
        });
    }
}

function updateLivePreview() {
    const get = function (id) {
        const el = document.getElementById(id);
        const v = parseFloat(el ? el.value : '');
        return isNaN(v) ? 0 : v;
    };

    let studyScore = (get('study_hours_per_day') / 10) * 100;
    if (studyScore > 100) studyScore = 100;

    const prediction =
        (studyScore * 0.20) +
        (get('attendance') * 0.20) +
        (get('previous_marks') * 0.30) +
        (get('assignment_marks') * 0.15) +
        (get('internal_marks') * 0.15);

    const clamped = Math.max(0, Math.min(100, prediction));
    const previewEl = document.getElementById('livePreview');
    if (previewEl) {
        previewEl.textContent = clamped.toFixed(2) + ' / 100 (approximate)';
    }
}

/* ---------------- Add/Edit student form validation ---------------- */
function initStudentFormValidation() {
    const form = document.getElementById('studentForm');
    if (!form) return;

    form.addEventListener('submit', function (e) {
        let valid = true;

        const nameInput = document.getElementById('name');
        const nameError = document.getElementById('name_error');
        if (nameInput && nameInput.value.trim().length < 2) {
            if (nameError) nameError.textContent = 'Name must be at least 2 characters.';
            valid = false;
        } else if (nameError) {
            nameError.textContent = '';
        }

        const emailInput = document.getElementById('email');
        const emailError = document.getElementById('email_error');
        if (emailInput && emailInput.value.trim() !== '') {
            const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!pattern.test(emailInput.value.trim())) {
                if (emailError) emailError.textContent = 'Enter a valid email address.';
                valid = false;
            } else if (emailError) {
                emailError.textContent = '';
            }
        } else if (emailError) {
            emailError.textContent = '';
        }

        const semesterInput = document.getElementById('semester');
        const semesterError = document.getElementById('semester_error');
        if (semesterInput) {
            const sem = parseInt(semesterInput.value, 10);
            if (isNaN(sem) || sem < 1 || sem > 12) {
                if (semesterError) semesterError.textContent = 'Semester must be between 1 and 12.';
                valid = false;
            } else if (semesterError) {
                semesterError.textContent = '';
            }
        }

        if (!valid) {
            e.preventDefault();
        }
    });
}
