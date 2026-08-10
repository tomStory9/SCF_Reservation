export function createEquipmentApi(config) {
    return {
        async getForZone(zoneId) {
            const endpoint =
                config.endpoints.equipments
                    .replace('{zoneId}', encodeURIComponent(zoneId));

            const response = await fetch(endpoint, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(
                    'Impossible de charger les équipements.'
                );
            }

            return response.json();
        }
    };
}