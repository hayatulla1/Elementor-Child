jQuery(document).ready(function($) {
    "use strict";

    // Initialize all category sliders on the page
    $('.hkdev-cat-section-wrapper').each(function() {
        const $wrapper = $(this);
        const containerElem = $wrapper.find('.hkdev-category-swiper')[0];
        
        if (!containerElem || typeof Swiper === 'undefined') return;

        new Swiper(containerElem, {
            slidesPerView: 3, // Default for very small mobile
            spaceBetween: 10,
            grabCursor: true,
            loop: false,
            speed: 500,
            autoplay: {
                delay: 4000,
                disableOnInteraction: true
            },
            navigation: {
                nextEl: $wrapper.find('.hkdev-cat-next')[0],
                prevEl: $wrapper.find('.hkdev-cat-prev')[0],
            },
            breakpoints: {
                // when window width is >= 480px
                480: {
                    slidesPerView: 4,
                    spaceBetween: 15
                },
                // when window width is >= 768px
                768: {
                    slidesPerView: 5,
                    spaceBetween: 20
                },
                // when window width is >= 1024px
                1024: {
                    slidesPerView: 7, // Fits exactly 7 items like the image
                    spaceBetween: 20
                }
            }
        });
    });
});