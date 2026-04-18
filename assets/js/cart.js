jQuery(document).ready(function($) {
    "use strict";

    // 🌐 Language Helper for JS
    function hkdevJsT(key) {
        const lang = (document.cookie.indexOf('hkdev_lang=en') !== -1) ? 'en' : 'bn';
        const dict = {
            'temp_error': { bn: 'সাময়িক সমস্যা হচ্ছে, অনুগ্রহ করে পেজটি রিলোড দিন।', en: 'Temporary issue, please reload the page.' },
            'server_error': { bn: 'সার্ভার এরর! পেজ রিলোড দিন।', en: 'Server error! Please reload the page.' },
            'confirm_remove': { bn: 'আপনি কি সত্যিই এই পণ্যটি সরাতে চান?', en: 'Are you sure you want to remove this item?' },
            'empty_coupon': { bn: 'অনুগ্রহ করে কুপন কোড লিখুন!', en: 'Please enter a coupon code!' }
        };
        return dict[key] ? dict[key][lang] : key;
    }

    const ajaxUrl = (typeof hkdev_ajax_obj !== 'undefined' && hkdev_ajax_obj.ajax_url) ? hkdev_ajax_obj.ajax_url : '/wp-admin/admin-ajax.php';
    const securityNonce = $('#hkdev_cart_nonce').val();

    // Function to trigger AJAX update
    function hkdev_update_cart(type, key = '', val = '') {
        const $container = $('#hkdev-cart-root');
        const $loader = $('#hkdev-cart-global-loader');

        $container.css('pointer-events', 'none');
        $loader.css('display', 'flex');

        let data = {
            action: 'hkdev_update_cart_ajax',
            security: securityNonce,
            update_type: type,
            cart_key: key
        };

        if (type === 'qty') data.new_qty = val;
        if (type === 'apply_coupon' || type === 'remove_coupon') data.coupon_code = val;

        $.ajax({
            type: 'POST',
            url: ajaxUrl,
            data: data,
            success: function(response) {
                if (response.success) {
                    if (response.data.is_empty) {
                        location.reload(); // Reload to show empty cart template
                    } else {
                        $('#hkdev-cart-items-area').html(response.data.items_html);
                        $('#hkdev-cart-totals-area').html(response.data.totals_html);
                        $container.css('pointer-events', 'auto');
                        $loader.fadeOut(200);
                        
                        // Update cart widget count (Mini Cart)
                        $(document.body).trigger('wc_fragment_refresh');

                        // 🔥 CRITICAL ADDITION: Trigger native WooCommerce events!
                        // এটি না দিলে BOGO Confetti এবং অন্যান্য পেমেন্ট প্লাগইন AJAX আপডেটের পর কাজ করবে না।
                        $(document.body).trigger('updated_cart_totals');
                        $(document.body).trigger('updated_wc_div');
                    }
                } else {
                    alert(hkdevJsT('temp_error'));
                    location.reload();
                }
            },
            error: function() {
                alert(hkdevJsT('server_error'));
                location.reload();
            }
        });
    }

    // Handle Quantity Plus / Minus
    $(document).on('click', '.hkdev-qty-mod', function() {
        const $btn = $(this);
        // Updated to find the row accurately even with native classes added
        const key = $btn.closest('[data-key]').data('key'); 
        const $valElement = $btn.siblings('.hkdev-qty-val');
        let currentQty = parseInt($valElement.text());
        
        let newQty = ($btn.data('act') === 'plus') ? currentQty + 1 : currentQty - 1;
        if (newQty < 1) return; // Prevent 0 or negative
        
        $valElement.text(newQty); // Instant UI update
        hkdev_update_cart('qty', key, newQty);
    });

    // Handle Remove Item
    $(document).on('click', '.hkdev-item-remove-btn', function() {
        if (!confirm(hkdevJsT('confirm_remove'))) return;
        const key = $(this).closest('[data-key]').data('key');
        hkdev_update_cart('remove', key);
    });

    // Handle Apply Coupon
    $(document).on('click', '#hkdev-apply-coupon-btn', function(e) {
        e.preventDefault();
        const code = $('#hkdev-coupon-input').val().trim();
        if (code === '') {
            alert(hkdevJsT('empty_coupon'));
            return;
        }
        hkdev_update_cart('apply_coupon', '', code);
    });

    // Handle Remove Coupon
    $(document).on('click', '.hkdev-remove-coupon', function(e) {
        e.preventDefault();
        const code = $(this).data('coupon');
        hkdev_update_cart('remove_coupon', '', code);
    });

});