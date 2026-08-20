import { Calendar } from '@fullcalendar/core';
import frLocale from '@fullcalendar/core/locales/fr';
import enGbLocale from '@fullcalendar/core/locales/en-gb';
import jaLocale from '@fullcalendar/core/locales/ja';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';

import '../styles/admin-dashboard.css';

document.addEventListener('DOMContentLoaded', () => {
    const calendarElement = document.getElementById('scf-booking-calendar');
    const modal = document.getElementById('booking-modal');
    const facilityFilter = document.getElementById('facility-filter');
    const approveButton = document.getElementById('booking-approve-button');
    const declineButton = document.getElementById('booking-decline-button');

    if (!calendarElement) {
        return;
    }

    const locales = {
        fr: frLocale,
        en: enGbLocale,
        ja: jaLocale,
    };

    const locale = calendarElement.dataset.locale || 'fr';
    let allEvents = [];

    try {
        allEvents = JSON.parse(calendarElement.dataset.events || '[]');
    } catch (error) {
        console.error('[admin-dashboard] Invalid calendar events data.', error);
    }

    function formatDate(value) {
        if (!value) {
            return '-';
        }

        return new Intl.DateTimeFormat('fr-FR', {
            dateStyle: 'full',
            timeStyle: 'short',
        }).format(new Date(value));
    }

    function formatPrice(value) {
        const number = Number(value);

        if (!Number.isFinite(number)) {
            return '-';
        }

        return new Intl.NumberFormat('ja-JP', {
            style: 'currency',
            currency: 'JPY',
            maximumFractionDigits: 0,
        }).format(number);
    }

    function safeSetText(elementId, value) {
        const element = document.getElementById(elementId);

        if (element) {
            element.textContent = value ?? '-';
        }
    }

    function closeBookingModal() {
        if (modal?.open) {
            modal.close();
        }
    }

    function renderEquipmentList(equipmentList) {
        const container = document.getElementById('modal-booking-equipment');

        if (!container) {
            return;
        }

        container.replaceChildren();

        if (!Array.isArray(equipmentList) || equipmentList.length === 0) {
            container.textContent = '-';
            return;
        }

        const list = document.createElement('ul');
        list.className = 'booking-equipment-list';

        equipmentList.forEach((equipment) => {
            const item = document.createElement('li');
            item.className = 'booking-equipment-item';

            const name = document.createElement('strong');
            name.textContent = equipment.name || equipment.equipmentName || 'Équipement inconnu';

            const quantity = document.createElement('span');
            quantity.textContent = `Quantité : ${equipment.quantity ?? 0}`;

            const unitPrice = document.createElement('span');
            unitPrice.textContent = `Prix unitaire : ${formatPrice(
                equipment.unitPrice ?? equipment.equipmentUnitPrice,
            )}`;

            const totalPrice = document.createElement('span');
            totalPrice.textContent = `Prix total : ${formatPrice(
                equipment.totalPrice ?? equipment.bookingEquipmentTotalPrice,
            )}`;

            item.append(name, quantity, unitPrice, totalPrice);
            list.appendChild(item);
        });

        container.appendChild(list);
    }

    function openBookingModal(event) {
        if (!modal) {
            return;
        }

        const props = event.extendedProps || {};

        safeSetText('modal-booking-id', event.id);
        safeSetText('modal-booking-status', props.status || '-');
        safeSetText('modal-booking-user', props.user || '-');
        safeSetText('modal-booking-zone', props.zone || '-');
        safeSetText('modal-booking-start', formatDate(event.start));
        safeSetText('modal-booking-end', formatDate(event.end));
        safeSetText('modal-booking-guest-count', props.guests ?? '-');
        safeSetText('modal-booking-full-day', props.isFullDay ? 'Oui' : 'Non');
        safeSetText('modal-booking-booking-price', formatPrice(props.amount));
        safeSetText('modal-booking-equipment-total', formatPrice(props.equipmentTotalPrice));
        safeSetText('modal-booking-total-price', formatPrice(props.totalPrice));

        renderEquipmentList(props.equipment);

        const isPending = [
            'En attente',
            'Pending',
            'pending',
        ].includes(props.status);

        if (approveButton) {
            approveButton.hidden = !isPending;
        }

        if (declineButton) {
            declineButton.hidden = !isPending;
        }

        if (!modal.open) {
            modal.showModal();
        }
    }

    const calendar = new Calendar(calendarElement, {
        plugins: [dayGridPlugin, timeGridPlugin],
        initialView: window.matchMedia('(max-width: 720px)').matches
            ? 'timeGridDay'
            : 'timeGridWeek',
        locale: locales[locale] || frLocale,
        firstDay: 1,
        nowIndicator: true,
        allDaySlot: true,
        slotMinTime: '07:00:00',
        slotMaxTime: '24:00:00',
        scrollTime: '08:00:00',
        height: 'auto',
        expandRows: true,
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
        },
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'timeGridWeek,timeGridDay,dayGridMonth',
        },
        events: allEvents,

        eventDidMount(info) {
            const details = info.event.extendedProps || {};

            info.el.title = [
                details.status,
                details.user,
                details.zone,
                details.facility,
            ]
                .filter(Boolean)
                .join(' · ');
        },

        eventClick(info) {
            info.jsEvent.preventDefault();
            openBookingModal(info.event);
        },
    });

    calendar.render();

    facilityFilter?.addEventListener('change', () => {
        const selectedFacility = facilityFilter.value;

        calendar.removeAllEvents();

        allEvents
            .filter((event) => {
                if (selectedFacility === '') {
                    return true;
                }

                return event.extendedProps?.facility === selectedFacility;
            })
            .forEach((event) => {
                calendar.addEvent(event);
            });
    });

    document
        .querySelectorAll('[data-booking-modal-close]')
        .forEach((button) => {
            button.addEventListener('click', closeBookingModal);
        });

    modal?.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeBookingModal();
        }
    });

    approveButton?.addEventListener('click', async () => {
        const bookingId = document.getElementById('modal-booking-id')?.textContent;

        if (!bookingId || bookingId === '-') {
            return;
        }

        const response = await fetch(`/booking/${bookingId}/approve`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            console.error('[admin-dashboard] Failed to approve booking:', bookingId);
            alert("Erreur lors de l'approbation");
            return;
        }

        window.location.reload();
    });

    declineButton?.addEventListener('click', async () => {
        const bookingId = document.getElementById('modal-booking-id')?.textContent;

        if (!bookingId || bookingId === '-') {
            return;
        }

        const response = await fetch(`/booking/${bookingId}/decline`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            console.error('[admin-dashboard] Failed to decline booking:', bookingId);
            alert('Erreur lors du refus');
            return;
        }

        window.location.reload();
    });
});