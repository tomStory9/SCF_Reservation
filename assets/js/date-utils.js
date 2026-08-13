export function normalizeDate(dateLike) {
    const date = new Date(dateLike);

    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

export function parseCleanIso(isoString) {
    if (!isoString) {
        return {
            dateFr: '',
            time: '',
            dateIso: '',
            cleanIso: ''
        };
    }

    const dateIso = isoString.slice(0, 10);
    const [year, month, day] = dateIso.split('-');

    const dateFr = `${day}/${month}/${year}`;
    const hasTime = isoString.includes('T');
    const time = hasTime ? isoString.slice(11, 16) : '';
    const cleanIso = hasTime ? `${dateIso}T${time}:00` : dateIso;

    return {
        dateFr,
        time,
        dateIso,
        cleanIso
    };
}

export function getTodayIsoString() {
    return normalizeDate(new Date());
}

export function getDayNumber(dateLike) {
    const date = new Date(dateLike);

    return date.getDay() || 7;
}