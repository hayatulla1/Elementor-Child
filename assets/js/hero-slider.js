jQuery(document).ready(function($) {
    "use strict";

    // Loop through each hero slider instance on the page
    $('.hkdev-hero-section-wrapper').each(function() {
        const $wrapper = $(this);
        const $container = $wrapper.find('.hkdev-hero-swiper');
        
        if (!$container.length || typeof Swiper === 'undefined') return;

        // Check if autoplay is enabled via data attribute
        const isAutoplay = $container.data('autoplay') === true;
        
        let swiperOptions = {
            slidesPerView: 1,
            spaceBetween: 0,
            loop: true,
            speed: 800, // Smooth transition
            effect: 'slide', 
            grabCursor: true,
            navigation: {
                nextEl: $wrapper.find('.hkdev-hero-next')[0],
                prevEl: $wrapper.find('.hkdev-hero-prev')[0],
            },
            pagination: {
                el: $wrapper.find('.hkdev-hero-pagination')[0],
                clickable: true,
            }
        };

        if (isAutoplay) {
            swiperOptions.autoplay = {
                delay: 5000, // 5 seconds per slide
                disableOnInteraction: false,
                pauseOnMouseEnter: true // Pauses when user hovers over banner
            };
        }

        // Initialize Swiper for this specific wrapper
        new Swiper($container[0], swiperOptions);
    });
});