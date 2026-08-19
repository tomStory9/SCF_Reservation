export function createEquipmentState() {
    return {
        availableEquipments: [],
        selectedEquipments: {},
        equipmentTotal: 0
    };
}

export function getSelectedEquipmentPayload(state) {
    return Object.entries(state.selectedEquipments)
        .filter(([, quantity]) => quantity > 0)
        .map(([equipmentId, quantity]) => ({
            equipmentId: Number(equipmentId),
            quantity: Number(quantity)
        }));
}
