import { Calendar } from '@fullcalendar/core';
import frLocale from '@fullcalendar/core/locales/fr';
import enGbLocale from '@fullcalendar/core/locales/en-gb';
import jaLocale from '@fullcalendar/core/locales/ja';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';

import { normalizeDate, parseCleanIso } from './date-utils.js';

const REQUIRED_PERIOD_KEYS = ['morning', 'afternoon', 'evening'];

const CALENDAR_SLOT_MIN_TIME = '09:00:00';
const CALENDAR_SLOT_MAX_TIME = '21:00:00';

const HOURLY_BOOKING_MIN_TIME = '09:00';
const HOURLY_BOOKING_MAX_TIME = '21:00';

function timeToMinutes(time) {
    if (typeof time !== 'string') {
        return null;
    }

    const match = time.match(/^(\d{2}):(\d{2})(?::\d{2})?$/);

    if (!match) {
        return null;
    }

    const hours = Number(match[1]);
    const minutes = Number(match[2]);

    if (
        !Number.isInteger(hours) ||
        !Number.isInteger(minutes) ||
        hours < 0 ||
        hours > 23 ||
        minutes < 0 ||
        minutes > 59
    ) {
        return null;
    }

    return hours * 60 + minutes;
}

function normalizeTime(time) {
    if (typeof time !== 'string') {
        return null;
    }

    const minutes = timeToMinutes(time);

    if (minutes === null) {
        return null;
    }

    return time.substring(0, 5);
}

function getPeriodLabel(key, texts) {
    const labels = {
        morning: texts.morning,
        afternoon: texts.afternoon,
        evening: texts.evening
    };

    return labels[key] ?? key;
}

function getPeriodClassName(key) {
    const classNames = {
        morning: 'fc-bg-period-morning',
        afternoon: 'fc-bg-period-afternoon',
        evening: 'fc-bg-period-evening'
    };

    return classNames[key] ?? '';
}

function buildPeriodsFromConfig(config, texts) {
    const rawPeriods = config?.periods;

    if (!rawPeriods || typeof rawPeriods !== 'object' || Array.isArray(rawPeriods)) {
        return {
            periods: {},
            errors: ['period_config_invalid']
        };
    }

    const periods = {};
    const errors = [];

    for (const key of REQUIRED_PERIOD_KEYS) {
        const rawPeriod = rawPeriods[key];

        if (!rawPeriod || typeof rawPeriod !== 'object') {
            errors.push(`period_missing_${key}`);
            continue;
        }

        const start = normalizeTime(rawPeriod.start);
        const end = normalizeTime(rawPeriod.end);

        if (!start || !end) {
            errors.push(`period_invalid_time_${key}`);
            continue;
        }

        const startMinutes = timeToMinutes(start);
        const endMinutes = timeToMinutes(end);

        if (endMinutes <= startMinutes) {
            errors.push(`period_invalid_range_${key}`);
            continue;
        }

        periods[key] = {
            key,
            label: getPeriodLabel(key, texts),
            start,
            end,
            className: getPeriodClassName(key)
        };
    }

    return { periods, errors };
}

function replaceTextPattern(pattern, replacements = {}) {
    return Object.entries(replacements).reduce(
        (text, [key, value]) => text.replaceAll(`{${key}}`, String(value)),
        pattern ?? ''
    );
}

