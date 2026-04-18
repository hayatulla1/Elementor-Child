jQuery(function($) {
    "use strict";

    // Set dynamic AJAX URL globally (Fallback to default WP ajax if not registered)
    const ajaxUrl = (typeof hkdev_ajax_obj !== 'undefined' && hkdev_ajax_obj.ajax_url) ? hkdev_ajax_obj.ajax_url : '/wp-admin/admin-ajax.php';

    function initHkdevSwiper(wrapperId) {
        const $wrapper = $('#' + wrapperId);
        const $container = $wrapper.find('.hkdev-swiper-container');
        if (!$container.length || typeof Swiper === 'undefined') return;

        const desktopCols = parseInt($wrapper.data('columns')) || 4;

        new Swiper($container[0], {
            slidesPerView: 2,
            spaceBetween: 10,
            grabCursor: true,
            speed: 600,
            observer: true,
            observeParents: true,
            autoplay: { delay: 5000, disableOnInteraction: true },
            pagination: { el: $wrapper.find('.swiper-pagination')[0], clickable: true },
            navigation: {
                nextEl: '.hkdev-next-' + wrapperId,
                prevEl: '.hkdev-prev-' + wrapperId,
            },
            breakpoints: {
                768: { slidesPerView: desktopCols > 3 ? 3 : desktopCols, spaceBetween: 15 },
                1024: { slidesPerView: desktopCols, spaceBetween: 20 }
            },
            on: {
                init: function () {
                    $container.removeClass('hkdev-loading-carousel');
                }
            }
        });
    }

    // Initialize all carousels on page load
    $('.hkdev-shop-wrapper[data-style="carousel"]').each(function() {
        initHkdevSwiper($(this).attr('id'));
    });

    // Category Tabs Filter AJAX
    $('.hkdev-tab-item').on('click', function() {
        var $btn = $(this), 
            $wrapper = $btn.closest('.hkdev-shop-wrapper'), 
            $grid = $wrapper.find('.hkdev-shop-grid'), 
            $loader = $wrapper.find('.hkdev-ajax-loader');
            
        if($btn.hasClass('active')) return;
        
        $wrapper.find('.hkdev-tab-item').removeClass('active'); 
        $btn.addClass('active');
        $loader.fadeIn(150).css('display', 'flex'); 
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'hkdev_filter_products',
                category: $btn.attr('data-slug'),
                exclude: $wrapper.data('exclude'), 
                include_children: $wrapper.data('include_children'),
                type: $wrapper.data('type'),
                limit: $wrapper.data('limit'),
                days: $wrapper.data('days'),
                order_by: $wrapper.data('order_by'),
                style: $wrapper.data('style')
            },
            success: function(response) { 
                $grid.html(response); 
                $loader.fadeOut(150); 
                if($wrapper.data('style') === 'carousel') {
                    initHkdevSwiper($wrapper.attr('id'));
                }
            }
        });
    });

    // Global Add to Cart Toast & Button state handler
    $(document.body).on('added_to_cart', function(e, f, h, $button) {
        $('.added_to_cart').remove(); // Default woocommerce 'view cart' link remove
        
        // Show Toast Notification
        var $toast = $('#hkdev-toast-master');
        if($toast.length) {
            $toast.show().addClass('show');
            setTimeout(function() {
                $toast.removeClass('show');
                setTimeout(() => $toast.hide(), 400);
            }, 2000);
        }

        // Logic to keep button enabled everywhere
        if ($button) {
            if ($button.hasClass('hkdev-cart-btn')) {
                // If it is our Grid Button, just change 'Buy Now' to 'Checkout' without disabling Add to Cart
                var $orderBtn = $button.closest('.hkdev-action-group').find('.hkdev-order-btn');
                if($orderBtn.length) {
                    $orderBtn.text('Checkout').attr('href', $orderBtn.data('checkout_url'));
                }
                
                // Remove WooCommerce loading class manually to reset the button state
                $button.removeClass('loading disabled').prop('disabled', false).css({'pointer-events': 'auto', 'opacity': '1'});
            } else {
                // If it is Single Product Page Button, re-enable it immediately
                $button.removeClass('disabled').prop('disabled', false).css({'pointer-events': 'auto', 'opacity': '1'});
            }
        }
    });
});