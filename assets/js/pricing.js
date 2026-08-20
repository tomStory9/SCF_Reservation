import { getDayNumber } from './date-utils.js';

export function createPricingService(getPricings, getFreeHours) {
    return {
        getPricesForSelection(startIso, endIso, mode, periodKey = null) {
            const pricings = getPricings();

            if (!startIso || !pricings || Object.keys(pricings).length === 0) {
                return null;
            }

            const freeHours = Number(getFreeHours() || 0);
            const current = parseCalendarDate(startIso);
            const dayNumber = getDayNumber(current);
            const dayPricings = pricings[dayNumber];

            if (!dayPricings) {
                return null;
            }

            if (mode === 'period' && periodKey) {
                let basePrice = dayPricings.period?.[periodKey];

                if (typeof basePrice === 'object' && basePrice !== null) {
                    basePrice = basePrice.price;
                }

                if (basePrice === undefined) {
                    return null;
                }

                return {
                    price: calculateDiscountedPrice(basePrice, freeHours, 4),
                    basePrice
                };
            }

            if (mode === 'hour' && endIso) {
                return getHourlyPrice(
                    current,
                    parseCalendarDate(endIso),
                    dayPricings,
                    pricings,
                    freeHours
                );
            }

            return null;
        }
    };
}

function parseCalendarDate(value) {
    if (value.length === 10) {
        const [year, month, day] = value.split('-');

        return new Date(Number(year), Number(month) - 1, Number(day), 0, 0, 0);
    }

    return new Date(value);
}

function calculateDiscountedPrice(basePrice, freeHours, totalHours) {
    if (freeHours >= totalHours) {
        return 0;
    }

    if (freeHours > 0) {
        return Math.round(basePrice * ((totalHours - freeHours) / totalHours));
    }

    return Math.round(basePrice);
}

function getHourlyPrice(start, end, initialDayPricings, allPricings, freeHours) {
    let current = new Date(start);
    let basePrice = 0;
    let validHoursCount = 0;

    while (current < end) {
        const dayNumber = getDayNumber(current);
        const hours = String(current.getHours()).padStart(2, '0');
        const minutes = String(current.getMinutes()).padStart(2, '0');
        const timeKey = `${hours}:${minutes}`;

        const hourlyPrice = allPricings[dayNumber]?.hourly?.[timeKey];

        if (hourlyPrice !== undefined && hourlyPrice !== null) {
            const price = typeof hourlyPrice === 'object' ? hourlyPrice.price : hourlyPrice;

            if (!Number.isNaN(Number(price))) {
                basePrice += Number(price);
                validHoursCount++;
            }
        }

        current.setHours(current.getHours() + 1);
    }

    if (validHoursCount === 0) {
        return null;
    }

    return {
        price: calculateDiscountedPrice(basePrice, freeHours, validHoursCount),
        basePrice
    };
}
