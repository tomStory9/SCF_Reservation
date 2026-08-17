import { Calendar } from '@fullcalendar/core';
import frLocale from '@fullcalendar/core/locales/fr';
import enGbLocale from '@fullcalendar/core/locales/en-gb';
import jaLocale from '@fullcalendar/core/locales/ja';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';

import '../styles/admin-dashboard.css';

const calendarElement = document.getElementById('scf-booking-calendar');

if (calendarElement) {
    const locales = {
        fr: frLocale,
        en: enGbLocale,
        ja: jaLocale
    };
    const locale = calendarElement.dataset.locale || 'fr';
    let events = [];

    try {
        events = JSON.parse(calendarElement.dataset.events || '[]');
    } catch (error) {
        console.error('[admin-dashboard] Invalid calendar data', error);
    }

    const calendar = new Calendar(calendarElement, {
        plugins: [dayGridPlugin, timeGridPlugin],
        initialView: window.matchMedia('(max-width: 720px)').matches
            ? 'timeGridDay'
            : 'timeGridWeek',
        locale: locales[locale] || frLocale,
        firstDay: 1,
        nowIndicator: true,
        allDaySlot: true,
        slotMinTime: '07:00:00',
        slotMaxTime: '24:00:00',
        scrollTime: '08:00:00',
        height: 'auto',
        expandRows: true,
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        },
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'timeGridWeek,timeGridDay,dayGridMonth'
        },
        events,
        eventDidMount(info) {
            const details = info.event.extendedProps;
            const tooltip = [
                details.status,
                details.user,
                details.zone,
                details.facility
            ].filter(Boolean).join(' · ');

            info.el.title = tooltip;
        },
        eventClick(info) {
            if (!info.event.url) {
                return;
            }

            info.jsEvent.preventDefault();
            window.location.assign(info.event.url);
        }
    });

    calendar.render();
}
