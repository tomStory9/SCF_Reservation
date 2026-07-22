import '../styles/reservation-calendar.css';

import { Calendar } from '@fullcalendar/core';
import frLocale from '@fullcalendar/core/locales/fr';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';

document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('calendar-holder');
    const calendarLegend = document.getElementById('calendar-legend');
    const locationTabs = document.querySelectorAll('.location-tab');
    const bookingModeLinks = document.querySelectorAll('.booking-mode-link');
    const locationLabel = document.getElementById('selected-location-label');
    const locationDescription = document.getElementById('location-description');
    const selectionPreview = document.getElementById('selection-preview');
    const activeBookingModeLabel = document.getElementById('active-booking-mode-label');

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
        events: function (fetchInfo, successCallback) {
            const baseEvents = getEventsForActiveLocation();
            recomputeDailyUsage(baseEvents);
            successCallback(baseEvents.concat(getBackgroundPeriodEvents(fetchInfo)));
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
                updatePreview(
                    '<span class="font-semibold text-secondary">Jour sélectionné :</span> ' +
                    '<span class="font-semibold text-primary">' + info.dateStr + '</span>'
                );
                return;
            }

            if (bookingMode === 'period' && calendar.view.type === 'timeGridWeek') {
                const period = getPeriodFromDate(info.date);

                if (!period) {
                    return;
                }

                const dateStr = normalizeDate(info.date);

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
                    '<br><span class="text-state">Date : ' + dateStr + '</span>' +
                    '<br><span class="text-state">Horaires : ' + period.start + ' → ' + period.end + '</span>' +
                    '<br><span class="text-state">Lieu : ' + locationLabels[activeLocation] + '</span>'
                );
            }
        },
        selectAllow: function (selectInfo) {
            return selectionAllowed(selectInfo);
        },
        select: function (info) {
            const dateStr = normalizeDate(info.start);

            if (info.allDay) {
                if (hasAnyEventOnDate(dateStr)) {
                    calendar.unselect();
                    alert('Impossible de créer un événement journée complète : un événement existe déjà sur cette journée.');
                    return;
                }

                clearPeriodPreviewEvent();
                calendar.refetchEvents();

                updatePreview(
                    '<span class="font-semibold text-secondary">Sélection all day :</span> ' +
                    '<span class="font-semibold text-primary">' + info.startStr + '</span>' +
                    '<br><span class="text-state">Lieu : ' + locationLabels[activeLocation] + '</span>'
                );

                return;
            }

            if (bookingMode === 'period') {
                calendar.unselect();
                return;
            }

            if (allDayBlockedDates.has(dateStr)) {
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

            const startHour = info.startStr.slice(11, 16);
            const endHour = info.endStr.slice(11, 16);
            const durationMs = info.end.getTime() - info.start.getTime();
            const durationHours = durationMs / (60 * 60 * 1000);

            updatePreview(
                '<span class="font-semibold text-secondary">Créneau horaire :</span> ' +
                '<span class="font-semibold text-primary">' + info.startStr + '</span> → ' +
                '<span class="font-semibold text-primary">' + info.endStr + '</span>' +
                '<br><span class="text-state">Plage : ' + startHour + ' → ' + endHour + '</span>' +
                '<br><span class="text-state">Durée : ' + durationHours + ' heure(s)</span>' +
                '<br><span class="text-state">Lieu : ' + locationLabels[activeLocation] + '</span>'
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