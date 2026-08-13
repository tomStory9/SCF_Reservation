export function createBookingState() {
    return {
        activeZoneId: null,
        currentZoneBookings: [],
        currentZonePricings: {},
        currentSelection: null,

        bookingMode: 'hour',
        selectedPeriodPreviewEvent: null,
        currentViewType: 'dayGridMonth',

        dailyUsage: {},
        allDayBlockedDates: new Set()
    };
}