jQuery(document).ready(function($) {
    "use strict";

    /* =========================================================================
     * 1. LANGUAGE HELPER & TRANSLATIONS
     * ========================================================================= */
    function hkdevJsT(key) {
        const lang = (document.cookie.indexOf('hkdev_lang=en') !== -1) ? 'en' : 'bn';
        const dict = {
            'confirm_remove_item': { bn: 'আপনি কি পণ্যটি ঝুড়ি থেকে সরাতে চান?', en: 'Are you sure you want to remove this item from the cart?' },
            'empty_coupon': { bn: 'অনুগ্রহ করে কুপন কোড লিখুন!', en: 'Please enter a coupon code!' },
            'apply_btn': { bn: 'এপ্লাই করুন', en: 'Apply' },
            'invalid_phone': { bn: 'অনুগ্রহ করে সঠিক মোবাইল নম্বর দিন।', en: 'Please enter a valid mobile number.' },
            'required_field_missing': { bn: 'অনুগ্রহ করে সকল প্রয়োজনীয় তথ্য (*) পূরণ করুন।', en: 'Please fill in all required fields (*).' },
            'invalid_email': { bn: 'অনুগ্র করে সঠিক ইমেইল দিন।', en: 'Please enter a valid email.' },
            'processing': { bn: 'প্রসেস হচ্ছে...', en: 'Processing...' },
            'order_error': { bn: 'অর্ডার করতে সমস্যা হয়েছে।', en: 'There was a problem processing your order.' },
            'confirm_order': { bn: 'অর্ডার কনফার্ম করুন', en: 'Confirm Order' }
        };
        return dict[key] ? dict[key][lang] : key;
    }

    const ajaxUrl = hkdev_ajax_obj.ajax_url;
    
    function showToast(message, type = 'success') {
        const $toast = $('#hkdev-co-toast');
        $toast.find('.toast-msg').html(message); 
        if (type === 'error') {
            $toast.addClass('error');
            $toast.find('.toast-icon i').attr('class', 'fa-solid fa-circle-xmark');
        } else {
            $toast.removeClass('error');
            $toast.find('.toast-icon i').attr('class', 'fa-solid fa-circle-check');
        }
        $toast.addClass('show');
        setTimeout(() => { $toast.removeClass('show'); }, 3500); 
    }
    
    /* =========================================================================
     * 3. AJAX CART UPDATER (NO BLUR)
     * ========================================================================= */

    // Apply mini-cart + cart-count fragments returned by every cart-update response.
    function applyCartFragments(data) {
        if (data.cart_count !== undefined) {
            $('.hkdev-nav-cart-count').text(data.cart_count);
        }
        if (data.minicart_body_html) {
            $('div.hkdev-minicart-body').replaceWith(data.minicart_body_html);
        }
        $(document.body).trigger('wc_fragments_refreshed');
    }

    function updateCartSections() {
        $.post(ajaxUrl, $('#hkdev-co-process-order').serialize() + '&action=hkdev_co_checkout_update_cart&security=' + hkdev_ajax_obj.co_nonce, function(res) {
            if(res.success) {
                $('#hkdev-co-items-ajax').html(res.data.items_html);
                $('#hkdev-co-totals-ajax').html(res.data.totals_html);
                applyCartFragments(res.data);
            }
        });
    }

    /* =========================================================================
     * 4. CART ITEM MODIFICATIONS
     * ========================================================================= */
    $(document).on('click', '.hkdev-co-qty-mod', function() {
        const $btn    = $(this);
        const $item   = $btn.closest('.hkdev-co-summary-item');
        const key     = $item.data('key');
        const current = parseInt($item.find('.hkdev-co-qty-val').text()) || 1;
        const newQty  = $btn.data('act') === 'plus' ? current + 1 : current - 1;

        if(newQty < 1) return;

        // Visual loading state — disable stepper while request is in-flight.
        $item.find('.hkdev-co-qty-stepper-ui').css({'opacity': '0.5', 'pointer-events': 'none'});

        $.post(ajaxUrl, { action: 'hkdev_co_checkout_update_cart', type: 'qty', key: key, qty: newQty, security: hkdev_ajax_obj.co_nonce }, function(res) {
            if(res.success) {
                $('#hkdev-co-items-ajax').html(res.data.items_html);
                $('#hkdev-co-totals-ajax').html(res.data.totals_html);
                applyCartFragments(res.data);
            }
        }).fail(function() {
            // Re-enable stepper on failure
            $item.find('.hkdev-co-qty-stepper-ui').css({'opacity': '1', 'pointer-events': 'auto'});
        });
    });

    $(document).on('click', '.hkdev-co-item-remove-trigger', function() {
        if(!confirm(hkdevJsT('confirm_remove_item'))) return;

        const key = $(this).closest('.hkdev-co-summary-item').data('key');

        $.post(ajaxUrl, { action: 'hkdev_co_checkout_update_cart', type: 'remove', key: key, security: hkdev_ajax_obj.co_nonce }, function(res) {
            if(res.data && res.data.cart_empty) {
                location.reload();
            } else if (res.success) {
                $('#hkdev-co-items-ajax').html(res.data.items_html);
                $('#hkdev-co-totals-ajax').html(res.data.totals_html);
                applyCartFragments(res.data);
            }
        });
    });

    /* =========================================================================
     * 5. COUPON MANAGEMENT
     * ========================================================================= */
    $(document).on('click', '#hkdev-co-apply-coupon', function(e) {
        e.preventDefault();
        const code = $('#hkdev-co-coupon-code').val().trim();
        if (code === '') { showToast(hkdevJsT('empty_coupon'), 'error'); return; }
        
        const $btn = $(this);
        $btn.prop('disabled', true).text('...');
        
        $.post(ajaxUrl, { action: 'hkdev_co_apply_coupon', coupon_code: code, security: hkdev_ajax_obj.co_nonce }, function(res) {
            $btn.prop('disabled', false).text(hkdevJsT('apply_btn'));
            if(res.success) {
                $('#hkdev-co-items-ajax').html(res.data.items_html);
                $('#hkdev-co-totals-ajax').html(res.data.totals_html);
                $('#hkdev-co-coupon-code').val(''); 
                applyCartFragments(res.data);
                showToast(res.data.message, 'success');
            } else {
                showToast(res.data.message, 'error');
            }
        });
    });

    $(document).on('click', '.hkdev-co-remove-coupon', function(e) {
        e.preventDefault();
        const code = $(this).data('coupon');
        
        $.post(ajaxUrl, { action: 'hkdev_co_remove_coupon', coupon_code: code, security: hkdev_ajax_obj.co_nonce }, function(res) {
            if(res.success) {
                $('#hkdev-co-items-ajax').html(res.data.items_html);
                $('#hkdev-co-totals-ajax').html(res.data.totals_html);
                applyCartFragments(res.data);
                showToast(res.data.message, 'success');
            }
        });
    });

    /* =========================================================================
     * 6. SHIPPING / PAYMENT SELECT TRIGGER
     * ========================================================================= */
    $(document).on('change', 'input[name^="shipping_method"], input[name="payment_method"], select[name="billing_country"], select[name="billing_state"]', function() {
        if($(this).attr('type') === 'radio') {
            $(this).closest('.hkdev-co-delivery-selection-wrap, .hkdev-co-payment-pill-list').find('label').removeClass('active');
            $(this).parent().addClass('active');
        }
        updateCartSections();
    });

    /* =========================================================================
     * 7. ORDER SUBMISSION
     * ========================================================================= */
    $('#hkdev-co-process-order').on('submit', function(e) {
        e.preventDefault();
        let hasError = false;

        $('.hkdev-co-form-area').find('input:visible, select:visible, textarea:visible').each(function() {
            const isRequired = $(this).prop('required') || $(this).closest('.validate-required').length > 0;
            if (isRequired && $(this).val().trim() === '') {
                showToast(hkdevJsT('required_field_missing'), 'error');
                $(this).focus();
                hasError = true;
                return false; 
            }
        });

        if (hasError) return false;

        const $phoneField = $('input[name="billing_phone"]:visible');
        if ($phoneField.length) {
            const phone = $phoneField.val().trim();
            const phoneRegex = /^(?:\+?88)?01[3-9]\d{8}$/;
            if(!phoneRegex.test(phone)) {
                showToast(hkdevJsT('invalid_phone'), 'error');
                $phoneField.focus();
                return false;
            }
        }

        const $btn = $('#hkdev-co-submit-btn');
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin" style="margin-right:8px;"></i> ' + hkdevJsT('processing'));
        $('#hkdev-co-global-loader').css('display', 'flex');
        
        $.post(ajaxUrl, $(this).serialize(), function(res) {
            if(res.success) {
                window.location.href = res.data.redirect;
            } else {
                showToast(res.data.message || hkdevJsT('order_error'), 'error');
                $btn.prop('disabled', false).html('<i class="fa-solid fa-lock" style="margin-right: 8px;"></i> ' + hkdevJsT('confirm_order'));
                $('#hkdev-co-global-loader').hide();
            }
        }).fail(function() {
            showToast(hkdevJsT('order_error'), 'error');
            $btn.prop('disabled', false).html('<i class="fa-solid fa-lock" style="margin-right: 8px;"></i> ' + hkdevJsT('confirm_order'));
            $('#hkdev-co-global-loader').hide();
        });
    });
});