export function createCalendar({
    calendarEl,
    config,
    state,
    api,
    eventService,
    pricingService,
    ui,
    getSelectedZoneName,
    getGuestCount,
    onSelectionChange
}) {
    const texts = config.texts;

    const calendarLocales = {
        fr: frLocale,
        en: enGbLocale,
        ja: jaLocale
    };

    const { periods, errors: periodConfigErrors } = buildPeriodsFromConfig(config, texts);

    const periodsAreValid =
        periodConfigErrors.length === 0 &&
        REQUIRED_PERIOD_KEYS.every((key) => Boolean(periods[key]));

    let calendar = null;

    const blockedDays = new Set();

    const blockedPeriodsArray = Array.isArray(config.blockedPeriods) ? config.blockedPeriods : [];

    blockedPeriodsArray.forEach((blockedPeriod) => {
        const startStr = blockedPeriod.start.substring(0, 10);
        const endStr = blockedPeriod.end.substring(0, 10);

        const start = new Date(`${startStr}T00:00:00`);
        const end = new Date(`${endStr}T00:00:00`);
        const cursor = new Date(start);

        while (cursor <= end) {
            const year = cursor.getFullYear();
            const month = String(cursor.getMonth() + 1).padStart(2, '0');
            const day = String(cursor.getDate()).padStart(2, '0');

            blockedDays.add(`${year}-${month}-${day}`);
            cursor.setDate(cursor.getDate() + 1);
        }
    });

    function isDateBlocked(dateStr) {
        return blockedDays.has(dateStr.substring(0, 10));
    }

    async function notifySelectionChange() {
        if (typeof onSelectionChange !== 'function') {
            return;
        }

        await onSelectionChange(state.currentSelection);
    }

    function getPeriodFromDate(date) {
        if (!periodsAreValid) {
            return null;
        }

        const currentMinutes = date.getHours() * 60 + date.getMinutes();

        for (const key of REQUIRED_PERIOD_KEYS) {
            const period = periods[key];

            if (!period) {
                continue;
            }

            const startMinutes = timeToMinutes(period.start);
            const endMinutes = timeToMinutes(period.end);

            if (currentMinutes >= startMinutes && currentMinutes < endMinutes) {
                return period;
            }
        }

        return null;
    }

    function getBackgroundPeriodEvents(fetchInfo) {
        if (
            state.bookingMode !== 'period' ||
            !calendar?.view ||
            calendar.view.type !== 'timeGridWeek' ||
            !periodsAreValid
        ) {
            return [];
        }

        const events = [];
        const cursor = new Date(fetchInfo.start);

        while (cursor < fetchInfo.end) {
            const date = normalizeDate(cursor);

            for (const key of REQUIRED_PERIOD_KEYS) {
                const period = periods[key];

                events.push({
                    id: `bg-${key}-${date}`,
                    start: `${date}T${period.start}:00`,
                    end: `${date}T${period.end}:00`,
                    display: 'background',
                    classNames: [period.className]
                });
            }

            cursor.setDate(cursor.getDate() + 1);
        }

        if (state.selectedPeriodPreviewEvent) {
            events.push(state.selectedPeriodPreviewEvent);
        }

        return events;
    }

    function selectionAllowed(info) {
        let cursor = new Date(info.start);
        const selectionEnd = new Date(info.end.getTime() - 1);

        while (cursor <= selectionEnd) {
            const year = cursor.getFullYear();
            const month = String(cursor.getMonth() + 1).padStart(2, '0');
            const day = String(cursor.getDate()).padStart(2, '0');

            if (isDateBlocked(`${year}-${month}-${day}`)) {
                return false;
            }

            cursor.setDate(cursor.getDate() + 1);
        }

        const startDate = normalizeDate(info.start);
        const endDate = normalizeDate(selectionEnd);

        if (startDate !== endDate) {
            return false;
        }

        if (info.start < new Date()) {
            return false;
        }

        if (info.allDay) {
            return !eventService.hasAnyEventOnDate(startDate);
        }

        if (state.bookingMode === 'period') {
            return false;
        }

        if (state.allDayBlockedDates.has(startDate)) {
            return false;
        }

        if (eventService.selectionOverlapsExistingEvent(info)) {
            return false;
        }

        const startMinutes = info.start.getHours() * 60 + info.start.getMinutes();
        const endMinutes = info.end.getHours() * 60 + info.end.getMinutes();

        const minAllowedMinutes = timeToMinutes(HOURLY_BOOKING_MIN_TIME);
        const maxAllowedMinutes = timeToMinutes(HOURLY_BOOKING_MAX_TIME);

        if (startMinutes < minAllowedMinutes || endMinutes > maxAllowedMinutes) {
            return false;
        }

        const duration = info.end.getTime() - info.start.getTime();

        return duration >= 60 * 60 * 1000;
    }

    calendar = new Calendar(calendarEl, {
        plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],

        initialView: 'dayGridMonth',
        locale: calendarLocales[config.locale] ?? enGbLocale,
        firstDay: 1,

        height: 'auto',
        contentHeight: 'auto',
        expandRows: false,

        nowIndicator: true,
        selectable: true,
        editable: false,
        weekends: true,

        allDaySlot: true,

        slotMinTime: CALENDAR_SLOT_MIN_TIME,
        slotMaxTime: CALENDAR_SLOT_MAX_TIME,

        slotDuration: '01:00:00',
        snapDuration: '01:00:00',

        selectMirror: true,
        unselectAuto: false,

        selectOverlap: (event) => event.display === 'background',
        eventOverlap: false,

        businessHours: {
            daysOfWeek: [0, 1, 2, 3, 4, 5, 6],
            startTime: HOURLY_BOOKING_MIN_TIME,
            endTime: HOURLY_BOOKING_MAX_TIME
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
            today: texts.today,
            month: texts.month,
            week: texts.week
        },

        displayEventTime: true,
        displayEventEnd: true,

        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        },

        validRange: {
            start: new Date(Date.now() + (config.minDays || 0) * 24 * 60 * 60 * 1000)
                .toISOString()
                .split('T')[0],
            end: config.maxDate
        },

        events: async (fetchInfo, successCallback, failureCallback) => {
            const blockedEvents = blockedPeriodsArray.map((blockedPeriod) => {
                const startStr = blockedPeriod.start.substring(0, 10);
                const endStr = blockedPeriod.end.substring(0, 10);

                const [endYear, endMonth, endDay] = endStr.split('-');

                const endDateObj = new Date(Number(endYear), Number(endMonth) - 1, Number(endDay));

                endDateObj.setDate(endDateObj.getDate() + 1);

                const exclusiveEndStr = [
                    endDateObj.getFullYear(),
                    String(endDateObj.getMonth() + 1).padStart(2, '0'),
                    String(endDateObj.getDate()).padStart(2, '0')
                ].join('-');

                return {
                    id: `blocked-${blockedPeriod.id}`,
                    start: startStr,
                    end: exclusiveEndStr,
                    title: texts.blockedPeriod,
                    allDay: true,
                    color: '#94a3b8',
                    textColor: '#ffffff',
                    classNames: ['pointer-events-none', 'fc-blocked-banner']
                };
            });

            if (!state.activeZoneId) {
                state.currentZoneBookings = [];

                successCallback([...getBackgroundPeriodEvents(fetchInfo), ...blockedEvents]);

                return;
            }

            try {
                state.currentZoneBookings = await api.getBookings(state.activeZoneId);

                eventService.recomputeDailyUsage(state.currentZoneBookings);

                successCallback([
                    ...state.currentZoneBookings,
                    ...getBackgroundPeriodEvents(fetchInfo),
                    ...blockedEvents
                ]);
            } catch (error) {
                failureCallback(error);
            }
        },

        dayCellClassNames: (arg) => {
            const date = normalizeDate(arg.date);

            const usageClass = eventService.getUsageClass(date, state.currentViewType);

            return usageClass ? [usageClass] : [];
        },

        dayCellDidMount: (arg) => {
            if (state.currentViewType !== 'dayGridMonth') {
                arg.el.removeAttribute('title');
                return;
            }

            const date = normalizeDate(arg.date);
            const usage = state.dailyUsage[date];

            if (usage && usage.usedHours > 0) {
                arg.el.title = `${usage.usedHours.toFixed(1)}${texts.usedHours}`;
            }
        },

        datesSet: (info) => {
            state.currentViewType = info.view.type;
            calendar.refetchEvents();
        },

        dateClick: async (info) => {
            const clickedDateStr = info.dateStr.substring(0, 10);

            if (isDateBlocked(clickedDateStr)) {
                return;
            }

            if (calendar.view.type === 'dayGridMonth') {
                calendar.changeView('timeGridWeek', info.dateStr);

                const data = parseCleanIso(info.dateStr);

                ui.updatePreview(
                    `<span class="font-semibold text-secondary">${texts.selectedDay}</span> ` +
                        `<span class="font-semibold text-primary">${data.dateFr}</span>`
                );

                return;
            }

            if (state.bookingMode !== 'period' || calendar.view.type !== 'timeGridWeek') {
                return;
            }

            if (!periodsAreValid) {
                alert(texts.periodConfigUnavailable);
                return;
            }

            const period = getPeriodFromDate(info.date);

            if (!period) {
                return;
            }

            const date = normalizeDate(info.date);

            if (eventService.hasAnyEventOnDate(date)) {
                alert(texts.periodConflict);
                return;
            }

            const prices = pricingService.getPricesForSelection(
                info.dateStr,
                null,
                'period',
                period.key
            );

            const data = parseCleanIso(info.dateStr);

            state.currentSelection = {
                bookingMode: 'period',
                startDate: data.dateIso,
                endDate: data.dateIso,
                startTime: period.start,
                endTime: period.end,
                periodKey: period.key,
                isFullDay: false,
                guestNb: getGuestCount(),
                price: prices?.price ?? 0,
                basePrice: prices?.basePrice ?? 0
            };

            state.selectedPeriodPreviewEvent = {
                id: `period-preview-${date}-${period.key}`,
                title: texts.selectedPeriodEvent.replace('{period}', period.label),
                start: `${date}T${period.start}:00`,
                end: `${date}T${period.end}:00`,
                allDay: false,
                classNames: ['period-preview-event', period.className]
            };

            ui.updatePrice(prices);
            await notifySelectionChange();
            calendar.refetchEvents();

            const priceDetailText = formatPriceDetail(prices, texts);
            const freeRemainingText = formatFreeHoursRemaining(prices, texts);

            ui.updatePreview(
                `<span class="font-semibold text-secondary">${texts.selectedPeriod}</span> ` +
                    `<span class="font-semibold text-primary">${period.label}</span>` +
                    `<br><span class="text-state">${texts.date} ${data.dateFr}</span>` +
                    `<br><span class="text-state">${texts.hours} ${period.start} → ${period.end}</span>` +
                    `<br><span class="text-state">${texts.location} ${getSelectedZoneName()}</span>` +
                    (freeRemainingText
                        ? `<br><span class="text-state font-semibold text-accent">${freeRemainingText}</span>`
                        : '') +
                    (priceDetailText
                        ? `<br><span class="text-state font-semibold text-primary">${priceDetailText}</span>`
                        : '')
            );
        },

        selectAllow: selectionAllowed,

        select: async (info) => {
            const startData = parseCleanIso(info.startStr);
            const endData = parseCleanIso(info.endStr);

            if (state.bookingMode === 'period') {
                calendar.unselect();
                return;
            }

            if (info.allDay) {
                if (eventService.hasAnyEventOnDate(startData.dateIso)) {
                    calendar.unselect();
                    alert(texts.fullDayConflict);
                    return;
                }

                state.currentSelection = {
                    bookingMode: 'hour',
                    startDate: startData.dateIso,
                    endDate: startData.dateIso,
                    startTime: '00:00',
                    endTime: '23:59',
                    periodKey: null,
                    isFullDay: true,
                    guestNb: getGuestCount(),
                    price: 0,
                    basePrice: 0
                };

                await notifySelectionChange();

                ui.updatePreview(
                    `<span class="font-semibold text-secondary">${texts.fullDaySelection}</span> ` +
                        `<span class="font-semibold text-primary">${startData.dateFr}</span>` +
                        `<br><span class="text-state">${texts.location} ${getSelectedZoneName()}</span>`
                );

                return;
            }

            if (state.allDayBlockedDates.has(startData.dateIso)) {
                calendar.unselect();
                alert(texts.blockedDay);
                return;
            }

            if (eventService.selectionOverlapsExistingEvent(info)) {
                calendar.unselect();
                alert(texts.overlappingEvent);
                return;
            }

            const prices = pricingService.getPricesForSelection(info.startStr, info.endStr, 'hour');

            const duration = (info.end.getTime() - info.start.getTime()) / (60 * 60 * 1000);

            state.currentSelection = {
                bookingMode: 'hour',
                startDate: startData.dateIso,
                endDate: endData.dateIso,
                startTime: startData.time,
                endTime: endData.time,
                periodKey: null,
                isFullDay: false,
                guestNb: getGuestCount(),
                price: prices?.price ?? 0,
                basePrice: prices?.basePrice ?? 0
            };

            ui.updatePrice(prices);
            await notifySelectionChange();
            calendar.refetchEvents();

            const priceDetailText = formatPriceDetail(prices, texts);
            const freeRemainingText = formatFreeHoursRemaining(prices, texts);

            ui.updatePreview(
                `<span class="font-semibold text-secondary">${texts.hourlySlot}</span> ` +
                    `<span class="font-semibold text-primary">${startData.time} → ${endData.time}</span>` +
                    `<br><span class="text-state">${texts.date} ${startData.dateFr}</span>` +
                    `<br><span class="text-state">${texts.duration} ${duration} ${texts.hoursUnit}</span>` +
                    `<br><span class="text-state">${texts.location} ${getSelectedZoneName()}</span>` +
                    (freeRemainingText
                        ? `<br><span class="text-state font-semibold text-accent">${freeRemainingText}</span>`
                        : '') +
                    (priceDetailText
                        ? `<br><span class="text-state font-semibold text-primary">${priceDetailText}</span>`
                        : '')
            );
        }
    });

    return calendar;
}

function formatPriceDetail(prices, texts) {
    if (!prices) {
        return '';
    }

    const freeUsed = prices.freeHoursUsed ?? 0;
    const paidHours = prices.paidHours ?? 0;
    const paidPrice = prices.price ?? 0;

    const parts = [];

    if (freeUsed > 0) {
        parts.push(replaceTextPattern(texts.freeHoursUsedPattern, { hours: freeUsed }));
    }

    if (paidHours > 0) {
        parts.push(
            replaceTextPattern(texts.paidHoursPattern, {
                hours: paidHours,
                price: paidPrice
            })
        );
    } else if (paidPrice > 0) {
        parts.push(replaceTextPattern(texts.pricePattern, { price: paidPrice }));
    }

    if (parts.length === 0) {
        return texts.fullyFree;
    }

    return parts.join(' + ');
}

function formatFreeHoursRemaining(prices, texts) {
    if (!prices) {
        return '';
    }

    const freeRemaining = prices.freeHoursRemaining ?? 0;
    const freeTotal = prices.freeHours ?? 0;

    if (freeTotal <= 0) {
        return '';
    }

    return replaceTextPattern(texts.freeHoursRemainingPattern, {
        remaining: freeRemaining,
        total: freeTotal
    });
}
