export function loadCalendarConfig() {
    const configElement = document.getElementById('calendar-config');

    if (!configElement) {
        throw new Error('Missing #calendar-config element');
    }

    const config = JSON.parse(configElement.textContent);

    return {
        ...config
    };
}

export function loadUserBookingData() {
    const element = document.getElementById('user-booking-data');

    if (!element) {
        return {
            freeHours: 0
        };
    }

    return JSON.parse(element.textContent);
}
