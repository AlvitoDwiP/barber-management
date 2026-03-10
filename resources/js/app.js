import './bootstrap';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

const initDatePickers = () => {
    const dateInputs = document.querySelectorAll('[data-flatpickr="date"]');

    dateInputs.forEach((input) => {
        if (input.dataset.fpInitialized === 'true') {
            return;
        }

        flatpickr(input, {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd M Y',
            allowInput: false,
        });

        input.dataset.fpInitialized = 'true';
    });
};

document.addEventListener('DOMContentLoaded', initDatePickers);
