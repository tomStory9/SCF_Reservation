import '../styles/reservation-calendar.css';

import { Calendar } from '@fullcalendar/core';
import frLocale from '@fullcalendar/core/locales/fr';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import TomSelect from "tom-select";

document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('calendar-holder');
    const calendarLegend = document.getElementById('calendar-legend');
    const locationTabs = document.querySelectorAll('.location-tab');
    const bookingModeLinks = document.querySelectorAll('.booking-mode-link');
    const locationLabel = document.getElementById('selected-location-label');
    const locationDescription = document.getElementById('location-description');
    const selectionPreview = document.getElementById('selection-preview');
    const activeBookingModeLabel = document.getElementById('active-booking-mode-label');
    const zoneSelectEl = document.getElementById('zone-select');

    if (!zoneSelectEl) return;

    let activeZoneId = null;
    let currentZoneBookings = [];

    let currentZonePricings = {};

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

            try {
                const response = await fetch(`/zone/${activeZoneId}/pricings`);
                if (!response.ok) throw new Error('Erreur lors du chargement des tarifs');

                currentZonePricings = await response.json();
                console.log('Tarifs :', currentZonePricings);
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

    let activeLocation = 'grande-salle';
    let bookingMode = 'hour';
    let selectedPeriodPreviewEvent = null;
    let currentViewType = 'dayGridMonth';

    const locationLabels = {
        'grande-salle': 'Grande salle',
        'studio-a': 'Studio A',
        'studio-b': 'Studio B',
        'exterieur': 'Extérieur'
    };

    const locationDescriptions = {
        'grande-salle': 'Grande salle polyvalente adaptée aux répétitions, ateliers et réservations à la journée.',
        'studio-a': 'Studio A, espace plus intimiste pour sessions techniques, enregistrements et petits groupes.',
        'studio-b': 'Studio B pensé pour les répétitions, essais techniques et occupations régulières en journée.',
        'exterieur': 'Espace extérieur adapté aux installations, tournages et événements ponctuels.'
    };

    const mockEventsByLocation = {
        'grande-salle': [
            { id: 'gs-1', title: 'Cours', start: '2026-07-22T08:00:00', end: '2026-07-22T09:00:00', allDay: false },
            { id: 'gs-2', title: 'Répétition', start: '2026-07-22T13:00:00', end: '2026-07-22T14:00:00', allDay: false },
            { id: 'gs-3', title: 'Privatisation journée', start: '2026-07-24', end: '2026-07-25', allDay: true }
        ],
        'studio-a': [
            { id: 'sa-1', title: 'Session audio', start: '2026-07-21T10:00:00', end: '2026-07-21T11:00:00', allDay: false },
            { id: 'sa-2', title: 'Studio fermé', start: '2026-07-26', end: '2026-07-27', allDay: true }
        ],
        'studio-b': [
            { id: 'sb-1', title: 'Répétition compagnie', start: '2026-07-22T08:00:00', end: '2026-07-22T09:00:00', allDay: false }
        ],
        'exterieur': [
            { id: 'ex-1', title: 'Tournage', start: '2026-07-20T08:00:00', end: '2026-07-20T09:00:00', allDay: false }
        ]
    };

    const dailyUsage = {};
    const allDayBlockedDates = new Set();

    function normalizeDate(dateLike) {
        const date = new Date(dateLike);
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function getEventsForActiveLocation() {
        return mockEventsByLocation[activeLocation] || [];
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
        return getEventsForActiveLocation().some(function (event) {
            if (!event.start) {
                return false;
            }

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

        return getEventsForActiveLocation().some(function (event) {
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

        if (hour >= 8 && hour < 12) {
            return { key: 'morning', label: 'Matin', start: '08:00', end: '12:00' };
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
                    start: `${dateStr}T08:00:00`,
                    end: `${dateStr}T12:00:00`,
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
        if (selectionPreview) {
            selectionPreview.innerHTML = content;
        }
    }

    function updateLocationDescription() {
        if (locationDescription && locationDescriptions[activeLocation]) {
            locationDescription.textContent = locationDescriptions[activeLocation];
        }
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
        const dateStr = normalizeDate(info.start);
        const now = new Date();

        if (info.start < now) {
            return false;
        }

        if (info.allDay) {
            return !hasAnyEventOnDate(dateStr);
        }

        if (bookingMode === 'period') {
            return false;
        }

        if (allDayBlockedDates.has(dateStr)) {
            return false;
        }

        if (selectionOverlapsExistingEvent(info)) {
            return false;
        }

        const startHour = info.start.getHours() + (info.start.getMinutes() / 60);
        const endHour = info.end.getHours() + (info.end.getMinutes() / 60);

        if (!(startHour >= 8 && endHour <= 20)) {
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

        const current = new Date(startIso);
        const dayNumber = current.getDay() || 7;
        const dayPricings = currentZonePricings[dayNumber];

        if (!dayPricings) return null;

        if (mode === 'period' && periodKey) {
            return dayPricings.period[periodKey] || null;
        }

        if (mode === 'hour' && endIso) {
            let totalFull = 0, totalReducedA = 0, totalReducedB = 0;
            let hasValidPricing = false;
            const end = new Date(endIso);

            while (current < end) {
                const loopDay = current.getDay() || 7;
                const hours = String(current.getHours()).padStart(2, '0');
                const minutes = String(current.getMinutes()).padStart(2, '0');
                const timeKey = `${hours}:${minutes}`;

                const prices = currentZonePricings[loopDay]?.hourly?.[timeKey];
                if (prices) {
                    totalFull += prices.full;
                    totalReducedA += prices.reducedA;
                    totalReducedB += prices.reducedB;
                    hasValidPricing = true;
                }
                current.setHours(current.getHours() + 1);
            }

            return hasValidPricing ? { full: totalFull, reducedA: totalReducedA, reducedB: totalReducedB } : null;
        }

        return null;
    }

    function updatePriceUI(prices) {
        const priceContainer = document.getElementById('price-container');
        const priceDisplay = document.getElementById('price-display');

        if (!priceContainer || !priceDisplay) return;

        if (!prices || prices.full === null || prices.full === undefined) {
            priceContainer.classList.add('hidden');
        } else {
            const selectedPrice = prices.full;

            const formattedPrice = selectedPrice.toLocaleString('fr-FR');
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
        slotMinTime: '08:00:00',
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
            startTime: '08:00',
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
            start: getTodayIsoString()
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
    updateLocationDescription();
    updateBookingModeUI();
    updateLegendVisibility();

    bookingModeLinks.forEach(function (button) {
        button.addEventListener('click', function () {
            bookingMode = button.dataset.mode;
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
            activeLocation = tab.dataset.location;
            clearPeriodPreviewEvent();

            locationTabs.forEach(function (item) {
                item.classList.remove('is-active', 'border-slate-200', 'bg-white', 'text-primary');
                item.classList.add('border-transparent', 'text-slate-500');
                item.setAttribute('aria-selected', 'false');
            });

            tab.classList.add('is-active', 'border-slate-200', 'bg-white', 'text-primary');
            tab.classList.remove('border-transparent', 'text-slate-500');
            tab.setAttribute('aria-selected', 'true');

            if (locationLabel && locationLabels[activeLocation]) {
                locationLabel.textContent = locationLabels[activeLocation];
            }

            updateLocationDescription();
            updatePreview('Aucune sélection pour le moment.');
            calendar.unselect();
            calendar.refetchEvents();
        });
    });
});
