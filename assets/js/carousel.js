import EmblaCarousel from 'embla-carousel';
import Autoplay from 'embla-carousel-autoplay';

document.addEventListener('DOMContentLoaded', function () {
    const emblaNode = document.querySelector('.login-carousel .embla__viewport');
    if (!emblaNode) return;

    const emblaOptions = {
        loop: true
    };

    const emblaApi = EmblaCarousel(emblaNode, emblaOptions, [
        Autoplay({
            delay: 4000,
            stopOnInteraction: false,
            stopOnFocusIn: false,
            playOnInit: true
        })
    ]);
});
