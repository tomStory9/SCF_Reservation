import { Controller } from '@hotwired/stimulus';
import intlTelInput from 'intl-tel-input';

import 'intl-tel-input/styles';


export default class extends Controller {

    connect() {

        console.log('phone connect');

        const input = this.element.querySelector('input');

        if (!input) {
            return;
        }


        this.iti = intlTelInput(input, {
            initialCountry: "jp",
            separateDialCode: true,

            loadUtils: () => import('intl-tel-input/utils'),
        });


        this.submitHandler = () => {
            input.value = this.iti.getNumber();
        };


        input.form?.addEventListener(
            'submit',
            this.submitHandler
        );

    }


    disconnect() {

        console.log('phone disconnect');


        if (this.iti) {
            this.iti.destroy();
            this.iti = null;
        }


        const input = this.element.querySelector('input');

        if (input?.form && this.submitHandler) {
            input.form.removeEventListener(
                'submit',
                this.submitHandler
            );
        }

    }

}