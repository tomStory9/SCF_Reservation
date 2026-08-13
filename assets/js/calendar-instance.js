import { Calendar } from '@fullcalendar/core';
import frLocale from '@fullcalendar/core/locales/fr';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';

import {
    getTodayIsoString,
    normalizeDate,
    parseCleanIso
} from './date-utils.js';


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

    let calendar = null;

    async function notifySelectionChange() {
        if (
            typeof onSelectionChange !==
            'function'
        ) {
            return;
        }

        await onSelectionChange(
            state.currentSelection
        );
    }

    function getPeriodFromDate(date) {
        const hour = date.getHours();

        if (hour >= 9 && hour < 13) {
            return {
                key: 'morning',
                label: texts.morning,
                start: '09:00',
                end: '13:00'
            };
        }

        if (hour >= 13 && hour < 17) {
            return {
                key: 'afternoon',
                label: texts.afternoon,
                start: '13:00',
                end: '17:00'
            };
        }

        if (hour >= 17 && hour < 21) {
            return {
                key: 'evening',
                label: texts.evening,
                start: '17:00',
                end: '21:00'
            };
        }

        return null;
    }

    function getBackgroundPeriodEvents(
        fetchInfo
    ) {
        if (
            state.bookingMode !== 'period' ||
            !calendar?.view ||
            calendar.view.type !== 'timeGridWeek'
        ) {
            return [];
        }

        const events = [];
        const cursor = new Date(
            fetchInfo.start
        );

        while (
            cursor < fetchInfo.end
        ) {
            const date =
                normalizeDate(cursor);

            events.push(
                {
                    id: `bg-morning-${date}`,
                    start: `${date}T09:00:00`,
                    end: `${date}T13:00:00`,
                    display: 'background',
                    classNames: [
                        'fc-bg-period-morning'
                    ]
                },
                {
                    id: `bg-afternoon-${date}`,
                    start: `${date}T13:00:00`,
                    end: `${date}T17:00:00`,
                    display: 'background',
                    classNames: [
                        'fc-bg-period-afternoon'
                    ]
                },
                {
                    id: `bg-evening-${date}`,
                    start: `${date}T17:00:00`,
                    end: `${date}T21:00:00`,
                    display: 'background',
                    classNames: [
                        'fc-bg-period-evening'
                    ]
                }
            );

            cursor.setDate(
                cursor.getDate() + 1
            );
        }

        if (
            state.selectedPeriodPreviewEvent
        ) {
            events.push(
                state.selectedPeriodPreviewEvent
            );
        }

        return events;
    }

    function selectionAllowed(info) {
        const startDate =
            normalizeDate(info.start);

        const endDate =
            normalizeDate(
                new Date(
                    info.end.getTime() - 1
                )
            );

        if (
            startDate !== endDate
        ) {
            return false;
        }

        if (
            info.start < new Date()
        ) {
            return false;
        }

        if (info.allDay) {
            return !eventService.hasAnyEventOnDate(
                startDate
            );
        }

        if (
            state.bookingMode === 'period'
        ) {
            return false;
        }

        if (
            state.allDayBlockedDates.has(
                startDate
            )
        ) {
            return false;
        }

        if (
            eventService.selectionOverlapsExistingEvent(
                info
            )
        ) {
            return false;
        }

        const startHour =
            info.start.getHours() +
            info.start.getMinutes() / 60;

        const endHour =
            info.end.getHours() +
            info.end.getMinutes() / 60;

        if (
            startHour < 8 ||
            endHour > 21
        ) {
            return false;
        }

        const duration =
            info.end.getTime() -
            info.start.getTime();

        return (
            duration >=
            60 * 60 * 1000
        );
    }

    calendar = new Calendar(
        calendarEl,
        {
            plugins: [
                dayGridPlugin,
                timeGridPlugin,
                interactionPlugin
            ],

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

            selectOverlap: event =>
                event.display === 'background',

            eventOverlap: false,

            businessHours: {
                daysOfWeek: [
                    0,
                    1,
                    2,
                    3,
                    4,
                    5,
                    6
                ],
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
                start: getTodayIsoString(),
                end: config.maxDate
            },

            events: async (
                fetchInfo,
                successCallback,
                failureCallback
            ) => {
                if (
                    !state.activeZoneId
                ) {
                    state.currentZoneBookings =
                        [];

                    successCallback(
                        getBackgroundPeriodEvents(
                            fetchInfo
                        )
                    );

                    return;
                }

                try {
                    state.currentZoneBookings =
                        await api.getBookings(
                            state.activeZoneId
                        );

                    eventService.recomputeDailyUsage(
                        state.currentZoneBookings
                    );

                    successCallback([
                        ...state.currentZoneBookings,
                        ...getBackgroundPeriodEvents(
                            fetchInfo
                        )
                    ]);
                } catch (error) {
                    console.error(
                        '[reservation] Erreur chargement calendrier :',
                        error
                    );

                    failureCallback(
                        error
                    );
                }
            },

            dayCellClassNames: arg => {
                const date =
                    normalizeDate(arg.date);

                const usageClass =
                    eventService.getUsageClass(
                        date,
                        state.currentViewType
                    );

                return usageClass
                    ? [usageClass]
                    : [];
            },

            dayCellDidMount: arg => {
                if (
                    state.currentViewType !==
                    'dayGridMonth'
                ) {
                    arg.el.removeAttribute(
                        'title'
                    );

                    return;
                }

                const date =
                    normalizeDate(arg.date);

                const usage =
                    state.dailyUsage[date];

                if (
                    usage &&
                    usage.usedHours > 0
                ) {
                    arg.el.title =
                        `${usage.usedHours.toFixed(1)}${texts.usedHours}`;
                }
            },

            datesSet: info => {
                state.currentViewType =
                    info.view.type;

                calendar.refetchEvents();
            },

            dateClick: async info => {
                if (
                    calendar.view.type ===
                    'dayGridMonth'
                ) {
                    calendar.changeView(
                        'timeGridWeek',
                        info.dateStr
                    );

                    const data =
                        parseCleanIso(
                            info.dateStr
                        );

                    ui.updatePreview(
                        `<span class="font-semibold text-secondary">${texts.selectedDay}</span> ` +
                        `<span class="font-semibold text-primary">${data.dateFr}</span>`
                    );

                    return;
                }

                if (
                    state.bookingMode !==
                    'period' ||
                    calendar.view.type !==
                    'timeGridWeek'
                ) {
                    return;
                }

                const period =
                    getPeriodFromDate(
                        info.date
                    );

                if (!period) {
                    return;
                }

                const date =
                    normalizeDate(info.date);

                if (
                    eventService.hasAnyEventOnDate(
                        date
                    )
                ) {
                    alert(
                        texts.periodConflict
                    );

                    return;
                }

                const prices =
                    pricingService.getPricesForSelection(
                        info.dateStr,
                        null,
                        'period',
                        period.key
                    );

                const data =
                    parseCleanIso(
                        info.dateStr
                    );

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
                    title:
                        texts.selectedPeriodEvent.replace(
                            '{period}',
                            period.label
                        ),
                    start:
                        `${date}T${period.start}:00`,
                    end:
                        `${date}T${period.end}:00`,
                    allDay: false,
                    classNames: [
                        'period-preview-event'
                    ]
                };

                ui.updatePrice(
                    prices
                );

                await notifySelectionChange();

                calendar.refetchEvents();

                ui.updatePreview(
                    `<span class="font-semibold text-secondary">${texts.selectedPeriod}</span> ` +
                    `<span class="font-semibold text-primary">${period.label}</span>` +
                    `<br><span class="text-state">${texts.date} ${data.dateFr}</span>` +
                    `<br><span class="text-state">${texts.hours} ${period.start} → ${period.end}</span>` +
                    `<br><span class="text-state">${texts.location} ${getSelectedZoneName()}</span>`
                );
            },

            selectAllow: selectionAllowed,

            select: async info => {
                const startData =
                    parseCleanIso(
                        info.startStr
                    );

                const endData =
                    parseCleanIso(
                        info.endStr
                    );

                if (
                    state.bookingMode ===
                    'period'
                ) {
                    calendar.unselect();

                    return;
                }

                if (info.allDay) {
                    if (
                        eventService.hasAnyEventOnDate(
                            startData.dateIso
                        )
                    ) {
                        calendar.unselect();

                        alert(
                            texts.fullDayConflict
                        );

                        return;
                    }

                    state.currentSelection = {
                        bookingMode: 'hour',
                        startDate:
                            startData.dateIso,
                        endDate:
                            startData.dateIso,
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

                if (
                    state.allDayBlockedDates.has(
                        startData.dateIso
                    )
                ) {
                    calendar.unselect();

                    alert(
                        texts.blockedDay
                    );

                    return;
                }

                if (
                    eventService.selectionOverlapsExistingEvent(
                        info
                    )
                ) {
                    calendar.unselect();

                    alert(
                        texts.overlappingEvent
                    );

                    return;
                }

                const prices =
                    pricingService.getPricesForSelection(
                        info.startStr,
                        info.endStr,
                        'hour'
                    );

                const duration =
                    (
                        info.end.getTime() -
                        info.start.getTime()
                    ) /
                    (60 * 60 * 1000);

                state.currentSelection = {
                    bookingMode: 'hour',
                    startDate:
                        startData.dateIso,
                    endDate:
                        endData.dateIso,
                    startTime:
                        startData.time,
                    endTime:
                        endData.time,
                    periodKey: null,
                    isFullDay: false,
                    guestNb: getGuestCount(),
                    price: prices?.price ?? 0,
                    basePrice: prices?.basePrice ?? 0
                };

                ui.updatePrice(
                    prices
                );

                await notifySelectionChange();

                calendar.refetchEvents();

                ui.updatePreview(
                    `<span class="font-semibold text-secondary">${texts.hourlySlot}</span> ` +
                    `<span class="font-semibold text-primary">${startData.time} → ${endData.time}</span>` +
                    `<br><span class="text-state">${texts.date} ${startData.dateFr}</span>` +
                    `<br><span class="text-state">${texts.duration} ${duration} heure(s)</span>` +
                    `<br><span class="text-state">${texts.location} ${getSelectedZoneName()}</span>`
                );
            }
        }
    );

    return calendar;
}