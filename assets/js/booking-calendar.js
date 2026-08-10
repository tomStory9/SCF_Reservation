import '../styles/reservation-calendar.css';

import TomSelect from 'tom-select';
import Swal from 'sweetalert2';

import { loadCalendarConfig, loadUserBookingData } from './config.js';
import { createBookingState } from './state.js';
import { createBookingApi } from './api.js';
import { createPricingService } from './pricing.js';
import { createBookingUI } from './booking-ui.js';
import { createCalendarEventService } from './calendar-events.js';
import { createCalendar } from './calendar-instance.js';

document.addEventListener('DOMContentLoaded', async () => {
    const zoneSelectElement =
        document.getElementById('zone-select');

    if (!zoneSelectElement) {
        return;
    }

    const calendarElement =
        document.getElementById('calendar-holder');

    const config = loadCalendarConfig();
    const userData = loadUserBookingData();
    const state = createBookingState();
    const api = createBookingApi(config);
    const ui = createBookingUI(config);
    const eventService =
        createCalendarEventService(state, config);

    const locationTabs =
        document.querySelectorAll('.location-tab');

    const bookingModeLinks =
        document.querySelectorAll('.booking-mode-link');

    const submitButton =
        document.getElementById('submit_booking');

    const guestCountInput =
        document.getElementById('guest-count-input');

    const zoneTomSelect = new TomSelect(
        zoneSelectElement,
        {
            valueField: 'id',
            labelField: 'name',
            searchField: 'name',
            placeholder: config.texts.zonePlaceholder,
            allowEmptyOption: false,

            onChange: async zoneId => {
                if (!zoneId) {
                    return;
                }

                state.activeZoneId = zoneId;
                state.currentSelection = null;
                state.currentZonePricings = {};
                ui.updatePrice(null);

                try {
                    state.currentZonePricings =
                        await api.getPricings(zoneId);

                    calendar?.refetchEvents();
                } catch (error) {
                    console.error(error);
                    state.currentZonePricings = {};
                }
            }
        }
    );

    const pricingService = createPricingService(
        () => state.currentZonePricings,
        () => userData.freeHours
    );

    let calendar = null;

    function getSelectedZoneName() {
        if (!state.activeZoneId) {
            return config.texts.undefined;
        }

        const option =
            zoneTomSelect.options[state.activeZoneId] ||
            zoneTomSelect.options[String(state.activeZoneId)];

        return option?.name ?? config.texts.undefined;
    }

    async function loadZonesForFacility(facilityId) {
        zoneTomSelect.clear();
        zoneTomSelect.clearOptions();

        try {
            const zones = await api.getZones(facilityId);

            if (zones.length === 0) {
                ui.setZonePlaceholder(
                    zoneTomSelect,
                    config.texts.noZoneAvailable
                );

                return;
            }

            zoneTomSelect.addOptions(zones);
            zoneTomSelect.setValue(zones[0].id);
        } catch (error) {
            console.error(error);
        }
    }

    calendar = createCalendar({
        calendarEl: calendarElement,
        config,
        state,
        api,
        eventService,
        pricingService,
        ui,
        getSelectedZoneName,
        getGuestCount: () => guestCountInput?.value ?? 0
    });

    calendar.render();

    function activateLocationTab(tab) {
        locationTabs.forEach(item => {
            item.classList.remove(
                'is-active',
                'border-slate-200',
                'bg-white',
                'text-primary'
            );

            item.classList.add(
                'border-transparent',
                'text-slate-500'
            );

            item.setAttribute('aria-selected', 'false');
        });

        tab.classList.add(
            'is-active',
            'border-slate-200',
            'bg-white',
            'text-primary'
        );

        tab.classList.remove(
            'border-transparent',
            'text-slate-500'
        );

        tab.setAttribute('aria-selected', 'true');
        ui.updateLocationLabel(tab.textContent.trim());
    }

    locationTabs.forEach(tab => {
        tab.addEventListener('click', async () => {
            activateLocationTab(tab);

            state.currentSelection = null;
            state.selectedPeriodPreviewEvent = null;

            ui.updatePreview(config.texts.noSelection);
            ui.updatePrice(null);

            calendar.unselect();
            calendar.refetchEvents();

            await loadZonesForFacility(tab.dataset.location);
        });
    });

    const initialActiveTab =
        document.querySelector('.location-tab.is-active');

    if (initialActiveTab) {
        await loadZonesForFacility(
            initialActiveTab.dataset.location
        );
    }

    bookingModeLinks.forEach(button => {
        button.addEventListener('click', () => {
            state.bookingMode = button.dataset.mode;
            state.currentSelection = null;
            state.selectedPeriodPreviewEvent = null;

            bookingModeLinks.forEach(link => {
                const active =
                    link.dataset.mode === state.bookingMode;

                link.classList.toggle('is-active', active);
                link.classList.toggle('text-secondary', active);
                link.classList.toggle('text-state', !active);
                link.setAttribute(
                    'aria-pressed',
                    active ? 'true' : 'false'
                );
            });

            ui.updateBookingModeLabel(state.bookingMode);
            ui.updatePrice(null);

            calendar.unselect();
            calendar.refetchEvents();

            ui.updatePreview(
                state.bookingMode === 'period'
                    ? config.texts.periodModeHelp
                    : config.texts.hourModeHelp
            );
        });
    });

    submitButton?.addEventListener('click', async () => {
        if (!state.currentSelection || !state.activeZoneId) {
            alert(config.texts.selectSlot);
            return;
        }

        const payload = {
            ...state.currentSelection,
            zoneId: state.activeZoneId
        };

        try {
            submitButton.disabled = true;
            submitButton.textContent = config.texts.loading;

            const { response, result } =
                await api.createBooking(payload);

            if (response.ok && result.success) {
                window.location.href = result.redirectUrl;
                return;
            }

            Swal.fire({
                icon: 'error',
                title: config.texts.bookingErrorTitle,
                text: result.error || config.texts.bookingError,
                confirmButtonColor: '#d33'
            });
        } catch (error) {
            console.error(error);

            Swal.fire({
                icon: 'error',
                title: config.texts.networkErrorTitle,
                text: config.texts.networkError,
                confirmButtonColor: '#d33'
            });
        } finally {
            submitButton.disabled = false;
            submitButton.textContent =
                config.texts.submitBooking;
        }
    });
});