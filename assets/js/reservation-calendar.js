import '../styles/reservation-calendar.css';

import { Calendar } from '@fullcalendar/core';
import frLocale from '@fullcalendar/core/locales/fr';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import TomSelect from "tom-select";
import Swal from 'sweetalert2';

document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('calendar-holder');
    const calendarLegend = document.getElementById('calendar-legend');
    const locationTabs = document.querySelectorAll('.location-tab');
    const bookingModeLinks = document.querySelectorAll('.booking-mode-link');
    const locationLabel = document.getElementById('selected-location-label');
    const selectionPreview = document.getElementById('selection-preview');
    const activeBookingModeLabel = document.getElementById('active-booking-mode-label');
    const zoneSelectEl = document.getElementById('zone-select');
    const submitButton = document.getElementById('submit_booking');
    const guestNbInput = document.getElementById('guest-count-input');
    const configEl = document.getElementById('calendar-config');


    if (!zoneSelectEl) return;

    let activeZoneId = null;
    let currentZoneBookings = [];

    let currentZonePricings = {};
    let currentSelection = null;

    const maxAllowedDate = configEl ? configEl.dataset.maxDate : null;

    const zoneTomSelect = new TomSelect(zoneSelectEl, {
        valueField: 'id',
        labelField: 'name',
        searchField: 'name',
        placeholder: 'Sélectionnez une zone...',
        allowEmptyOption: false,
        onChange: async function(zoneId) {
            if (!zoneId) return;

            activeZoneId = zoneId;
            updatePriceUI(null);
            currentSelection = null;

            try {
                const response = await fetch(`/zone/${activeZoneId}/pricings`);
                if (!response.ok) throw new Error('Erreur lors du chargement des tarifs');

                currentZonePricings = await response.json();
            } catch (error) {
                console.error('Erreur :', error);
                currentZonePricings = {};
            }

            if (calendar) {
                calendar.refetchEvents();
            }
        }
    });

    function getSelectedZoneName() {
        if (!activeZoneId || !zoneTomSelect) return 'Non défini';
        const option = zoneTomSelect.options[activeZoneId] || zoneTomSelect.options[String(activeZoneId)] || zoneTomSelect.options[Number(activeZoneId)];
        return option ? option.name : 'Non défini';
    }

    async function loadZonesForFacility(facilityId) {
        zoneTomSelect.clear();
        zoneTomSelect.clearOptions();

        const response = await fetch(`/facility/${facilityId}/zones`);
        if (!response.ok) throw new Error('Erreur lors de la récupération des zones');

        const zones = await response.json();

        if (zones.length === 0) {
            zoneTomSelect.settings.placeholder = 'Aucune zone disponible';
            zoneTomSelect.input.placeholder = 'Aucune zone disponible';
            zoneTomSelect.updatePlaceholder();
            return;
        }

        zoneTomSelect.addOptions(zones);

        zoneTomSelect.setValue(zones[0].id);
    }

    locationTabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            const facilityId = tab.dataset.location;

            locationTabs.forEach(item => {
                item.classList.remove('is-active', 'border-slate-200', 'bg-white', 'text-primary');
                item.classList.add('border-transparent', 'text-slate-500');
                item.setAttribute('aria-selected', 'false');
            });

            tab.classList.add('is-active', 'border-slate-200', 'bg-white', 'text-primary');
            tab.classList.remove('border-transparent', 'text-slate-500');
            tab.setAttribute('aria-selected', 'true');

            if (locationLabel) {
                locationLabel.textContent = tab.textContent.trim();
            }

            loadZonesForFacility(facilityId);
        });
    });

    const initialActiveTab = document.querySelector('.location-tab.is-active');
    if (initialActiveTab) {
        loadZonesForFacility(initialActiveTab.dataset.location);
    }

    if (!calendarEl) {
        return;
    }

    let bookingMode = 'hour';
    let selectedPeriodPreviewEvent = null;
    let currentViewType = 'dayGridMonth';

    const dailyUsage = {};
    const allDayBlockedDates = new Set();

    function normalizeDate(dateLike) {
        const date = new Date(dateLike);
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function clearPeriodPreviewEvent() {
        selectedPeriodPreviewEvent = null;
    }

    function updateLegendVisibility() {
        if (!calendarLegend) {
            return;
        }

        calendarLegend.classList.toggle('hidden', currentViewType !== 'dayGridMonth');
    }

    function getUsageClass(dateStr) {
        if (currentViewType !== 'dayGridMonth') {
            return '';
        }

        const usage = dailyUsage[dateStr];

        if (!usage || !usage.percentage) {
            return '';
        }

        if (usage.percentage >= 100) return 'day-usage-100';
        if (usage.percentage >= 75) return 'day-usage-75';
        if (usage.percentage >= 50) return 'day-usage-50';
        if (usage.percentage >= 25) return 'day-usage-25';

        return '';
    }

    function recomputeDailyUsage(events) {
        Object.keys(dailyUsage).forEach(function (key) {
            delete dailyUsage[key];
        });

        allDayBlockedDates.clear();

        const dailyHoursLimit = 12;

        events.forEach(function (event) {
            if (!event.start) {
                return;
            }

            if (event.allDay) {
                const start = new Date(event.start);
                const end = event.end ? new Date(event.end) : new Date(event.start);
                const cursor = new Date(start);

                while (cursor < end) {
                    const loopDate = normalizeDate(cursor);

                    if (!dailyUsage[loopDate]) {
                        dailyUsage[loopDate] = {
                            usedHours: 0,
                            percentage: 0
                        };
                    }

                    allDayBlockedDates.add(loopDate);
                    dailyUsage[loopDate].usedHours = dailyHoursLimit;
                    dailyUsage[loopDate].percentage = 100;

                    cursor.setDate(cursor.getDate() + 1);
                }

                return;
            }

            const dateStr = normalizeDate(event.start);

            if (!dailyUsage[dateStr]) {
                dailyUsage[dateStr] = {
                    usedHours: 0,
                    percentage: 0
                };
            }

            if (event.start && event.end) {
                const start = new Date(event.start);
                const end = new Date(event.end);
                const duration = Math.max(0, (end - start) / 36e5);

                dailyUsage[dateStr].usedHours += duration;
                dailyUsage[dateStr].percentage = Math.min(
                    100,
                    Math.round((dailyUsage[dateStr].usedHours / dailyHoursLimit) * 100)
                );
            }
        });
    }

    function hasAnyEventOnDate(dateStr) {
        return currentZoneBookings.some(function (event) {
            if (!event.start) return false;

            if (event.allDay) {
                const start = new Date(event.start);
                const end = event.end ? new Date(event.end) : new Date(event.start);
                const cursor = new Date(start);

                while (cursor < end) {
                    if (normalizeDate(cursor) === dateStr) {
                        return true;
                    }

                    cursor.setDate(cursor.getDate() + 1);
                }

                return false;
            }

            return normalizeDate(event.start) === dateStr;
        });
    }

    function selectionOverlapsExistingEvent(selectInfo) {
        if (bookingMode !== 'hour' || selectInfo.allDay) {
            return false;
        }

        return currentZoneBookings.some(function (event) {
            if (event.allDay || !event.start || !event.end) {
                return false;
            }

            const eventStart = new Date(event.start).getTime();
            const eventEnd = new Date(event.end).getTime();
            const selectStart = selectInfo.start.getTime();
            const selectEnd = selectInfo.end.getTime();

            return selectStart < eventEnd && selectEnd > eventStart;
        });
    }

    function getPeriodFromDate(date) {
        const hour = date.getHours();

        if (hour >= 9 && hour < 13) {
            return { key: 'morning', label: 'Matin', start: '09:00', end: '13:00' };
        }

        if (hour >= 13 && hour < 17) {
            return { key: 'afternoon', label: 'Après-midi', start: '13:00', end: '17:00' };
        }

        if (hour >= 17 && hour < 21) {
            return { key: 'evening', label: 'Soir', start: '17:00', end: '21:00' };
        }

        return null;
    }

    function getBackgroundPeriodEvents(fetchInfo) {
        if (bookingMode !== 'period' || !calendar.view || calendar.view.type !== 'timeGridWeek') {
            return [];
        }

        const events = [];
        const cursor = new Date(fetchInfo.start);

        while (cursor < fetchInfo.end) {
            const dateStr = normalizeDate(cursor);

            events.push(
                {
                    id: `bg-morning-${dateStr}`,
                    start: `${dateStr}T09:00:00`,
                    end: `${dateStr}T13:00:00`,
                    display: 'background',
                    classNames: ['fc-bg-period-morning']
                },
                {
                    id: `bg-afternoon-${dateStr}`,
                    start: `${dateStr}T13:00:00`,
                    end: `${dateStr}T17:00:00`,
                    display: 'background',
                    classNames: ['fc-bg-period-afternoon']
                },
                {
                    id: `bg-evening-${dateStr}`,
                    start: `${dateStr}T17:00:00`,
                    end: `${dateStr}T21:00:00`,
                    display: 'background',
                    classNames: ['fc-bg-period-evening']
                }
            );

            cursor.setDate(cursor.getDate() + 1);
        }

        if (selectedPeriodPreviewEvent) {
            events.push(selectedPeriodPreviewEvent);
        }

        return events;
    }

    function updatePreview(content) {
        if (selectionPreview) selectionPreview.innerHTML = content;
    }

    function updateBookingModeUI() {
        bookingModeLinks.forEach(function (button) {
            const isActive = button.dataset.mode === bookingMode;
            button.classList.toggle('is-active', isActive);
            button.classList.toggle('text-secondary', isActive);
            button.classList.toggle('text-state', !isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });

        if (activeBookingModeLabel) {
            activeBookingModeLabel.textContent = bookingMode === 'period' ? 'Par période' : 'Par heure';
        }
    }

    function selectionAllowed(info) {
        const startDateStr = normalizeDate(info.start);

        const endDateStr = normalizeDate(new Date(info.end.getTime() - 1));

        if (startDateStr !== endDateStr) {
            return false;
        }

        const now = new Date();

        if (info.start < now) {
            return false;
        }

        if (info.allDay) {
            return !hasAnyEventOnDate(startDateStr);
        }

        if (bookingMode === 'period') {
            return false;
        }

        if (allDayBlockedDates.has(startDateStr)) {
            return false;
        }

        if (selectionOverlapsExistingEvent(info)) {
            return false;
        }

        const startHour = info.start.getHours() + (info.start.getMinutes() / 60);
        const endHour = info.end.getHours() + (info.end.getMinutes() / 60);

        if (!(startHour >= 8 && endHour <= 21)) {
            return false;
        }

        const durationMs = info.end.getTime() - info.start.getTime();
        const oneHourMs = 60 * 60 * 1000;

        return durationMs >= oneHourMs;
    }

    function parseCleanIso(isoString) {
        if (!isoString) {
            return { dateFr: '', time: '', dateIso: '', cleanIso: '' };
        }

        const dateIso = isoString.slice(0, 10);

        const [year, month, day] = dateIso.split('-');
        const dateFr = `${day}/${month}/${year}`;

        const hasTime = isoString.includes('T');
        const time = hasTime ? isoString.slice(11, 16) : '';

        const cleanIso = hasTime ? `${dateIso}T${time}:00` : dateIso;

        return { dateFr, time, dateIso, cleanIso };
    }

    function getTodayIsoString() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function getPricesForSelection(startIso, endIso, mode, periodKey = null) {
        if (!startIso || !currentZonePricings || Object.keys(currentZonePricings).length === 0) {
            return null;
        }

        const userDataEl = document.getElementById('user-booking-data');
        const freeHours = userDataEl ? parseFloat(userDataEl.dataset.freeHours) : 0;

        let current;
        if (startIso.length === 10) {
            const parts = startIso.split('-');
            current = new Date(parts[0], parts[1] - 1, parts[2], 0, 0, 0);
        } else {
            current = new Date(startIso);
        }

        const dayNumber = current.getDay() || 7;
        const dayPricings = currentZonePricings[dayNumber];

        if (!dayPricings) return null;

        if (mode === 'period' && periodKey) {
            let basePrice = dayPricings.period[periodKey];

            if (typeof basePrice === 'object' && basePrice !== null) {
                basePrice = basePrice.price;
            }

            if (basePrice === undefined) return null;

            let finalPrice = basePrice;

            if (freeHours >= 4) {
                finalPrice = 0;
            } else if (freeHours > 0) {
                finalPrice = basePrice * ((4 - freeHours) / 4);
            }

            return {
                price: Math.round(finalPrice),
                basePrice: basePrice
            };
        }

        if (mode === 'hour' && endIso) {
            let basePrice = 0;
            let hasValidPricing = false;
            let validHoursCount = 0;

            let end;
            if (endIso.length === 10) {
                const parts = endIso.split('-');
                end = new Date(parts[0], parts[1] - 1, parts[2], 0, 0, 0);
            } else {
                end = new Date(endIso);
            }

            while (current < end) {
                const loopDay = current.getDay() || 7;
                const hours = String(current.getHours()).padStart(2, '0');
                const minutes = String(current.getMinutes()).padStart(2, '0');
                const timeKey = `${hours}:${minutes}`;

                const hourlyPrice = currentZonePricings[loopDay]?.hourly?.[timeKey];

                if (hourlyPrice !== undefined && hourlyPrice !== null) {
                    const priceToAdd = typeof hourlyPrice === 'object' ? hourlyPrice.price : hourlyPrice;

                    if (!isNaN(priceToAdd)) {
                        basePrice += priceToAdd;
                        validHoursCount++;
                        hasValidPricing = true;
                    }
                }
                current.setHours(current.getHours() + 1);
            }

            let finalPrice = basePrice;

            if (freeHours >= validHoursCount) {
                finalPrice = 0;
            } else if (freeHours > 0 && validHoursCount > 0) {
                finalPrice = basePrice * ((validHoursCount - freeHours) / validHoursCount);
            }

            return hasValidPricing ? {
                price: Math.round(finalPrice),
                basePrice: basePrice
            } : null;
        }

        return null;
    }

    function updatePriceUI(prices) {
        const priceContainer = document.getElementById('price-container');
        const priceDisplay = document.getElementById('price-display');

        if (!priceContainer || !priceDisplay) return;

        if (!prices || prices.price === null || prices.price === undefined) {
            priceContainer.classList.add('hidden');
        } else {
            const formattedPrice = prices.price.toLocaleString('fr-FR');

            priceDisplay.textContent = `${formattedPrice} ¥`;
            priceContainer.classList.remove('hidden');
        }
    }

    const calendar = new Calendar(calendarEl, {
        plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
        initialView: 'dayGridMonth',
        locale: frLocale,
        firstDay: 1,
        height: 'auto',
        contentHeight: 'auto',
        expandRows: false,
        nowIndicator: true,
        selectable: true,
        editable: false,
        weekends: true,
        allDaySlot: true,
        slotMinTime: '09:00:00',
        slotMaxTime: '21:00:00',
        slotDuration: '01:00:00',
        snapDuration: '01:00:00',
        selectMirror: true,
        selectOverlap: function (event) {
            return event.display === 'background';
        },
        eventOverlap: false,
        businessHours: {
            daysOfWeek: [0, 1, 2, 3, 4, 5, 6],
            startTime: '09:00',
            endTime: '21:00'
        },
        views: {
            dayGridMonth: {
                dayMaxEventRows: 3
            }
        },
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek'
        },
        buttonText: {
            today: 'Aujourd’hui',
            month: 'Mois',
            week: 'Semaine'
        },
        displayEventTime: true,
        displayEventEnd: true,
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        },
        validRange: {
            start: getTodayIsoString(),
            end: maxAllowedDate
        },
        events: async function (fetchInfo, successCallback, failureCallback) {
            if (!activeZoneId) {
                currentZoneBookings = [];
                successCallback(getBackgroundPeriodEvents(fetchInfo));
                return;
            }

            try {
                const response = await fetch(`/zone/${activeZoneId}/bookings`);
                if (!response.ok) throw new Error('Erreur lors du chargement des réservations');

                currentZoneBookings = await response.json();
                recomputeDailyUsage(currentZoneBookings);

                successCallback(currentZoneBookings.concat(getBackgroundPeriodEvents(fetchInfo)));
            } catch (error) {
                console.error(error);
                failureCallback(error);
            }
        },
        dayCellClassNames: function (arg) {
            const dateStr = normalizeDate(arg.date);
            const usageClass = getUsageClass(dateStr);
            return usageClass ? [usageClass] : [];
        },
        dayCellDidMount: function (arg) {
            if (currentViewType !== 'dayGridMonth') {
                arg.el.removeAttribute('title');
                return;
            }

            const dateStr = normalizeDate(arg.date);
            const usage = dailyUsage[dateStr];

            if (usage && usage.usedHours > 0) {
                arg.el.title = usage.usedHours.toFixed(1) + 'h utilisées sur 12h';
            }
        },
        datesSet: function (info) {
            currentViewType = info.view.type;
            updateLegendVisibility();
            calendar.refetchEvents();
        },
        dateClick: function (info) {
            if (calendar.view.type === 'dayGridMonth') {
                calendar.changeView('timeGridWeek', info.dateStr);
                const data = parseCleanIso(info.dateStr);
                updatePreview(
                    '<span class="font-semibold text-secondary">Jour sélectionné :</span> ' +
                    '<span class="font-semibold text-primary">' + data.dateFr + '</span>'
                );
                return;
            }

            if (bookingMode === 'period' && calendar.view.type === 'timeGridWeek') {
                const period = getPeriodFromDate(info.date);
                if (!period) return;

                const prices = getPricesForSelection(info.dateStr, null, 'period', period.key);

                const dateStr = normalizeDate(info.date);
                const data = parseCleanIso(info.dateStr);

                updatePriceUI(prices);

                if (hasAnyEventOnDate(dateStr)) {
                    alert('Impossible de réserver une période : un événement existe déjà sur cette journée.');
                    return;
                }

                currentSelection = {
                    bookingMode: 'period',
                    startDate: data.dateIso,
                    startTime: period.start,
                    endTime: period.end,
                    periodKey: period.key,
                    isFullDay: false,
                    guestNb: guestNbInput.value,
                    price: prices ? prices.price : 0,
                    basePrice: prices ? prices.basePrice : 0
                };

                selectedPeriodPreviewEvent = {
                    id: `period-preview-${dateStr}-${period.key}`,
                    title: `Période sélectionnée · ${period.label}`,
                    start: `${dateStr}T${period.start}:00`,
                    end: `${dateStr}T${period.end}:00`,
                    allDay: false,
                    classNames: ['period-preview-event']
                };

                calendar.refetchEvents();

                updatePreview(
                    '<span class="font-semibold text-secondary">Réservation par période :</span> ' +
                    '<span class="font-semibold text-primary">' + period.label + '</span>' +
                    '<br><span class="text-state">Date : ' + data.dateFr + '</span>' +
                    '<br><span class="text-state">Horaires : ' + period.start + ' → ' + period.end + '</span>' +
                    '<br><span class="text-state">Lieu : ' + getSelectedZoneName() + '</span>'
                );
            }
        },
        selectAllow: function (selectInfo) {
            return selectionAllowed(selectInfo);
        },
        select: function (info) {
            const startData = parseCleanIso(info.startStr);
            const endData = parseCleanIso(info.endStr);
            const prices = getPricesForSelection(info.startStr, info.endStr, 'hour');

            updatePriceUI(prices);

            if (info.allDay) {
                if (hasAnyEventOnDate(startData.dateIso)) {
                    calendar.unselect();
                    alert('Impossible de créer un événement journée complète : un événement existe déjà sur cette journée.');
                    return;
                }

                currentSelection = {
                    bookingMode: 'hour',
                    startDate: startData.dateIso,
                    startTime: '00:00',
                    endTime: '23:59',
                    periodKey: null,
                    isFullDay: true,
                    guestNb: guestNbInput.value,
                    price: prices ? prices.price : 0,
                    basePrice: prices ? prices.basePrice : 0
                };

                clearPeriodPreviewEvent();
                calendar.refetchEvents();

                updatePreview(
                    '<span class="font-semibold text-secondary">Sélection all day :</span> ' +
                    '<span class="font-semibold text-primary">' + startData.dateFr + '</span>' +
                    '<br><span class="text-state">Lieu : ' + getSelectedZoneName() + '</span>'
                );
                return;
            }

            if (bookingMode === 'period') {
                calendar.unselect();
                return;
            }

            if (allDayBlockedDates.has(startData.dateIso)) {
                calendar.unselect();
                alert('Impossible de réserver des heures sur cette journée : elle est bloquée en all day.');
                return;
            }

            if (selectionOverlapsExistingEvent(info)) {
                calendar.unselect();
                alert('Impossible de créer ce créneau : il chevauche déjà un événement existant.');
                return;
            }

            currentSelection = {
                bookingMode: 'hour',
                startDate: startData.dateIso,
                startTime: startData.time,
                endTime: endData.time,
                periodKey: null,
                isFullDay: false,
                guestNb: guestNbInput.value,
                price: prices ? prices.price : 0,
                basePrice: prices ? prices.basePrice : 0
            };

            clearPeriodPreviewEvent();
            calendar.refetchEvents();

            const durationMs = info.end.getTime() - info.start.getTime();
            const durationHours = durationMs / (60 * 60 * 1000);

            updatePreview(
                '<span class="font-semibold text-secondary">Créneau horaire :</span> ' +
                '<span class="font-semibold text-primary">' + startData.time + ' → ' + endData.time + '</span>' +
                '<br><span class="text-state">Date : ' + startData.dateFr + '</span>' +
                '<br><span class="text-state">Durée : ' + durationHours + ' heure(s)</span>' +
                '<br><span class="text-state">Lieu : ' + getSelectedZoneName() + '</span>'
            );
        }
    });

    calendar.render();
    updateBookingModeUI();
    updateLegendVisibility();

    bookingModeLinks.forEach(function (button) {
        button.addEventListener('click', function () {
            bookingMode = button.dataset.mode;
            currentSelection = null;
            clearPeriodPreviewEvent();
            updateBookingModeUI();
            updatePriceUI(null);
            calendar.unselect();
            calendar.refetchEvents();

            updatePreview(
                bookingMode === 'period'
                    ? 'Mode de réservation actif : par période. Clique directement dans une zone Matin, Après-midi ou Soir dans la vue semaine.'
                    : 'Mode de réservation actif : par heure. Tu peux sélectionner plusieurs heures d’affilée avec un minimum de 1 heure, sans chevauchement.'
            );
        });
    });

    locationTabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            currentSelection = null;
            clearPeriodPreviewEvent();

            locationTabs.forEach(function (item) {
                item.classList.remove('is-active', 'border-slate-200', 'bg-white', 'text-primary');
                item.classList.add('border-transparent', 'text-slate-500');
                item.setAttribute('aria-selected', 'false');
            });

            tab.classList.add('is-active', 'border-slate-200', 'bg-white', 'text-primary');
            tab.classList.remove('border-transparent', 'text-slate-500');
            tab.setAttribute('aria-selected', 'true');

            updatePreview('Aucune sélection pour le moment.');
            updatePriceUI(null);
            calendar.unselect();
            calendar.refetchEvents();
        });
    });

    if (submitButton) {
        submitButton.addEventListener('click', async function() {
            if (!currentSelection || !activeZoneId) {
                alert('Veuillez sélectionner un créneau sur le calendrier.');
                return;
            }

            const payload = {
                ...currentSelection,
                zoneId: activeZoneId
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
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: result.error || 'Impossible d\'enregistrer la réservation.',
                        confirmButtonColor: '#d33'
                    });
                }
            } catch (error) {
                console.error(error);
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur réseau',
                    text: 'Une erreur est survenue lors de la communication avec le serveur.',
                    confirmButtonColor: '#d33'
                });
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = 'Réserver le créneau';
            }
        });
    }
});
