import { Calendar } from '@fullcalendar/core';
import frLocale from '@fullcalendar/core/locales/fr';
import timeGridPlugin from '@fullcalendar/timegrid';
import dayGridPlugin from '@fullcalendar/daygrid';


document.addEventListener('DOMContentLoaded', () => {
    const calendarElement = document.getElementById('booking-calendar');
    const zoneFilter = document.getElementById('zone-filter');
    const modal = document.getElementById('booking-modal');

    const approveButton = document.getElementById('booking-approve-button');
    const declineButton = document.getElementById('booking-decline-button');

    const config = window.bookingCalendarConfig ?? {};

    const currentYear = Number(config.currentYear) || new Date().getFullYear();
    const currentMonth = Number(config.currentMonth) || new Date().getMonth() + 1;
    const calendarUrl = config.calendarUrl || window.location.pathname;

    const rawBookings = Array.isArray(window.bookingCalendarBookings)
        ? window.bookingCalendarBookings
        : [];

    console.log(rawBookings);

    const statusLabels = {
        pending: 'En attente',
        approved: 'Approuvée',
        declined: 'Refusée',
    };

    const statusColors = {
        pending: '#d69e2e',
        approved: '#198754',
        declined: '#dc3545',
    };

    let selectedEvent = null;


    function getStatusClass(status) {
        return `booking-status-${status}`;
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
        if (value === null || value === undefined || value === '') {
            return '-';
        }

        return new Intl.NumberFormat('ja-JP', {
            style: 'currency',
            currency: 'JPY',
            maximumFractionDigits: 0,
        }).format(Number(value));
    }

    function renderEquipments(equipments) {
        if (!Array.isArray(equipments) || equipments.length === 0) {
            return 'Aucun équipement';
        }

        return equipments
            .map((equipment) => {
                const name = equipment.name ?? equipment.equipment?.name ?? 'Équipement';
                const quantity = equipment.quantity ?? 1;
                const totalPrice = equipment.totalPrice ?? equipment.price ?? null;

                return `${name} × ${quantity} (${formatPrice(totalPrice)})`;
            })
            .join(', ');
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

        selectedEvent = null;
    }

    function redirectToMonth(date) {
        const url = new URL(calendarUrl, window.location.origin);

        url.searchParams.set('year', String(date.getFullYear()));
        url.searchParams.set('month', String(date.getMonth() + 1));

        window.location.assign(url.toString());
    }

    const events = rawBookings.map((booking) => {

        return {
            id: String(booking.id),
            title: booking.zone.name,
            start: booking.startDate,
            end: booking.endDate,
            allDay: Boolean(booking.isFullDay),
            classNames: [
                'booking-calendar-event',
                getStatusClass(booking.status),
            ],
            backgroundColor: statusColors[booking.status] ?? statusColors.pending,
            borderColor: statusColors[booking.status] ?? statusColors.pending,
            textColor: '#ffffff',
            extendedProps: {
                status: booking.status,
                zoneId: booking.zone?.id,
                zoneName: booking.zone?.name,
                userName: booking.userBooking ? `${booking.userBooking.name} ${booking.userBooking.lastName}` : '-',
                guestCount: booking.guestCount ?? '-',
                totalPrice: booking.totalPrice ?? null,
                equipmentPrice: booking.equipmentPrice ?? null,
                isFullDay: Boolean(booking.isFullDay),
                equipments: booking.bookingEquipment
                    ?? [],
            },
        };
    });

    function openBookingModal(event) {
        if (!modal) {
            return;
        }

        selectedEvent = event;

        const props = event.extendedProps ?? {};
        const status = props.status;

        safeSetText('modal-booking-id', event.id);
        safeSetText(
            'modal-booking-status',
            statusLabels[status] ?? status,
        );
        safeSetText('modal-booking-user', props.userName);
        safeSetText('modal-booking-zone', props.zoneName);
        safeSetText('modal-booking-start', formatDate(event.start));
        safeSetText('modal-booking-end', formatDate(event.end));
        safeSetText('modal-booking-guest-count', props.guestCount);
        safeSetText('modal-booking-total-price', formatPrice(props.totalPrice));
        safeSetText(
            'modal-booking-equipment-price',
            formatPrice(props.equipmentPrice),
        );
        safeSetText(
            'modal-booking-full-day',
            props.isFullDay ? 'Oui' : 'Non',
        );
        safeSetText(
            'modal-booking-equipment',
            renderEquipments(props.equipments),
        );
        safeSetText('modal-booking-description', props.description);

        const isPending = status === 'pending';

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
        plugins: [
            dayGridPlugin,
            timeGridPlugin,
        ],

        initialView: 'dayGridMonth',
        initialDate: `${currentYear}-${String(currentMonth).padStart(2, '0')}-01`,

        locale: frLocale,
        firstDay: 1,

        height: 'auto',
        expandRows: true,

        slotMinTime: '07:00:00',
        slotMaxTime: '21:00:00',
        slotDuration: '00:30:00',
        slotEventOverlap: false,

        headerToolbar: {
            left: 'prev,next,today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay',
        },

        buttonText: {
            today: "Aujourd’hui",
            month: 'Mois',
            week: 'Semaine',
            day: 'Jour',
        },

        events,

        datesSet(info) {
            const displayedDate = info.view.currentStart;

            if (
                displayedDate.getFullYear() !== currentYear
                || displayedDate.getMonth() + 1 !== currentMonth
            ) {
                redirectToMonth(displayedDate);
            }
        },

        eventClick(info) {
            info.jsEvent.preventDefault();
            openBookingModal(info.event);
        },
    });

    calendar.render();

    zoneFilter?.addEventListener('change', () => {
        const selectedZoneId = zoneFilter.value;

        calendar.removeAllEvents();

        events
            .filter((event) => {
                return selectedZoneId === ''
                    || String(event.extendedProps.zoneId) === selectedZoneId;
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
        const response = await fetch(`/booking/${selectedEvent?.id}/approve`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        if (!response.ok) {
            console.error(
                '[booking-calendar] Failed to approve booking:',
                selectedEvent?.id,
                response.status,
                response.statusText,
            );
            return; //reload page
        }
        location.reload()
    });

    declineButton?.addEventListener('click', async () => {
        const response = await fetch(`/booking/${selectedEvent?.id}/decline`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            console.error(
                '[booking-calendar] Failed to approve booking:',
                selectedEvent?.id,
                response.status,
                response.statusText,
            );
            return;
        }
        location.reload()
    });
});