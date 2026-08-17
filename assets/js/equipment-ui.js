export function createEquipmentUI(
    state,
    elements,
    config
) {
    function formatPrice(price) {
        return `${Number(price).toLocaleString(config.locale)} ¥`;
    }

    function calculateTotal() {
        return Object.entries(
            state.selectedEquipments
        ).reduce(
            (total, [equipmentId, quantity]) => {
                const equipment =
                    state.availableEquipments.find(
                        item =>
                            item.id ===
                            Number(equipmentId)
                    );

                if (!equipment) {
                    return total;
                }

                return total +
                    Number(equipment.unitPrice) *
                    Number(quantity);
            },
            0
        );
    }

    function updateTotal() {
        state.equipmentTotal =
            calculateTotal();

        if (
            state.equipmentTotal > 0
        ) {
            elements.totalContainer.classList.remove(
                'hidden'
            );

            elements.totalDisplay.textContent =
                formatPrice(
                    state.equipmentTotal
                );
        } else {
            elements.totalContainer.classList.add(
                'hidden'
            );

            elements.totalDisplay.textContent =
                formatPrice(0);
        }
    }

    function reset() {
        state.availableEquipments =
            [];

        state.selectedEquipments =
            {};

        state.equipmentTotal =
            0;

        elements.list.innerHTML =
            '';

        elements.totalContainer.classList.add(
            'hidden'
        );

        elements.totalDisplay.textContent =
            formatPrice(0);

        if (
            elements.grandTotalContainer
        ) {
            elements.grandTotalContainer.classList.add(
                'hidden'
            );
        }

        if (
            elements.grandTotalDisplay
        ) {
            elements.grandTotalDisplay.textContent =
                formatPrice(0);
        }
    }

    function render(equipments) {
        state.availableEquipments =
            equipments;

        state.selectedEquipments =
            {};

        state.equipmentTotal =
            0;

        if (
            !equipments.length
        ) {
            elements.list.innerHTML = `
                <p class="px-3 py-2 text-xs text-state">
                    ${config.texts.noEquipmentsAvailable}
                </p>
            `;

            updateTotal();

            return;
        }

        elements.list.innerHTML =
            equipments
                .map(equipment => {
                    const availableQuantity =
                        Number(
                            equipment.availableQuantity ??
                            0
                        );

                    const isUnavailable =
                        availableQuantity <= 0;

                    return `
                        <div
                            class="equipment-item flex items-center justify-between bg-white px-3 py-2 ${isUnavailable
                            ? 'opacity-50'
                            : ''
                        }"
                            data-equipment-id="${equipment.id}"
                        >
                            <div class="flex flex-col">
                                <span class="text-xs font-semibold text-secondary">
                                    ${equipment.name}
                                </span>

                                <span class="text-[11px] text-state">
                                    ${formatPrice(
                            equipment.unitPrice
                        )}
                                    · ${config.texts.availableEquipment} :
                                    ${availableQuantity}
                                </span>
                            </div>

                            <div class="flex items-center gap-1">
                                <button
                                    type="button"
                                    class="equipment-decrease-btn h-6 w-6 rounded bg-slate-100 text-xs font-bold"
                                    disabled
                                >
                                    −
                                </button>

                                <span
                                    class="equipment-quantity-display min-w-[24px] text-center text-xs font-semibold"
                                >
                                    0
                                </span>

                                <button
                                    type="button"
                                    class="equipment-increase-btn h-6 w-6 rounded bg-slate-100 text-xs font-bold"
                                    ${isUnavailable
                            ? 'disabled'
                            : ''
                        }
                                >
                                    +
                                </button>
                            </div>
                        </div>
                    `;
                })
                .join('');

        equipments.forEach(
            equipment => {
                const item =
                    elements.list.querySelector(
                        `[data-equipment-id="${equipment.id}"]`
                    );

                if (!item) {
                    return;
                }

                const decreaseButton =
                    item.querySelector(
                        '.equipment-decrease-btn'
                    );

                const increaseButton =
                    item.querySelector(
                        '.equipment-increase-btn'
                    );

                const quantityDisplay =
                    item.querySelector(
                        '.equipment-quantity-display'
                    );

                const availableQuantity =
                    Number(
                        equipment.availableQuantity ??
                        0
                    );

                function updateButtons(
                    quantity
                ) {
                    decreaseButton.disabled =
                        quantity <= 0;

                    increaseButton.disabled =
                        availableQuantity <= 0 ||
                        quantity >=
                        availableQuantity;
                }

                decreaseButton.addEventListener(
                    'click',
                    () => {
                        const current =
                            state.selectedEquipments[
                            equipment.id
                            ] || 0;

                        const quantity =
                            Math.max(
                                0,
                                current - 1
                            );

                        if (
                            quantity === 0
                        ) {
                            delete state
                                .selectedEquipments[
                                equipment.id
                            ];
                        } else {
                            state.selectedEquipments[
                                equipment.id
                            ] = quantity;
                        }

                        quantityDisplay.textContent =
                            quantity;

                        updateButtons(
                            quantity
                        );

                        updateTotal();
                    }
                );

                increaseButton.addEventListener(
                    'click',
                    () => {
                        const current =
                            state.selectedEquipments[
                            equipment.id
                            ] || 0;

                        const quantity =
                            Math.min(
                                availableQuantity,
                                current + 1
                            );

                        if (
                            quantity <= 0
                        ) {
                            return;
                        }

                        state.selectedEquipments[
                            equipment.id
                        ] = quantity;

                        quantityDisplay.textContent =
                            quantity;

                        updateButtons(
                            quantity
                        );

                        updateTotal();
                    }
                );

                updateButtons(0);
            }
        );

        updateTotal();
    }

    return {
        render,
        reset,
        getTotal: () =>
            state.equipmentTotal
    };
}
