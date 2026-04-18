jQuery(document).ready(function($) {
    "use strict";

    // 🌐 Language Helper for JS
    function hkdevJsT(key) {
        const lang = (document.cookie.indexOf('hkdev_lang=en') !== -1) ? 'en' : 'bn';
        const dict = {
            'off_text': { bn: 'ছাড়!', en: 'Off!' },
            'in_stock': { bn: 'স্টকে আছে', en: 'In Stock' },
            'out_of_stock': { bn: 'স্টকে নেই', en: 'Out Of Stock' },
            'select_variation_alert': { bn: 'অনুগ্রহ করে অর্ডার করার আগে ভেরিয়েশন নির্বাচন করুন!', en: 'Please select variations before ordering!' },
            'add_to_cart_fail': { bn: 'প্রোডাক্টটি কার্টে যোগ করা যায়নি। আবার চেষ্টা করুন।', en: 'Could not add product to cart. Try again.' },
            'server_error': { bn: 'সার্ভারে সমস্যা হয়েছে। আবার চেষ্টা করুন।', en: 'Server error occurred. Please try again.' },
            'complete_order': { bn: 'অর্ডার সম্পূর্ণ করুন', en: 'Complete Order' }
        };
        return dict[key] ? dict[key][lang] : key;
    }

    // 1. Quantity Plus/Minus Buttons
    $(document).on('click', '.hkdev-sp-qty-btn', function() {
        const $input = $('#hkdev-sp-qty-field');
        let val = parseInt($input.val()) || 1;

        if ($(this).hasClass('plus')) {
            val += 1;
        } else if ($(this).hasClass('minus')) {
            val -= 1;
        }

        if (val < 1) val = 1;
        $input.val(val);
    });

    // 2. Variations Data
    const variations = $('.hkdev-sp-variable-options').data('variations') || [];
    
    // 3. Image Gallery Logic
    function updateMainImage(index) {
        const $thumbs = $('.hkdev-sp-thumb');
        if(index >= $thumbs.length) index = 0;
        if(index < 0) index = $thumbs.length - 1;
        
        const $target = $thumbs.eq(index);
        const fullSrc = $target.data('full');
        
        $thumbs.removeClass('active');
        $target.addClass('active');
        
        $('#hkdev-sp-viewport').removeClass('zoomed-active');
        
        $('#hkdev-sp-main-img').fadeOut(100, function() {
            $(this).attr('src', fullSrc).fadeIn(200);
        });
        
        const container = $('.hkdev-sp-thumbnails');
        if ($target.length) {
            container.animate({ 
                scrollLeft: $target.position().left + container.scrollLeft() - (container.width() / 2) + ($target.width() / 2) 
            }, 200);
        }
    }

    $(document).on('click', '.hkdev-sp-thumb', function() { 
        updateMainImage($('.hkdev-sp-thumb').index(this)); 
    });
    
    $(document).on('click', '#hkdev-sp-next-img', function(e) { 
        e.stopPropagation(); 
        updateMainImage($('.hkdev-sp-thumb.active').index() + 1); 
    });
    
    $(document).on('click', '#hkdev-sp-prev-img', function(e) { 
        e.stopPropagation(); 
        updateMainImage($('.hkdev-sp-thumb.active').index() - 1); 
    });

    // 4. Image Zoom Feature
    $(document).on('click', '#hkdev-sp-zoom-btn, #hkdev-sp-zoom-container', function(e) {
        if($(e.target).closest('.hkdev-sp-arrow').length) return;
        
        $('#hkdev-sp-viewport').toggleClass('zoomed-active');
        
        if($('#hkdev-sp-viewport').hasClass('zoomed-active')) {
            $('#hkdev-sp-main-img').css('transform', 'scale(2)');
        } else {
            $('#hkdev-sp-main-img').css('transform', 'scale(1)');
        }
    });

    $(document).on('mousemove', '#hkdev-sp-zoom-container', function(e) {
        if(!$('#hkdev-sp-viewport').hasClass('zoomed-active')) return;
        
        const offset = $(this).offset();
        const x = ((e.pageX - offset.left) / $(this).width()) * 100;
        const y = ((e.pageY - offset.top) / $(this).height()) * 100;
        
        $('#hkdev-sp-main-img').css('transform-origin', x + '% ' + y + '%');
    });

    // 6. Variation Selection logic
    $(document).on('click', '.hkdev-sp-swatch-item', function() {
        const row = $(this).closest('.hkdev-sp-variation-row');
        row.find('.hkdev-sp-swatch-item').removeClass('selected');
        $(this).addClass('selected');
        row.find('.selected-val').text($(this).attr('data-value')); 
        updateVariation();
    });

    function updateVariation() {
        let selectedAttrs = {};
        let allSelected = true;
        
        $('.hkdev-sp-variation-row').each(function() {
            const attr = $(this).data('attribute');
            const val = $(this).find('.hkdev-sp-swatch-item.selected').attr('data-value'); 
            
            if (val !== undefined && val !== "") {
                selectedAttrs[attr] = val; 
            } else {
                allSelected = false;
            }
        });
        
        if (!allSelected) return;
        
        const match = variations.find(v => {
            return Object.keys(selectedAttrs).every(key => {
                let vAttrVal = v.attributes[key];
                let selectedVal = selectedAttrs[key];
                if (vAttrVal === "") return true; 
                return String(vAttrVal).toLowerCase() === String(selectedVal).toLowerCase();
            });
        });
        
        if (match) {
            $('.hkdev-sp-price-box').html(match.price_html);
            
            const $badge = $('.hkdev-sp-sale-badge');
            if (match.discount_percentage > 0) {
                $badge.text(match.discount_percentage + '% ' + hkdevJsT('off_text')).show();
            } else {
                $badge.hide();
            }

            if (match.image && match.image.src) {
                $('#hkdev-sp-main-img').attr('src', match.image.src);
                $('.hkdev-sp-thumb').removeClass('active');
            }
            
            $('.sku-val').text(match.sku || 'N/A');
            $('.stock-val').html(match.is_in_stock ? '<span class="in-stock-pill">' + hkdevJsT('in_stock') + '</span>' : '<span class="out-stock-pill">' + hkdevJsT('out_of_stock') + '</span>');
            
            $('#hkdev-sp-add-to-cart, #hkdev-sp-buy-now').attr('data-variation-id', match.variation_id).data('variation-id', match.variation_id);
        }
    }

    // 7. AJAX Add to Cart & Buy Now
    $(document).off('click', '#hkdev-sp-add-to-cart, #hkdev-sp-buy-now').on('click', '#hkdev-sp-add-to-cart, #hkdev-sp-buy-now', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const isBuyNow = $btn.attr('id') === 'hkdev-sp-buy-now';
        
        if (isBuyNow && $btn.hasClass('checkout-active')) { 
            window.location.href = $btn.data('checkout-url'); 
            return; 
        }
        
        let pId = $btn.attr('data-product-id');
        let vId = $btn.attr('data-variation-id');
        let qty = $('#hkdev-sp-qty-field').val() || 1;

        if ($('.hkdev-sp-variable-options').length > 0 && (!vId || vId == 0 || vId === "0")) { 
            alert(hkdevJsT('select_variation_alert')); 
            return; 
        }

        if($btn.prop('disabled')) return;
        $btn.prop('disabled', true).css('opacity', '0.7');
        
        const ajaxUrl = (typeof hkdev_ajax_obj !== 'undefined' && hkdev_ajax_obj.ajax_url) ? hkdev_ajax_obj.ajax_url : '/wp-admin/admin-ajax.php';

        $.ajax({
            type: 'POST',
            url: ajaxUrl,
            data: { 
                action: 'hkdev_ajax_add_to_cart', 
                product_id: pId, 
                variation_id: vId, 
                quantity: qty 
            },
            success: function(res) {
                $btn.prop('disabled', false).css('opacity', '1');
                if (res.success) {
                    $(document.body).trigger('added_to_cart', [res.data.fragments, res.data.cart_hash, $btn]);
                    if (isBuyNow) {
                        window.location.href = $btn.data('checkout-url');
                    } else {
                        if ($btn.attr('id') === 'hkdev-sp-add-to-cart') { 
                            $('#hkdev-sp-buy-now').addClass('checkout-active').find('.btn-text').text(hkdevJsT('complete_order')); 
                        }
                        // Toast removed from here
                    }
                } else { 
                    alert(hkdevJsT('add_to_cart_fail')); 
                }
            },
            error: function() {
                $btn.prop('disabled', false).css('opacity', '1');
                alert(hkdevJsT('server_error'));
            }
        });
    });

    // 8. Description & Review Tabs
    $(document).on('click', '.hkdev-sp-tab-link', function() {
        $('.hkdev-sp-tab-link, .hkdev-sp-tab-content').removeClass('active');
        $(this).addClass('active');
        $('#' + $(this).data('tab')).addClass('active');
    });

    // 9. Size Chart Modal Logic
    $(document).on('click', '#hkdev-size-chart-btn', function(e) {
        e.preventDefault();
        $('#hkdev-size-chart-modal').css('display', 'flex').hide().fadeIn(200);
    });

    $(document).on('click', '#hkdev-size-chart-close', function() {
        $('#hkdev-size-chart-modal').fadeOut(200);
    });

    $(document).on('click', '#hkdev-size-chart-modal', function(e) {
        if (e.target === this) {
            $(this).fadeOut(200);
        }
    });

});