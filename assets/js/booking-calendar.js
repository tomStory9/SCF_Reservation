import '../styles/reservation-calendar.css';

import TomSelect from 'tom-select';
import Swal from 'sweetalert2';

import {
    loadCalendarConfig,
    loadUserBookingData
} from './config.js';

import { createBookingState } from './state.js';
import { createBookingApi } from './api.js';

import { createPricingService } from './pricing.js';

import { createBookingUI } from './booking-ui.js';

import {
    createCalendarEventService
} from './calendar-events.js';

import { createCalendar } from './calendar-instance.js';

import { createEquipmentApi } from './equipment-api.js';

import {
    createEquipmentState,
    getSelectedEquipmentPayload
} from './equipment-state.js';

import {
    createEquipmentUI
} from './equipment-ui.js';


document.addEventListener('DOMContentLoaded', async () => {
    const calendarElement =
        document.getElementById('calendar-holder');

    const zoneSelectElement =
        document.getElementById('zone-select');

    if (!calendarElement || !zoneSelectElement) {
        console.warn(
            '[reservation] Éléments nécessaires introuvables'
        );

        return;
    }

    const config = loadCalendarConfig();
    const userData = loadUserBookingData();

    const state = createBookingState();
    const api = createBookingApi(config);
    const ui = createBookingUI(config);

    const eventService =
        createCalendarEventService(state, config);

    const equipmentState =
        createEquipmentState();

    const equipmentApi =
        createEquipmentApi(config);

    const equipmentListElement =
        document.getElementById('equipment-list');

    const equipmentTotalContainer =
        document.getElementById(
            'equipment-total-container'
        );

    const equipmentTotalDisplay =
        document.getElementById(
            'equipment-total-display'
        );

    const grandTotalContainer =
        document.getElementById(
            'grand-total-container'
        );

    const grandTotalDisplay =
        document.getElementById(
            'grand-total-display'
        );

    const equipmentUI =
        equipmentListElement &&
            equipmentTotalContainer &&
            equipmentTotalDisplay
            ? createEquipmentUI(
                equipmentState,
                {
                    list: equipmentListElement,
                    totalContainer: equipmentTotalContainer,
                    totalDisplay: equipmentTotalDisplay,
                    grandTotalContainer,
                    grandTotalDisplay
                }
            )
            : null;

    const pricingService =
        createPricingService(
            () => state.currentZonePricings,
            () => userData.freeHours
        );

    const locationTabs =
        document.querySelectorAll('.location-tab');

    const bookingModeLinks =
        document.querySelectorAll('.booking-mode-link');

    const submitButton =
        document.getElementById('submit_booking');

    const guestCountInput =
        document.getElementById('guest-count-input');

    const mobileBookingModeLabel =
        document.getElementById(
            'active-booking-mode-label-mobile'
        );

    let calendar = null;

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
                    state.activeZoneId = null;
                    state.currentZonePricings = {};
                    state.currentSelection = null;

                    ui.updatePrice(null);

                    if (equipmentUI) {
                        equipmentUI.render([]);
                    }

                    calendar?.refetchEvents();

                    return;
                }

                state.activeZoneId = zoneId;
                state.currentSelection = null;
                state.currentZonePricings = {};

                ui.updatePrice(null);

                try {
                    state.currentZonePricings =
                        await api.getPricings(zoneId);
                } catch (error) {
                    console.error(
                        '[reservation] Erreur tarifs :',
                        error
                    );

                    state.currentZonePricings = {};
                }

                if (equipmentUI) {
                    await loadEquipmentsForZone(zoneId);
                }

                calendar?.refetchEvents();
            }
        }
    );

    function getSelectedZoneName() {
        if (!state.activeZoneId) {
            return config.texts.undefined;
        }

        const option =
            zoneTomSelect.options[state.activeZoneId] ||
            zoneTomSelect.options[String(state.activeZoneId)];

        return option?.name ?? config.texts.undefined;
    }

    async function loadEquipmentsForZone(zoneId) {
        if (!equipmentUI || !equipmentListElement) {
            return;
        }

        try {
            equipmentListElement.innerHTML = `
                <p class="px-3 py-2 text-xs text-state">
                    Chargement des équipements...
                </p>
            `;

            const equipments =
                await equipmentApi.getForZone(zoneId);

            equipmentUI.render(equipments);
        } catch (error) {
            console.error(
                '[reservation] Erreur équipements :',
                error
            );

            equipmentListElement.innerHTML = `
                <p class="px-3 py-2 text-xs text-red-600">
                    Impossible de charger les équipements.
                </p>
            `;

            if (equipmentTotalContainer) {
                equipmentTotalContainer.classList.add('hidden');
            }

            if (grandTotalContainer) {
                grandTotalContainer.classList.add('hidden');
            }
        }
    }

    async function loadZonesForFacility(facilityId) {
        zoneTomSelect.clear();
        zoneTomSelect.clearOptions();

        state.activeZoneId = null;
        state.currentSelection = null;
        state.currentZonePricings = {};

        ui.updatePrice(null);

        if (equipmentUI) {
            equipmentUI.render([]);
        }

        try {
            const zones =
                await api.getZones(facilityId);

            if (!zones.length) {
                zoneTomSelect.settings.placeholder =
                    config.texts.noZoneAvailable;

                zoneTomSelect.input.placeholder =
                    config.texts.noZoneAvailable;

                zoneTomSelect.updatePlaceholder();

                return;
            }

            zoneTomSelect.addOptions(zones);


            zoneTomSelect.setValue(zones[0].id);
        } catch (error) {
            console.error(
                '[reservation] Erreur zones :',
                error
            );

            zoneTomSelect.settings.placeholder =
                config.texts.noZoneAvailable;

            zoneTomSelect.input.placeholder =
                config.texts.noZoneAvailable;

            zoneTomSelect.updatePlaceholder();
        }
    }

    function updateBookingModeLinks() {
        bookingModeLinks.forEach(button => {
            const isActive =
                button.dataset.mode === state.bookingMode;

            button.classList.toggle(
                'is-active',
                isActive
            );

            button.classList.toggle(
                'text-secondary',
                isActive
            );

            button.classList.toggle(
                'text-state',
                !isActive
            );

            button.setAttribute(
                'aria-pressed',
                isActive ? 'true' : 'false'
            );
        });

        ui.updateBookingModeLabel(
            state.bookingMode
        );

        if (mobileBookingModeLabel) {
            mobileBookingModeLabel.textContent =
                state.bookingMode === 'period'
                    ? config.texts.bookingByPeriod
                    : config.texts.bookingByHour;
        }
    }

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

            item.setAttribute(
                'aria-selected',
                'false'
            );
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

        tab.setAttribute(
            'aria-selected',
            'true'
        );

        ui.updateLocationLabel(
            tab.textContent.trim()
        );
    }

    function resetCurrentSelection() {
        state.currentSelection = null;
        state.selectedPeriodPreviewEvent = null;

        ui.updatePreview(
            config.texts.noSelection
        );

        ui.updatePrice(null);

        calendar?.unselect();
        calendar?.refetchEvents();
    }

    /*
     * Création du calendrier
     */
    calendar = createCalendar({
        calendarEl: calendarElement,
        config,
        state,
        api,
        eventService,
        pricingService,
        ui,
        getSelectedZoneName,
        getGuestCount: () =>
            guestCountInput?.value ?? 0
    });

    calendar.render();

    /*
     * Initialisation du mode de réservation
     */
    state.bookingMode = 'hour';
    updateBookingModeLinks();

    /*
     * Changement de mode :
     * par heure / par période
     */
    bookingModeLinks.forEach(button => {
        button.addEventListener('click', () => {
            const selectedMode =
                button.dataset.mode;

            if (
                selectedMode !== 'hour' &&
                selectedMode !== 'period'
            ) {
                return;
            }

            state.bookingMode = selectedMode;

            resetCurrentSelection();
            updateBookingModeLinks();

            ui.updatePreview(
                state.bookingMode === 'period'
                    ? config.texts.periodModeHelp
                    : config.texts.hourModeHelp
            );
        });
    });

    /*
     * Changement de lieu
     */
    locationTabs.forEach(tab => {
        tab.addEventListener('click', async () => {
            activateLocationTab(tab);
            resetCurrentSelection();

            await loadZonesForFacility(
                tab.dataset.location
            );
        });
    });

    /*
     * Chargement initial du premier lieu
     */
    const initialActiveTab =
        document.querySelector(
            '.location-tab.is-active'
        );

    if (initialActiveTab) {
        activateLocationTab(initialActiveTab);

        await loadZonesForFacility(
            initialActiveTab.dataset.location
        );
    }

    /*
     * Soumission de la réservation
     */
    submitButton?.addEventListener(
        'click',
        async () => {
            if (
                !state.currentSelection ||
                !state.activeZoneId
            ) {
                alert(config.texts.selectSlot);
                return;
            }

            const selectedEquipments =
                getSelectedEquipmentPayload(
                    equipmentState
                );

            const payload = {
                ...state.currentSelection,
                zoneId: state.activeZoneId,
                equipments: selectedEquipments
            };
            console.log(payload);
            try {
                submitButton.disabled = true;
                submitButton.textContent =
                    config.texts.loading;

                const {
                    response,
                    result
                } = await api.createBooking(payload);

                if (
                    response.ok &&
                    result.success
                ) {
                    window.location.href =
                        result.redirectUrl;

                    return;
                }

                Swal.fire({
                    icon: 'error',
                    title: config.texts.bookingErrorTitle,
                    text:
                        result.error ||
                        config.texts.bookingError,
                    confirmButtonColor: '#d33'
                });
            } catch (error) {
                console.error(
                    '[reservation] Erreur réservation :',
                    error
                );

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
        }
    );
});