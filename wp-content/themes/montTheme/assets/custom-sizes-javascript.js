function toggleSection(header) {
    const group = header.closest('.mont_variation-group');
    const list = group.querySelector('.mont_option-list');
    const icon = header.querySelector('.mont_toggle-icon');
    const isSizeSection = group.classList.contains('pa_size'); // Check if it's the size section
    const passformChecked = document.querySelectorAll('.pa_body-fit-checkbox:checked').length > 0; // Check if any passform checkbox is checked

    // Prevent opening size section if no checkbox is checked in passform section
    if (isSizeSection && !passformChecked) {
        return;
    }

    list.classList.toggle('mont_open');
    group.classList.toggle('mont_open');

    if (list.classList.contains('mont_open')) {
        icon.innerHTML = '<i data-lucide="chevron-up"></i>';
    } else {
        icon.innerHTML = '<i data-lucide="chevron-down"></i>';
    }

    // Re-initialize Lucide icons after replacing the HTML
    if (typeof lucide !== 'undefined' && lucide.createIcons) {
        lucide.createIcons();
    }
}

function closeAlert() {
    var alertEl = document.getElementById('mont_alert');
    var backdrop = document.getElementById('mont_alert_backdrop');
    if (alertEl) {
        alertEl.classList.add('mont_hidden');
        alertEl.classList.remove('is-open');
        alertEl.setAttribute('hidden', 'hidden');
        alertEl.style.top = '';
        alertEl.style.left = '';
        alertEl.style.right = '';
    }
    if (backdrop) {
        backdrop.classList.remove('is-open');
        backdrop.setAttribute('hidden', 'hidden');
    }
    document.body.classList.remove('mont-alert-open');
}

/**
 * Show custom-options notice next to an anchor (desktop popover / mobile sheet).
 * @param {Element|jQuery|null} anchorEl
 */
function showMontCustomAlert(anchorEl) {
    var alertEl = document.getElementById('mont_alert');
    var backdrop = document.getElementById('mont_alert_backdrop');
    if (!alertEl) return;

    var el = anchorEl && anchorEl.jquery ? anchorEl.get(0) : anchorEl;
    alertEl.classList.remove('mont_hidden');
    alertEl.removeAttribute('hidden');
    alertEl.classList.add('is-open');
    alertEl.style.display = '';

    var isDesktop = window.matchMedia('(min-width: 1025px)').matches;

    if (isDesktop && el && el.getBoundingClientRect) {
        var rect = el.getBoundingClientRect();
        var scrollY = window.pageYOffset || document.documentElement.scrollTop;
        var scrollX = window.pageXOffset || document.documentElement.scrollLeft;
        var popW = Math.min(420, window.innerWidth - 48);
        var left = rect.right + scrollX + 14;
        var top = rect.top + scrollY - 8;

        // Prefer right of anchor; if overflow, place to the left.
        if (left + popW > scrollX + window.innerWidth - 16) {
            left = Math.max(16 + scrollX, rect.left + scrollX - popW - 14);
        }
        // Keep within viewport vertically (approx).
        var maxTop = scrollY + window.innerHeight - 280;
        if (top > maxTop) top = Math.max(scrollY + 16, maxTop);

        alertEl.style.position = 'absolute';
        alertEl.style.top = Math.round(top) + 'px';
        alertEl.style.left = Math.round(left) + 'px';
        alertEl.style.right = 'auto';

        if (backdrop) {
            backdrop.classList.remove('is-open');
            backdrop.setAttribute('hidden', 'hidden');
        }
        document.body.classList.remove('mont-alert-open');

        // Move popover to body so absolute position is page-relative.
        if (alertEl.parentElement !== document.body) {
            document.body.appendChild(alertEl);
        }
    } else {
        alertEl.style.position = '';
        alertEl.style.top = '';
        alertEl.style.left = '';
        alertEl.style.right = '';
        if (backdrop) {
            backdrop.removeAttribute('hidden');
            backdrop.classList.add('is-open');
        }
        document.body.classList.add('mont-alert-open');
    }
}

// Backdrop tap closes on mobile
document.addEventListener('click', function (e) {
    if (e.target && e.target.id === 'mont_alert_backdrop') {
        closeAlert();
    }
});
