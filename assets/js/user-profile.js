import TomSelect from 'tom-select';
import 'tom-select/dist/css/tom-select.css';

import '../styles/user-profile.css';

document.addEventListener('DOMContentLoaded', () => {
    const specialtiesElement = document.querySelector('.tom-select-specialties');

    if (!specialtiesElement) {
        return;
    }

    const placeholderText =
        specialtiesElement.dataset.placeholder || 'Sélectionnez vos spécialités...';

    new TomSelect(specialtiesElement, {
        plugins: ['remove_button'],
        placeholder: placeholderText,
        maxOptions: null,
        hideSelected: true,
        dropdownClass: 'ts-dropdown rounded-xl border border-slate-200 shadow-lg mt-1'
    });
});
