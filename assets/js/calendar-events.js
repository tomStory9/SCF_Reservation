import { normalizeDate } from './date-utils.js';

export function createCalendarEventService(state, config) {
    const dailyHoursLimit = 12;

    return {
        recomputeDailyUsage(events) {
            Object.keys(state.dailyUsage).forEach(key => {
                delete state.dailyUsage[key];
            });

            state.allDayBlockedDates.clear();

            events.forEach(event => {
                if (!event.start) {
                    return;
                }

                if (event.allDay) {
                    const start = new Date(event.start);
                    const end = event.end
                        ? new Date(event.end)
                        : new Date(event.start);

                    const cursor = new Date(start);

                    while (cursor < end) {
                        const date = normalizeDate(cursor);

                        state.dailyUsage[date] ??= {
                            usedHours: 0,
                            percentage: 0
                        };

                        state.allDayBlockedDates.add(date);
                        state.dailyUsage[date].usedHours =
                            dailyHoursLimit;
                        state.dailyUsage[date].percentage = 100;

                        cursor.setDate(cursor.getDate() + 1);
                    }

                    return;
                }

                const date = normalizeDate(event.start);

                state.dailyUsage[date] ??= {
                    usedHours: 0,
                    percentage: 0
                };

                if (event.end) {
                    const start = new Date(event.start);
                    const end = new Date(event.end);
                    const duration =
                        Math.max(0, end - start) / 36e5;

                    state.dailyUsage[date].usedHours += duration;
                    state.dailyUsage[date].percentage = Math.min(
                        100,
                        Math.round(
                            state.dailyUsage[date].usedHours /
                            dailyHoursLimit *
                            100
                        )
                    );
                }
            });
        },

        hasAnyEventOnDate(dateStr) {
            return state.currentZoneBookings.some(event => {
                if (!event.start) {
                    return false;
                }

                if (!event.allDay) {
                    return normalizeDate(event.start) === dateStr;
                }

                const start = new Date(event.start);
                const end = event.end
                    ? new Date(event.end)
                    : new Date(event.start);

                const cursor = new Date(start);

                while (cursor < end) {
                    if (normalizeDate(cursor) === dateStr) {
                        return true;
                    }

                    cursor.setDate(cursor.getDate() + 1);
                }

                return false;
            });
        },

        selectionOverlapsExistingEvent(selectInfo) {
            if (
                state.bookingMode !== 'hour' ||
                selectInfo.allDay
            ) {
                return false;
            }

            return state.currentZoneBookings.some(event => {
                if (
                    event.allDay ||
                    !event.start ||
                    !event.end
                ) {
                    return false;
                }

                const eventStart =
                    new Date(event.start).getTime();

                const eventEnd =
                    new Date(event.end).getTime();

                const selectionStart =
                    selectInfo.start.getTime();

                const selectionEnd =
                    selectInfo.end.getTime();

                return (
                    selectionStart < eventEnd &&
                    selectionEnd > eventStart
                );
            });
        },

        getUsageClass(dateStr, currentViewType) {
            if (currentViewType !== 'dayGridMonth') {
                return '';
            }

            const usage = state.dailyUsage[dateStr];

            if (!usage || !usage.percentage) {
                return '';
            }

            if (usage.percentage >= 100) {
                return 'day-usage-100';
            }

            if (usage.percentage >= 75) {
                return 'day-usage-75';
            }

            if (usage.percentage >= 50) {
                return 'day-usage-50';
            }

            if (usage.percentage >= 25) {
                return 'day-usage-25';
            }

            return '';
        }
    };
}