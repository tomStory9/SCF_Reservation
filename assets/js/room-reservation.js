import '../styles/reservation-calendar.css';
import { Calendar } from '@fullcalendar/core';
import frLocale from '@fullcalendar/core/locales/fr';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import Swal from 'sweetalert2';

document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('calendar-holder');
    const roomTabs = document.querySelectorAll('.room-tab');
    const selectionPreview = document.getElementById('selection-preview');
    const priceContainer = document.getElementById('price-container');
    const priceDisplay = document.getElementById('price-display');
    const submitButton = document.getElementById('submit_booking');

    if (!calendarEl) return;

    let calendar;

    let activeRoomId = null;
    let activeRoomName = '';
    let currentSelection = null;
    let currentRoomBookings = [];

    let activeRoomPricing = {};

    const initialTab = document.querySelector('.room-tab.is-active');
    if (initialTab) {
        setActiveRoom(initialTab);
    }

    function setActiveRoom(tabElement) {
        activeRoomId = tabElement.dataset.roomId;
        activeRoomName = tabElement.dataset.roomName;

        const rawPricing = JSON.parse(tabElement.dataset.roomPricing);
        activeRoomPricing = {};

        rawPricing.forEach(pricing => {
            activeRoomPricing[pricing.dayNumber] = pricing.fullPrice;
        });

        currentSelection = null;
        updatePreview('Aucune date sélectionnée.');
        priceContainer.classList.add('hidden');

        if (calendar) {
            calendar.unselect();
            calendar.refetchEvents();
        }
    }

    roomTabs.forEach(tab => {
        tab.addEventListener('click', function () {
            roomTabs.forEach(item => {
                item.classList.remove('is-active', 'border-b-0', 'bg-white', 'text-primary');
                item.classList.add('border-transparent', 'text-slate-500');
                item.setAttribute('aria-selected', 'false');
            });

            tab.classList.add('is-active', 'border-b-0', 'bg-white', 'text-primary');
            tab.classList.remove('border-transparent', 'text-slate-500');
            tab.setAttribute('aria-selected', 'true');

            setActiveRoom(tab);
        });
    });

    calendar = new Calendar(calendarEl, {
        plugins: [dayGridPlugin, interactionPlugin],
        initialView: 'dayGridMonth',
        locale: frLocale,
        firstDay: 1,
        height: 'auto',
        selectable: true,
        selectMirror: true,
        unselectAuto: false,
        headerToolbar: {
            left: 'prev,next',
            center: 'title',
            right: 'today'
        },
        validRange: {
            start: new Date().toISOString().split('T')[0]
        },

        events: async function (fetchInfo, successCallback, failureCallback) {
            if (!activeRoomId) {
                successCallback([]);
                return;
            }
            try {
                const response = await fetch(`/zone/${activeRoomId}/bookings`);
                const events = await response.json();

                const formattedEvents = events.map(event => ({
                    ...event,
                    display: 'background',
                    color: '#ff9f89'
                }));

                currentRoomBookings = formattedEvents;
                successCallback(formattedEvents);
            } catch (error) {
                failureCallback(error);
            }
        },

        selectAllow: function (selectInfo) {
            const selectStart = selectInfo.start.getTime();
            const selectEnd = selectInfo.end.getTime();

            return !currentRoomBookings.some(event => {
                const eventStart = new Date(event.start).getTime();
                const eventEnd = new Date(event.end).getTime();
                return selectStart < eventEnd && selectEnd > eventStart;
            });
        },

        select: function (info) {
            let totalPrice = 0;
            let nights = 0;

            let currentCursor = new Date(info.start);
            const endDate = new Date(info.end);

            while (currentCursor < endDate) {
                const jsDay = currentCursor.getDay();

                const phpDayNumber = jsDay === 0 ? 7 : jsDay;

                const nightPrice = activeRoomPricing[phpDayNumber] || 0;
                totalPrice += nightPrice;
                nights++;

                currentCursor.setDate(currentCursor.getDate() + 1);
            }

            const startStrFr = info.start.toLocaleDateString('fr-FR');
            const endDisplayDate = new Date(info.end.getTime() - (1000 * 3600 * 24));
            const endStrFr = endDisplayDate.toLocaleDateString('fr-FR');

            currentSelection = {
                startDate: info.startStr,
                endDate: info.endStr,
                nights: nights,
                price: totalPrice
            };

            updatePreview(
                `<span class="font-semibold text-secondary">Chambre :</span> <span class="text-primary">${activeRoomName}</span><br>` +
                `<span class="font-semibold text-secondary">Arrivée :</span> ${startStrFr} (dès 14h)<br>` +
                `<span class="font-semibold text-secondary">Départ :</span> ${endStrFr} (avant 11h)<br>` +
                `<span class="font-semibold text-secondary">Durée :</span> ${nights} nuit(s)`
            );

            priceDisplay.textContent = `${totalPrice.toLocaleString('fr-FR')} ¥`;
            priceContainer.classList.remove('hidden');
        }
    });

    calendar.render();

    function updatePreview(content) {
        if (selectionPreview) selectionPreview.innerHTML = content;
    }

    if (submitButton) {
        submitButton.addEventListener('click', async function() {
            if (!currentSelection || !activeRoomId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Attention',
                    text: 'Veuillez sélectionner vos dates sur le calendrier.'
                });
                return;
            }

            const payload = {
                ...currentSelection,
                roomId: activeRoomId,
                bookingMode: 'room_night'
            };

            try {
                submitButton.disabled = true;
                submitButton.textContent = 'Enregistrement...';

                const response = await fetch('/booking/create', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    window.location.href = result.redirectUrl;
                } else {
                    Swal.fire({ icon: 'error', title: 'Erreur', text: result.error });
                }
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Erreur réseau', text: 'Impossible de contacter le serveur.' });
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = 'Confirmer la réservation';
            }
        });
    }
});
