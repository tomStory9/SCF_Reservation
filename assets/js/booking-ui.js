export function createBookingUI(config) {
    const text = config.texts;

    const selectionPreview =
        document.getElementById('selection-preview');

    const activeBookingModeLabel =
        document.getElementById('active-booking-mode-label');

    const locationLabel =
        document.getElementById('selected-location-label');

    const priceContainer =
        document.getElementById('price-container');

    const priceDisplay =
        document.getElementById('price-display');

    return {
        updatePreview(content) {
            if (selectionPreview) {
                selectionPreview.innerHTML = content;
            }
        },

        updatePrice(prices) {
            if (!priceContainer || !priceDisplay) {
                return;
            }

            if (
                !prices ||
                prices.price === null ||
                prices.price === undefined
            ) {
                priceContainer.classList.add('hidden');
                return;
            }

            priceDisplay.textContent =
                `${prices.price.toLocaleString(config.locale)} ¥`;

            priceContainer.classList.remove('hidden');
        },

        updateLocationLabel(label) {
            if (locationLabel) {
                locationLabel.textContent = label;
            }
        },

        updateBookingModeLabel(mode) {
            if (!activeBookingModeLabel) {
                return;
            }

            activeBookingModeLabel.textContent =
                mode === 'period'
                    ? text.bookingByPeriod
                    : text.bookingByHour;
        },

        setZonePlaceholder(tomSelect, placeholder) {
            tomSelect.settings.placeholder = placeholder;
            tomSelect.input.placeholder = placeholder;
            tomSelect.updatePlaceholder();
        },

        showError(error) {
            console.error(error);
        }
    };
}
