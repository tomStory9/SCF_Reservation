import '../styles/member-dashboard.css';

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-booking-dialog-open]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const dialog = document.getElementById(trigger.dataset.bookingDialogOpen);

            if (dialog instanceof HTMLDialogElement) {
                dialog.showModal();
            }
        });
    });

    document.querySelectorAll('[data-booking-dialog-close]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const dialog = trigger.closest('dialog');

            if (dialog instanceof HTMLDialogElement) {
                dialog.close();
            }
        });
    });

    document.querySelectorAll('dialog[data-booking-dialog]').forEach((dialog) => {
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) {
                dialog.close();
            }
        });
    });

    document.querySelectorAll('[data-auto-submit]').forEach((field) => {
        field.addEventListener('change', () => field.form?.requestSubmit());
    });
});
