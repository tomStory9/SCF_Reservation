import Chart from 'chart.js/auto';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import '../styles/statistics.css';

document.addEventListener('DOMContentLoaded', () => {
    const dataElement = document.getElementById('stats-data');
    if (!dataElement) return;

    const data = JSON.parse(dataElement.dataset.charts);

    const isDarkMode = document.body.classList.contains('ea-dark-scheme');
    const textColor = isDarkMode ? '#cbd5e1' : '#475569';
    const gridColor = isDarkMode ? '#334155' : '#e2e8f0';

    Chart.defaults.color = textColor;
    Chart.defaults.scale.grid.color = gridColor;
    Chart.defaults.scale.grid.borderColor = gridColor;

    // Palette de couleurs plus lumineuse pour bien ressortir sur le noir
    const colors = [
        '#e9b94d',
        '#3B82F6',
        '#10B981',
        '#F59E0B',
        '#EF4444',
        '#8B5CF6',
        '#EC4899',
        '#14B8A6',
        '#F97316',
        '#6366f1'
    ];

    // --- GRAPHIQUE 1 : Nationalités ---
    new Chart(document.getElementById('nationalityChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: data.nationalities.map((n) => n.name),
            datasets: [
                {
                    data: data.nationalities.map((n) => n.count),
                    backgroundColor: colors,
                    borderColor: isDarkMode ? '#1e293b' : '#ffffff', // Bordure adaptée au fond du panel
                    borderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { color: textColor, padding: 20 } }
            }
        }
    });

    // --- GRAPHIQUE 2 : Spécialités ---
    new Chart(document.getElementById('specialtyChart').getContext('2d'), {
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

    // --- GRAPHIQUE 3 : Mensuel ---
    const monthlyDataAllYears = data.monthly;
    const yearFilter = document.getElementById('monthly-year-filter');

    // Année sélectionnée (ou la plus récente s'il n'y a pas de select)
    let currentYear = yearFilter
        ? yearFilter.value
        : Object.keys(monthlyDataAllYears).sort().reverse()[0];
    const monthLabels = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'];

    // Fonction pour extraire les données d'une année précise
    function getMonthlyDataForYear(year) {
        const yearData = monthlyDataAllYears[year];
        return {
            counts: monthLabels.map((m) => yearData[m].count),
            revenues: monthLabels.map((m) => yearData[m].revenue)
        };
    }

    let currentChartData = getMonthlyDataForYear(currentYear);
    const monthCtx = document.getElementById('monthlyChart').getContext('2d');

    const monthlyChart = new Chart(monthCtx, {
        type: 'bar',
        data: {
            labels: monthLabels,
            datasets: [
                {
                    type: 'line',
                    label: 'Revenus (¥)',
                    data: currentChartData.revenues,
                    borderColor: '#e9b94d',
                    backgroundColor: '#e9b94d',
                    borderWidth: 3,
                    yAxisID: 'y1',
                    tension: 0.3
                },
                {
                    type: 'bar',
                    label: 'Réservations',
                    data: currentChartData.counts,
                    backgroundColor: '#3B82F6',
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
                    position: 'left',
                    title: { display: true, text: 'Réservations', color: textColor }
                },
                y1: {
                    type: 'linear',
                    position: 'right',
                    grid: { drawOnChartArea: false },
                    title: { display: true, text: 'Revenus (¥)', color: textColor }
                }
            }
        }
    });

    // On met à jour UNIQUEMENT ce graphique quand l'année change
    if (yearFilter) {
        yearFilter.addEventListener('change', (e) => {
            const selectedYear = e.target.value;
            const newData = getMonthlyDataForYear(selectedYear);

            monthlyChart.data.datasets[0].data = newData.revenues;
            monthlyChart.data.datasets[1].data = newData.counts;
            monthlyChart.update();
        });
    }

    // --- GRAPHIQUE 4 : Zones ---
    const zoneLabels = Object.keys(data.zones);
    new Chart(document.getElementById('zoneChart').getContext('2d'), {
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

    // --- GRAPHIQUE 5 : Top Users ---
    new Chart(document.getElementById('topUsersChart').getContext('2d'), {
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

    // --- 6. CARTE LEAFLET ---
    const map = L.map('residenceMap').setView([36.2048, 138.2529], 5);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(map);

    const mapLoader = document.getElementById('map-loading');

    async function placeCitiesOnMap() {
        if (data.cities.length > 0 && mapLoader) mapLoader.classList.remove('d-none');

        for (const city of data.cities) {
            try {
                // Respect de l'API gratuite (1 sec d'attente)
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
                        fillColor: '#e9b94d', // Couleur or SCF
                        color: '#fff',
                        weight: 2,
                        opacity: 1,
                        fillOpacity: 0.8
                    })
                        .addTo(map)
                        .bindPopup(
                            `<b style="color:#1e293b;">${city.city}</b><br><span style="color:#475569;">${city.count} résident(s)</span>`
                        );
                }
            } catch (e) {
                console.warn('Impossible de trouver la ville : ' + city.city);
            }
        }
        if (mapLoader) mapLoader.classList.add('d-none');
    }

    placeCitiesOnMap();
});
