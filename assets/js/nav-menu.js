jQuery(document).ready(function($) {
    "use strict";

    // ==========================================
    // 1. STICKY HEADER SCROLL EFFECT
    // ==========================================
    $(window).on('scroll', function() {
        if ($(window).scrollTop() > 50) {
            $('#hkdev-header').addClass('scrolled');
        } else {
            $('#hkdev-header').removeClass('scrolled');
        }
    });

    // ==========================================
    // 2. MOBILE MENU & MINI CART TOGGLE LOGIC
    // ==========================================
    const $overlay = $('#hkdev-common-overlay');
    const $mobileSidebar = $('#hkdev-mobile-sidebar');
    const $minicartSidebar = $('#hkdev-minicart-sidebar');
    const $body = $('body');

    function openSidebar($sidebar) {
        $sidebar.addClass('active');
        $overlay.addClass('active');
        $body.addClass('hkdev-no-scroll');
    }

    function closeSidebars() {
        $mobileSidebar.removeClass('active');
        $minicartSidebar.removeClass('active');
        $overlay.removeClass('active');
        $body.removeClass('hkdev-no-scroll');
    }

    // Open Mobile Menu
    $('#hkdev-mobile-toggle').off('click').on('click', function(e) {
        e.preventDefault();
        openSidebar($mobileSidebar);
    });

    // Open Mini Cart
    $('#hkdev-mini-cart-trigger').off('click').on('click', function(e) {
        e.preventDefault();
        openSidebar($minicartSidebar);
    });

    // Close Everything
    $('#hkdev-mobile-close, #hkdev-minicart-close, #hkdev-common-overlay').off('click').on('click', function(e) {
        e.preventDefault();
        closeSidebars();
    });

    // ==========================================
    // 3. SEARCH OVERLAY & AJAX SEARCH LOGIC
    // ==========================================
    const $searchOverlay = $('#hkdev-search-overlay');
    const $searchInput = $('#hkdev-ajax-search-input');
    const $searchOutput = $('#hkdev-ajax-search-output');
    
    // Open Search
    $('#hkdev-search-trigger').off('click').on('click', function(e) {
        e.preventDefault();
        $searchOverlay.addClass('active');
        $body.addClass('hkdev-no-scroll');
        setTimeout(function() {
            $searchInput.focus();
        }, 300);
    });

    // Close Search
    $('#hkdev-search-close').off('click').on('click', function(e) {
        e.preventDefault();
        $searchOverlay.removeClass('active');
        $body.removeClass('hkdev-no-scroll');
        $searchInput.val('');
        $searchOutput.empty();
    });

    // AJAX Search Request with Debounce
    let searchTimer;
    $searchInput.on('input', function() {
        clearTimeout(searchTimer);
        const keyword = $(this).val().trim();

        if (keyword.length < 2) {
            $searchOutput.empty();
            return;
        }

        $searchOutput.html('<div style="padding:20px;text-align:center;color:var(--hkdev-brand-color);"><i class="fa-solid fa-spinner fa-spin"></i> Searching...</div>');

        searchTimer = setTimeout(function() {
            $.ajax({
                url: hkdev_ajax_obj.ajax_url,
                type: 'GET',
                data: {
                    action: 'hkdev_search_action',
                    keyword: keyword
                },
                success: function(res) {
                    $searchOutput.html(res);
                }
            });
        }, 500); 
    });

    // ==========================================
    // 4. MINI CART QUANTITY UPDATE LOGIC
    // ==========================================
    $(document).off('click', '.hkdev-qty-btn').on('click', '.hkdev-qty-btn', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const $input = $btn.siblings('.hkdev-qty-input');
        let currentVal = parseInt($input.val());
        const cartItemKey = $btn.data('key');

        if ($btn.hasClass('plus')) {
            currentVal += 1;
        } else if ($btn.hasClass('minus')) {
            currentVal -= 1;
            if (currentVal < 1) currentVal = 1;
        }

        $input.val(currentVal);
        
        const $minicartBody = $('.hkdev-minicart-body');
        $minicartBody.css({'opacity': '0.5', 'pointer-events': 'none'});

        $.ajax({
            url: hkdev_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'hkdev_mc_update_qty',
                cart_item_key: cartItemKey,
                qty: currentVal,
                security: hkdev_ajax_obj.mc_nonce
            },
            success: function(res) {
                if (res && res.fragments) {
                    $.each(res.fragments, function(key, value) {
                        $(key).replaceWith(value);
                    });
                    $(document.body).trigger('wc_fragments_refreshed');
                }

                // মেইন পেজ যদি কার্ট পেজ হয়, সেটি রিফ্রেশ করা
                if ($('.woocommerce-cart-form').length > 0) {
                    $('[name="update_cart"]').prop('disabled', false).trigger('click');
                }
                
                // মেইন পেজ যদি চেকআউট পেজ হয়, সেটি রিফ্রেশ করা
                if ($('form.checkout').length > 0) {
                    $(document.body).trigger('update_checkout');
                }

                // আপনার কাস্টম চেকআউটের রিফ্রেশ
                if ($('#hkdev-co-items-ajax').length > 0) {
                    $.post(hkdev_ajax_obj.ajax_url, { action: 'hkdev_co_checkout_update_cart', security: hkdev_ajax_obj.co_nonce }, function(coRes) {
                        if(coRes.success) {
                            $('#hkdev-co-items-ajax').html(coRes.data.items_html);
                            $('#hkdev-co-totals-ajax').html(coRes.data.totals_html);
                        }
                    });
                }
            },
            complete: function() {
                $('.hkdev-minicart-body').css({'opacity': '1', 'pointer-events': 'auto'});
            }
        });
    });

    // ==========================================
    // 5. 🔥 SYNC MINI CART WITH MAIN CART/CHECKOUT
    // ==========================================
    // কার্ট বা চেকআউট পেজে কোনো আপডেট হলে মিনি কার্ট অটোমেটিক রিফ্রেশ হবে!
    $(document.body).on('updated_cart_totals updated_checkout', function() {
        $(document.body).trigger('wc_fragment_refresh');
    });

});