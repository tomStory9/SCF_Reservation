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

                const totalHours = 4;
                const detail = calculateDiscountedPriceDetail(basePrice, freeHours, totalHours);

                return {
                    price: detail.price,
                    basePrice: detail.basePrice,
                    freeHours: detail.freeHours,
                    freeHoursUsed: detail.freeHoursUsed,
                    paidHours: detail.paidHours,
                    paidPrice: detail.paidPrice,
                    freeHoursRemaining: Math.max(0, detail.freeHours - detail.freeHoursUsed)
                };
            }

            if (mode === 'hour' && endIso) {
                const hourlyResult = getHourlyPrice(
                    current,
                    parseCalendarDate(endIso),
                    dayPricings,
                    pricings,
                    freeHours
                );

                if (!hourlyResult) {
                    return null;
                }

                return {
                    price: hourlyResult.price,
                    basePrice: hourlyResult.basePrice,
                    freeHours: hourlyResult.freeHours,
                    freeHoursUsed: hourlyResult.freeHoursUsed,
                    paidHours: hourlyResult.paidHours,
                    paidPrice: hourlyResult.paidPrice,
                    freeHoursRemaining: Math.max(
                        0,
                        hourlyResult.freeHours - hourlyResult.freeHoursUsed
                    )
                };
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

function calculateDiscountedPriceDetail(basePrice, freeHours, totalHours) {
    const total = Number(totalHours) || 0;
    const free = Number(freeHours) || 0;

    if (total <= 0) {
        return {
            price: 0,
            basePrice: 0,
            freeHours: free,
            freeHoursUsed: 0,
            paidHours: 0,
            paidPrice: 0,
            freeHoursRemaining: free
        };
    }

    const freeHoursUsed = Math.max(0, Math.min(free, total));
    const paidHours = total - freeHoursUsed;

    const paidPrice = paidHours <= 0 ? 0 : Math.round(basePrice * (paidHours / total));

    return {
        price: paidPrice,
        basePrice: Math.round(basePrice),
        freeHours: free,
        freeHoursUsed: freeHoursUsed,
        paidHours,
        paidPrice,
        freeHoursRemaining: Math.max(0, free - freeHoursUsed)
    };
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

    const detail = calculateDiscountedPriceDetail(basePrice, freeHours, validHoursCount);

    return {
        price: detail.price,
        basePrice: detail.basePrice,
        freeHours: detail.freeHours,
        freeHoursUsed: detail.freeHoursUsed,
        paidHours: detail.paidHours,
        paidPrice: detail.paidPrice,
        freeHoursRemaining: detail.freeHoursRemaining
    };
}
