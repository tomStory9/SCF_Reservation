import Chart from 'chart.js/auto';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css'; // Importe le CSS de Leaflet directement via Webpack

document.addEventListener('DOMContentLoaded', () => {
    const dataElement = document.getElementById('stats-data');

    if (!dataElement) {
        return;
    }

    const data = JSON.parse(dataElement.dataset.charts);
    const colors = [
        '#033862',
        '#e9b94d',
        '#3B82F6',
        '#10B981',
        '#F59E0B',
        '#EF4444',
        '#8B5CF6',
        '#EC4899',
        '#14B8A6',
        '#F97316'
    ];

    // --- GRAPHIQUE 1 : Nationalités (Camembert) ---
    const natCtx = document.getElementById('nationalityChart').getContext('2d');
    new Chart(natCtx, {
        type: 'doughnut',
        data: {
            labels: data.nationalities.map((n) => n.name),
            datasets: [
                {
                    data: data.nationalities.map((n) => n.count),
                    backgroundColor: colors
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });

    // --- GRAPHIQUE 2 : Spécialités (Barres horizontales) ---
    const specCtx = document.getElementById('specialtyChart').getContext('2d');
    new Chart(specCtx, {
        type: 'bar',
        data: {
            labels: data.specialties.map((s) => s.name),
            datasets: [
                {
                    label: 'Nombre de pratiquants',
                    data: data.specialties.map((s) => s.count),
                    backgroundColor: '#3B82F6',
                    borderRadius: 4
                }
            ]
        },
        options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false }
    });

    // --- GRAPHIQUE 3 : Mensuel (Ligne + Barres) ---
    const monthLabels = Object.keys(data.monthly);
    const monthCounts = monthLabels.map((k) => data.monthly[k].count);
    const monthRevenues = monthLabels.map((k) => data.monthly[k].revenue);

    const monthCtx = document.getElementById('monthlyChart').getContext('2d');
    new Chart(monthCtx, {
        type: 'bar',
        data: {
            labels: monthLabels,
            datasets: [
                {
                    type: 'line',
                    label: 'Revenus (¥)',
                    data: monthRevenues,
                    borderColor: '#e9b94d',
                    backgroundColor: '#e9b94d',
                    borderWidth: 3,
                    yAxisID: 'y1',
                    tension: 0.3
                },
                {
                    type: 'bar',
                    label: 'Nombre de réservations',
                    data: monthCounts,
                    backgroundColor: '#033862',
                    borderRadius: 4,
                    yAxisID: 'y'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: { display: true, text: 'Réservations' }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    grid: { drawOnChartArea: false },
                    title: { display: true, text: 'Revenus (¥)' }
                }
            }
        }
    });

    // --- GRAPHIQUE 4 : Zones ---
    const zoneLabels = Object.keys(data.zones);
    const zoneCtx = document.getElementById('zoneChart').getContext('2d');
    new Chart(zoneCtx, {
        type: 'bar',
        data: {
            labels: zoneLabels,
            datasets: [
                {
                    label: 'Réservations',
                    data: zoneLabels.map((k) => data.zones[k].count),
                    backgroundColor: '#10B981',
                    yAxisID: 'y'
                },
                {
                    label: 'Revenus (¥)',
                    data: zoneLabels.map((k) => data.zones[k].revenue),
                    backgroundColor: '#F59E0B',
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { type: 'linear', position: 'left' },
                y1: { type: 'linear', position: 'right', grid: { drawOnChartArea: false } }
            }
        }
    });

    // --- GRAPHIQUE 5 : Top Users (Type CNAC) ---
    const topUsersCtx = document.getElementById('topUsersChart').getContext('2d');
    new Chart(topUsersCtx, {
        type: 'bar',
        data: {
            labels: data.topUsers.map((u) => u.name),
            datasets: [
                {
                    label: 'Dépenses (¥)',
                    data: data.topUsers.map((u) => u.revenue),
                    backgroundColor: '#8B5CF6',
                    yAxisID: 'y'
                },
                {
                    label: 'Réservations',
                    data: data.topUsers.map((u) => u.count),
                    backgroundColor: '#EC4899',
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            indexAxis: 'x',
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { type: 'linear', position: 'left' },
                y1: { type: 'linear', position: 'right', grid: { drawOnChartArea: false } }
            }
        }
    });

    // --- 6. CARTE LEAFLET (Résidences type Immich) ---
    const map = L.map('residenceMap').setView([36.2048, 138.2529], 5);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    const mapLoader = document.getElementById('map-loading');

    async function placeCitiesOnMap() {
        if (data.cities.length > 0) mapLoader.classList.remove('d-none');

        for (const city of data.cities) {
            try {
                await new Promise((r) => setTimeout(r, 1000));

                const res = await fetch(
                    `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(city.city)}`
                );
                const geoData = await res.json();

                if (geoData.length > 0) {
                    const lat = geoData[0].lat;
                    const lon = geoData[0].lon;

                    L.circleMarker([lat, lon], {
                        radius: 8 + city.count * 2,
                        fillColor: '#EF4444',
                        color: '#fff',
                        weight: 2,
                        opacity: 1,
                        fillOpacity: 0.8
                    })
                        .addTo(map)
                        .bindPopup(`<b>${city.city}</b><br>${city.count} résident(s)`);
                }
            } catch (e) {
                console.warn('Impossible de trouver la ville : ' + city.city);
            }
        }
        mapLoader.classList.add('d-none');
    }

    placeCitiesOnMap();
});
