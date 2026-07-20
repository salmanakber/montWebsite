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
    lucide.createIcons();
}

function closeAlert() {
    document.getElementById('mont_alert').classList.add('mont_hidden');
}