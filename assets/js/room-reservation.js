import '../styles/reservation-calendar.css';
import { Calendar } from '@fullcalendar/core';
import frLocale from '@fullcalendar/core/locales/fr';
import enGbLocale from '@fullcalendar/core/locales/en-gb';
import jaLocale from '@fullcalendar/core/locales/ja';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import Swal from 'sweetalert2';

document.addEventListener('DOMContentLoaded', function () {
    const configElement = document.getElementById('room-booking-config');
    const config = configElement ? JSON.parse(configElement.textContent) : null;
    const calendarEl = document.getElementById('calendar-holder');
    const roomTabs = document.querySelectorAll('.room-tab');
    const selectionPreview = document.getElementById('selection-preview');
    const priceContainer = document.getElementById('price-container');
    const priceDisplay = document.getElementById('price-display');
    const submitButton = document.getElementById('submit_booking');

    if (!calendarEl || !config) return;

    const texts = config.texts;
    const calendarLocales = {
        fr: frLocale,
        en: enGbLocale,
        ja: jaLocale
    };

    let calendar;

    let activeRoomId = null;
    let activeRoomName = '';
    let currentSelection = null;
    let currentRoomBookings = [];

    let activeRoomPricing = {};

    const initialTab = document.querySelector('.room-tab.is-active');
    if (initialTab) {
        setActiveRoom(initialTab);
    }

    function setActiveRoom(tabElement) {
        activeRoomId = tabElement.dataset.roomId;
        activeRoomName = tabElement.dataset.roomName;

        const rawPricing = JSON.parse(tabElement.dataset.roomPricing);
        activeRoomPricing = {};

        rawPricing.forEach((pricing) => {
            activeRoomPricing[pricing.dayNumber] = pricing.fullPrice;
        });

        currentSelection = null;
        updatePreview(texts.noSelection);
        priceContainer.classList.add('hidden');

        if (calendar) {
            calendar.unselect();
            calendar.refetchEvents();
        }
    }

    roomTabs.forEach((tab) => {
        tab.addEventListener('click', function () {
            roomTabs.forEach((item) => {
                item.classList.remove('is-active', 'border-b-0', 'bg-white', 'text-primary');
                item.classList.add('border-transparent', 'text-slate-500');
                item.setAttribute('aria-selected', 'false');
            });

            tab.classList.add('is-active', 'border-b-0', 'bg-white', 'text-primary');
            tab.classList.remove('border-transparent', 'text-slate-500');
            tab.setAttribute('aria-selected', 'true');

            setActiveRoom(tab);
        });
    });

    calendar = new Calendar(calendarEl, {
        plugins: [dayGridPlugin, interactionPlugin],
        initialView: 'dayGridMonth',
        locale: calendarLocales[config.locale] ?? enGbLocale,
        firstDay: 1,
        height: 'auto',
        selectable: true,
        selectMirror: true,
        unselectAuto: false,
        headerToolbar: {
            left: 'prev,next',
            center: 'title',
            right: 'today'
        },
        validRange: {
            start: new Date(Date.now() + (config.minDays || 0) * 24 * 60 * 60 * 1000)
                .toISOString()
                .split('T')[0]
        },

        events: async function (fetchInfo, successCallback, failureCallback) {
            const blockedPeriods = config.blockedPeriods || [];
            const blockedEvents = blockedPeriods.map((bp) => {
                const startStr = bp.start.substring(0, 10);
                const endStr = bp.end.substring(0, 10);

                const [endYear, endMonth, endDay] = endStr.split('-');
                const endDateObj = new Date(endYear, endMonth - 1, endDay);
                endDateObj.setDate(endDateObj.getDate() + 1);

                const nextYear = endDateObj.getFullYear();
                const nextMonth = String(endDateObj.getMonth() + 1).padStart(2, '0');
                const nextDay = String(endDateObj.getDate()).padStart(2, '0');
                const exclusiveEndStr = `${nextYear}-${nextMonth}-${nextDay}`;

                return {
                    id: `blocked-${bp.id}`,
                    start: startStr,
                    end: exclusiveEndStr,
                    title: texts.blockedPeriod,
                    allDay: true,
                    color: '#94a3b8',
                    textColor: '#ffffff',
                    classNames: ['pointer-events-none', 'fc-blocked-banner']
                };
            });

            if (!activeRoomId) {
                successCallback(blockedEvents);
                return;
            }

            try {
                const response = await fetch(`/zone/${activeRoomId}/bookings`);
                const events = await response.json();

                const formattedEvents = events.map((event) => {
                    const startStr = event.start.substring(0, 10);
                    const endStr = event.end.substring(0, 10);

                    const [endYear, endMonth, endDay] = endStr.split('-');
                    const endDateObj = new Date(endYear, endMonth - 1, endDay);
                    endDateObj.setDate(endDateObj.getDate() + 1);

                    const nextYear = endDateObj.getFullYear();
                    const nextMonth = String(endDateObj.getMonth() + 1).padStart(2, '0');
                    const nextDay = String(endDateObj.getDate()).padStart(2, '0');
                    const exclusiveEndStr = `${nextYear}-${nextMonth}-${nextDay}`;

                    return {
                        id: event.id,
                        start: startStr,
                        end: exclusiveEndStr,
                        title: event.title,
                        backgroundColor: event.backgroundColor,
                        allDay: true,
                        classNames: ['pointer-events-none']
                    };
                });

                currentRoomBookings = formattedEvents;

                successCallback([...formattedEvents, ...blockedEvents]);
            } catch (error) {
                console.error(error);
                failureCallback(error);
            }
        },

        selectAllow: function (selectInfo) {
            const selectStart = selectInfo.start.getTime();
            const selectEnd = selectInfo.end.getTime();

            const blockedPeriods = config.blockedPeriods || [];
            const isBlocked = blockedPeriods.some((bp) => {
                const bpStart = new Date(bp.start).getTime();
                const bpEnd = new Date(bp.end).getTime();
                return selectStart < bpEnd && selectEnd > bpStart;
            });

            if (isBlocked) {
                return false;
            }

            const days = Math.round((selectEnd - selectStart) / (1000 * 3600 * 24));
            let checkEnd = selectEnd;
            if (days === 1) {
                checkEnd = selectStart + 2 * 24 * 3600 * 1000;
            }

            return !currentRoomBookings.some((event) => {
                const eventStart = new Date(event.start).getTime();
                const eventEnd = new Date(event.end).getTime();

                return selectStart < eventEnd && checkEnd > eventStart;
            });
        },

        select: function (info) {
            const selectDays = Math.round(
                (info.end.getTime() - info.start.getTime()) / (1000 * 3600 * 24)
            );

            if (selectDays === 1) {
                const newEnd = new Date(info.start.getTime() + 2 * 24 * 3600 * 1000);
                calendar.select(info.start, newEnd);
                return;
            }

            const departureDate = new Date(info.end.getTime() - 1000 * 3600 * 24);
            const nights = Math.round(
                (departureDate.getTime() - info.start.getTime()) / (1000 * 3600 * 24)
            );

            let totalPrice = 0;
            let currentCursor = new Date(info.start);

            while (currentCursor < departureDate) {
                const jsDay = currentCursor.getDay();
                const phpDayNumber = jsDay === 0 ? 7 : jsDay;

                totalPrice += activeRoomPricing[phpDayNumber] || 0;
                currentCursor.setDate(currentCursor.getDate() + 1);
            }

            const backendEndDate = new Date(departureDate.getTime() - 1000 * 3600 * 24);
            const year = backendEndDate.getFullYear();
            const month = String(backendEndDate.getMonth() + 1).padStart(2, '0');
            const day = String(backendEndDate.getDate()).padStart(2, '0');
            const backendEndDateStr = `${year}-${month}-${day}T23:59:59`;

            const localizedStartDate = info.start.toLocaleDateString(config.locale);
            const localizedEndDate = departureDate.toLocaleDateString(config.locale);

            currentSelection = {
                startDate: info.startStr,
                endDate: backendEndDateStr,
                nights: nights,
                price: totalPrice
            };

            updatePreview(
                `<span class="font-semibold text-secondary">${texts.room}</span> <span class="text-primary">${activeRoomName}</span><br>` +
                    `<span class="font-semibold text-secondary">${texts.arrival}</span> ${localizedStartDate}<br>` +
                    `<span class="font-semibold text-secondary">${texts.departure}</span> ${localizedEndDate}<br>` +
                    `<span class="font-semibold text-secondary">${texts.duration}</span> ${texts.nights.replace('%count%', nights)}`
            );

            priceDisplay.textContent = `${totalPrice.toLocaleString(config.locale)} ¥`;
            priceContainer.classList.remove('hidden');
        }
    });

    calendar.render();

    function updatePreview(content) {
        if (selectionPreview) selectionPreview.innerHTML = content;
    }

    if (submitButton) {
        submitButton.addEventListener('click', async function () {
            if (!currentSelection || !activeRoomId) {
                Swal.fire({
                    icon: 'warning',
                    title: texts.warningTitle,
                    text: texts.selectDates
                });
                return;
            }

            const payload = {
                ...currentSelection,
                roomId: activeRoomId,
                bookingMode: 'room_night'
            };

            try {
                submitButton.disabled = true;
                submitButton.textContent = texts.loading;

                const response = await fetch('/room/booking/create', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (response.ok && result.success) {
                    window.location.href = result.redirectUrl;
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: texts.errorTitle,
                        text: result.error ?? texts.bookingError
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: texts.networkErrorTitle,
                    text: texts.networkError
                });
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = texts.submit;
            }
        });
    }
});
