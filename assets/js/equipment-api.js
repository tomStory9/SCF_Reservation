export function createEquipmentApi(config) {
    return {
        async getForZone(zoneId, startDate, endDate) {
            if (!zoneId || !startDate || !endDate) {
                return [];
            }

            const params = new URLSearchParams({
                startDate: String(startDate),
                endDate: String(endDate)
            });

            const response = await fetch(
                `/zone/${encodeURIComponent(zoneId)}/equipments?${params}`
            );

            if (!response.ok) {
                throw new Error(
                    'Impossible de charger les équipements'
                );
            }

            return response.json();
        }
    };
}