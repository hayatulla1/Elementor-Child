/**
 * HKDEV BOGO Offer Scripts - Complete & Fixed
 * Version: 2.1.0 (Global animation + Thank You page support)
 */

jQuery(document).ready(function($) {
    'use strict';

    // ============================================================================
    // 1. CONFIGURATION
    // ============================================================================
    
    const BOGO_CONFIG = {
        congratsTextTemplate: (typeof hkdev_bogo_vars !== 'undefined') ? hkdev_bogo_vars.congrats_msg : 'Congratulations! You got %d free item(s)!',
        ajaxUrl: (typeof hkdev_bogo_vars !== 'undefined') ? hkdev_bogo_vars.ajaxurl : (typeof hkdev_ajax_obj !== 'undefined' ? hkdev_ajax_obj.ajax_url : '/wp-admin/admin-ajax.php'),
        nonce: (typeof hkdev_bogo_vars !== 'undefined') ? hkdev_bogo_vars.nonce : '',
        toastDuration: 3000,
        confettiCount: 50,
        checkInterval: 800
    };

    const STATE = {
        previousCount: 0,
        isProcessing: false
    };

    // ============================================================================
    // 2. HELPER FUNCTIONS
    // ============================================================================

    /**
     * Convert Bengali digits to English
     */
    function benglaToEnglish(str) {
        const banglaDigits = { '০':0, '১':1, '२':2, '३':3, '४':4, '५':5, '६':6, '७':7, '८':8, '९':9 };
        if (!str) return '';
        return String(str).replace(/[०-९]/g, function(d) { return banglaDigits[d] || d; });
    }

    /**
     * Convert English digits to Bengali
     */
    function englishToBengla(number) {
        const banglaDigits = ['०', '१', '२', '३', '४', '५', '६', '७', '८', '९'];
        return String(number).replace(/\d/g, function(d) { return banglaDigits[d]; });
    }

    /**
     * ✅ Count free items from cart DOM (used on checkout/cart pages)
     */
    function countFreeItemsFromCart() {
        let totalFreeCount = 0;

        // Method 1: From item meta data (most accurate)
        $('.hkdev-co-summary-item, .hkdev-cart-item-row').each(function() {
            const freeCount = parseInt($(this).data('free-count')) || 0;
            if (freeCount > 0) {
                totalFreeCount += freeCount;
            }
        });

        // Method 2: Fallback - from badges
        if (totalFreeCount === 0) {
            $('.hkdev-bogo-cart-badge').each(function() {
                const badgeText = $(this).text();
                const match = badgeText.match(/(\d+)/);
                if (match) {
                    const count = parseInt(benglaToEnglish(match[1]));
                    if (!isNaN(count)) {
                        totalFreeCount += count;
                    }
                }
            });
        }

        // Method 3: From fee rows
        if (totalFreeCount === 0) {
            $('tr.fee').each(function() {
                const feeText = $(this).text();
                if (feeText.includes('Free') || feeText.includes('ফ্রি')) {
                    const match = feeText.match(/\(x(\d+)\)|x([०-९]+)/);
                    if (match) {
                        const count = parseInt(benglaToEnglish(match[1] || match[2]));
                        if (!isNaN(count)) {
                            totalFreeCount += count;
                        }
                    } else {
                        totalFreeCount += 1;
                    }
                }
            });
        }

        return Math.max(0, totalFreeCount);
    }

    /**
     * ✅ Update UI free item count display
     */
    function updateFreeItemCountDisplay() {
        const freeCount = countFreeItemsFromCart();

        // Calculate total items (sum of quantities)
        let totalItems = 0;
        $('.hkdev-co-summary-item, .hkdev-cart-item-row').each(function() {
            const qty = parseInt($(this).find('.hkdev-co-qty-val, .hkdev-qty-val').text()) || 0;
            totalItems += qty;
        });
        const paidCount = Math.max(0, totalItems - freeCount);
        
        const $countSummary = $('.hkdev-co-items-count-summary');
        if ($countSummary.length > 0) {
            const $freeSpan = $countSummary.find('.free-count strong');
            if ($freeSpan.length) {
                $freeSpan.text(freeCount);
            }
            const $paidSpan = $countSummary.find('.paid-count strong');
            if ($paidSpan.length) {
                $paidSpan.text(paidCount);
            }
        }

        return freeCount;
    }

    /**
     * ✅ Show congratulation toast
     */
    function showBogoMiddleToast(message) {
        $('.hkdev-bogo-toast').fadeOut(300, function() {
            $(this).remove();
        });

        setTimeout(function() {
            const toast = $('<div class="hkdev-bogo-toast"></div>');
            
            toast.html(message).css({
                'position': 'fixed',
                'top': '50%',
                'left': '50%',
                'transform': 'translate(-50%, -50%)',
                'background': 'linear-gradient(135deg, #28a745 0%, #20c997 100%)',
                'color': '#ffffff',
                'padding': '20px 40px',
                'border-radius': '50px',
                'box-shadow': '0 15px 35px rgba(0,0,0,0.3), 0 0 20px rgba(40, 167, 69, 0.5)',
                'z-index': '10000000',
                'font-size': '18px',
                'font-weight': 'bold',
                'text-align': 'center',
                'max-width': '500px',
                'border': '2px solid rgba(255,255,255,0.3)'
            });

            $('body').append(toast);
            toast.fadeIn(400);

            setTimeout(function() {
                toast.fadeOut(600, function() {
                    $(this).remove();
                });
            }, BOGO_CONFIG.toastDuration);
        }, 100);
    }

    /**
     * ✅ Trigger confetti / falling flowers animation
     */
    function triggerFlowerConfetti() {
        const flowers = ['🌸', '🌼', '🌺', '🌻', '💚', '✨', '🎉', '🎊', '🎈'];

        for (let i = 0; i < BOGO_CONFIG.confettiCount; i++) {
            const randomFlower = flowers[Math.floor(Math.random() * flowers.length)];
            const confetti = $('<div class="hkdev-confetti-piece"></div>');
            
            const startLeft = Math.random() * 100;
            const fontSize = (Math.random() * 1.5 + 1.2);
            const duration = (Math.random() * 3 + 2);
            const delay = Math.random() * 1.5;
            const rotation = Math.random() * 360;

            confetti.text(randomFlower).css({
                'position': 'fixed',
                'left': startLeft + 'vw',
                'top': '-50px',
                'font-size': fontSize + 'rem',
                'z-index': '9999999',
                'pointer-events': 'none',
                'opacity': '1',
                'transform': 'rotate(' + rotation + 'deg)',
                'animation': 'hkdev-fall ' + duration + 's linear ' + delay + 's forwards'
            });

            $('body').append(confetti);

            setTimeout(function() {
                confetti.remove();
            }, (duration + delay) * 1000 + 200);
        }
    }

    /**
     * ✅ Check and show BOGO notification (DOM-based, for checkout/cart pages)
     */
    function checkAndShowBogoNotification() {
        if (STATE.isProcessing) return;
        
        STATE.isProcessing = true;

        setTimeout(function() {
            const currentCount = countFreeItemsFromCart();
            const prevCount = STATE.previousCount;

            if (currentCount > prevCount) {
                const isBangla = /[\u0980-\u09FF]/.test(BOGO_CONFIG.congratsTextTemplate);
                const displayCount = isBangla ? englishToBengla(currentCount) : currentCount;
                
                const finalMessage = BOGO_CONFIG.congratsTextTemplate.replace('%d', displayCount);
                
                showBogoMiddleToast(finalMessage);
                triggerFlowerConfetti();
            }

            STATE.previousCount = currentCount;
            updateFreeItemCountDisplay();
            STATE.isProcessing = false;
        }, 300);
    }

    /**
     * ✅ Global: Fetch free item count from server and animate if increased.
     * Used on any page when a product is added to cart via AJAX.
     */
    function checkFreeItemsViaAjax(callback) {
        if (!BOGO_CONFIG.nonce) return;

        $.post(BOGO_CONFIG.ajaxUrl, {
            action: 'hkdev_recalc_bogo',
            nonce: BOGO_CONFIG.nonce
        }, function(response) {
            if (response && response.success) {
                const newCount = parseInt(response.data.free_count) || 0;
                if (typeof callback === 'function') {
                    callback(newCount);
                }
            }
        });
    }

    // ============================================================================
    // 3. INIT
    // ============================================================================

    $(document).ready(function() {
        // Initialize previous count from DOM (checkout/cart) or via server
        setTimeout(function() {
            const domCount = countFreeItemsFromCart();
            if (domCount > 0) {
                STATE.previousCount = domCount;
                updateFreeItemCountDisplay();
            } else {
                // Initialize from server for non-cart pages
                checkFreeItemsViaAjax(function(serverCount) {
                    STATE.previousCount = serverCount;
                });
            }
        }, 500);

        // ── Thank You page: auto-trigger falling flowers ──────────────────────
        if (typeof hkdev_ajax_obj !== 'undefined' && hkdev_ajax_obj.is_order_received === '1') {
            setTimeout(function() {
                triggerFlowerConfetti();
            }, 600);
        }
    });

    // ============================================================================
    // 4. EVENT LISTENERS
    // ============================================================================

    // WooCommerce checkout/cart events (DOM-based check)
    $(document.body).on('updated_cart_totals updated_checkout wc_fragments_loaded', function() {
        checkAndShowBogoNotification();
    });

    // Custom HKDEV checkout events (DOM-based check)
    $(document.body).on('hkdev_cart_updated hkdev_checkout_updated', function() {
        checkAndShowBogoNotification();
    });

    // ── Global: WooCommerce AJAX add-to-cart (fires on shop, product pages, etc.) ──
    $(document.body).on('added_to_cart', function() {
        checkFreeItemsViaAjax(function(newCount) {
            if (newCount > STATE.previousCount) {
                const isBangla = /[\u0980-\u09FF]/.test(BOGO_CONFIG.congratsTextTemplate);
                const displayCount = isBangla ? englishToBengla(newCount) : newCount;
                const finalMessage = BOGO_CONFIG.congratsTextTemplate.replace('%d', displayCount);

                showBogoMiddleToast(finalMessage);
                triggerFlowerConfetti();
            }
            STATE.previousCount = newCount;
        });
    });

    // Manual trigger helpers
    window.hkdevBogoCheck = function() {
        checkAndShowBogoNotification();
    };

    window.hkdevBogoReset = function() {
        STATE.previousCount = 0;
        checkAndShowBogoNotification();
    };

    window.hkdevTriggerFlowers = function() {
        triggerFlowerConfetti();
    };

    // ============================================================================
    // 5. MUTATION OBSERVER (checkout/cart pages)
    // ============================================================================

    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList' || mutation.type === 'characterData') {
                const $target = $(mutation.target);
                
                if ($target.closest('#hkdev-co-items-ajax').length || 
                    $target.closest('.hkdev-co-items-count-summary').length) {
                    checkAndShowBogoNotification();
                }
            }
        });
    });

    const observerConfig = {
        childList: true,
        subtree: true,
        attributes: true,
        characterData: true
    };

    const $itemsContainer = $('#hkdev-co-items-ajax, .cart-contents');
    $itemsContainer.each(function() {
        observer.observe(this, observerConfig);
    });

});