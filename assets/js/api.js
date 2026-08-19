function replaceParameter(url, parameter, value) {
    return url.replace(`{${parameter}}`, encodeURIComponent(value));
}

export function createBookingApi(config) {
    return {
        async getZones(facilityId) {
            const url = replaceParameter(config.endpoints.zones, 'facilityId', facilityId);

            const response = await fetch(url);

            if (!response.ok) {
                throw new Error(config.texts.zonesLoadingError);
            }

            return response.json();
        },

        async getPricings(zoneId) {
            const url = replaceParameter(config.endpoints.pricings, 'zoneId', zoneId);

            const response = await fetch(url);

            if (!response.ok) {
                throw new Error(config.texts.pricingLoadingError);
            }

            return response.json();
        },

        async getBookings(zoneId) {
            const url = replaceParameter(config.endpoints.bookings, 'zoneId', zoneId);

            const response = await fetch(url);

            if (!response.ok) {
                throw new Error(config.texts.bookingsLoadingError);
            }

            return response.json();
        },

        async createBooking(payload) {
            const response = await fetch(config.endpoints.createBooking, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            });

            const result = await response.json();

            return {
                response,
                result
            };
        }
    };
}
