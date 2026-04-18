<?php
/**
 * Plugin/Snippet Name: HKDEV Custom WooCommerce Cart
 * Shortcode: [hkdev_cart]
 * Description: Brand matched Custom AJAX Cart Page with 100% Native Hooks for BOGO & Fees.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ===================================================================
// ⚙️ HKDEV CART SETTINGS (YES / NO CONTROL)
// ===================================================================
define('HKDEV_SHOW_ITEM_IMAGE', 'yes');          // প্রোডাক্টের ছবি দেখাবে কিনা?
define('HKDEV_SHOW_ITEM_PRICE', 'yes');          // প্রোডাক্টের একক মূল্য (Unit Price) দেখাবে কিনা?
define('HKDEV_SHOW_ITEM_SUBTOTAL', 'yes');       // প্রোডাক্টের মোট মূল্য (Item Subtotal) দেখাবে কিনা?
define('HKDEV_SHOW_REMOVE_BTN', 'yes');          // প্রোডাক্ট ডিলিট করার বাটন দেখাবে কিনা?
define('HKDEV_SHOW_COUPON_FORM', 'yes');         // কুপন কোড বসানোর অপশন দেখাবে কিনা?
define('HKDEV_SHOW_CART_SUBTOTAL', 'yes');       // কার্টের সাবটোটাল হিসাব দেখাবে কিনা?
define('HKDEV_SHOW_SHIPPING', 'yes');            // শিপিং চার্জ দেখাবে কিনা?


// ===================================================================
// ১. মূল শর্টকোড ফাংশন
// ===================================================================
function hkdev_custom_cart_shortcode() {
    if ( ! class_exists( 'WooCommerce' ) ) return 'WooCommerce plugin is not active.';
    if ( is_admin() ) return;

    ob_start();
    ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <?php
    if ( WC()->cart->is_empty() ) {
        ?>
        <div class="hkdev-cart-empty-wrap">
            <i class="fa-solid fa-cart-arrow-down" style="font-size: 60px; color: #d1d5db; margin-bottom: 25px;"></i>
            <h3><?php echo function_exists('hkdev_t') ? hkdev_t('cart_empty') : 'Your cart is empty'; ?></h3>
            <p><?php echo function_exists('hkdev_t') ? hkdev_t('cart_empty_sub') : 'Browse our products and add to cart.'; ?></p>
            <a href="<?php echo get_permalink( wc_get_page_id( 'shop' ) ); ?>" class="hkdev-cart-primary-btn" style="display: inline-block; width: auto; padding: 15px 40px;"><?php echo function_exists('hkdev_t') ? hkdev_t('start_shop') : 'Start Shopping'; ?></a>
        </div>
        <?php
        return ob_get_clean();
    }

    // 🚨 CRITICAL FIX FOR INITIAL LOAD: Force recalculate totals & fees before generating HTML
    WC()->cart->calculate_totals();
    ?>

    <div class="hkdev-cart-container" id="hkdev-cart-root">
        
        <?php 
        // WOOCOMMERCE HOOK: Before Cart
        do_action( 'woocommerce_before_cart' ); 
        ?>

        <div class="hkdev-cart-header">
            <h2><?php echo function_exists('hkdev_t') ? hkdev_t('shopping_cart') : 'Shopping Cart'; ?></h2>
            <p><?php echo function_exists('hkdev_t') ? hkdev_t('selected_products') : 'Selected Products'; ?></p>
        </div>

        <div class="hkdev-cart-grid">
            <div class="hkdev-cart-items-column">
                <div class="hkdev-cart-card">
                    <div id="hkdev-cart-items-area">
                        <?php echo hkdev_get_cart_items_html(); ?>
                    </div>

                    <?php if ( HKDEV_SHOW_COUPON_FORM === 'yes' && wc_coupons_enabled() ) : ?>
                    <div class="hkdev-cart-coupon-wrap">
                        <div class="coupon-box">
                            <input type="text" id="hkdev-coupon-input" placeholder="<?php echo esc_attr(function_exists('hkdev_t') ? hkdev_t('coupon_ph') : 'Coupon Code'); ?>">
                            <button type="button" id="hkdev-apply-coupon-btn"><?php echo function_exists('hkdev_t') ? hkdev_t('apply') : 'Apply'; ?></button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="hkdev-cart-totals-column">
                <div class="hkdev-cart-card hkdev-sticky-sidebar">
                    <div class="hkdev-cart-totals-header">
                        <h3><?php echo function_exists('hkdev_t') ? hkdev_t('cart_summary') : 'Cart Summary'; ?></h3>
                    </div>
                    
                    <div id="hkdev-cart-totals-area">
                        <?php echo hkdev_get_cart_totals_html(); ?>
                    </div>
                    
                    <?php 
                    // WOOCOMMERCE HOOK: Proceed to Checkout Button Location
                    do_action( 'woocommerce_proceed_to_checkout' ); 
                    ?>
                    
                    <a href="<?php echo get_permalink( wc_get_page_id( 'shop' ) ); ?>" class="hkdev-cart-secondary-btn"><?php echo function_exists('hkdev_t') ? hkdev_t('shop_more') : 'Continue Shopping'; ?></a>
                </div>
            </div>
        </div>
        
        <?php 
        // WOOCOMMERCE HOOK: After Cart
        do_action( 'woocommerce_after_cart' ); 
        ?>

        <div id="hkdev-cart-global-loader"><div class="loader-box-inner"><i class="fa-solid fa-circle-notch fa-spin"></i> &nbsp; <?php echo function_exists('hkdev_t') ? hkdev_t('updating') : 'Updating...'; ?></div></div>
    </div>
    <input type="hidden" id="hkdev_cart_nonce" value="<?php echo wp_create_nonce('hkdev_cart_nonce'); ?>">
    <?php
    return ob_get_clean();
}
add_shortcode('hkdev_cart', 'hkdev_custom_cart_shortcode');

// ===================================================================
// ২. NATIVE HOOK OVERRIDE: Customizing the Default Checkout Button
// ===================================================================
// এটি উকমার্সের ডিফল্ট বাটন মুছে আপনার স্টাইলের বাটনটি নেটিভভাবে অ্যাড করবে
remove_action( 'woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20 );
add_action( 'woocommerce_proceed_to_checkout', 'hkdev_native_custom_checkout_button', 20 );

function hkdev_native_custom_checkout_button() {
    $checkout_text = function_exists('hkdev_t') ? hkdev_t('checkout') : 'Proceed to Checkout';
    ?>
    <a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="checkout-button button alt wc-forward hkdev-cart-primary-btn proceed-btn">
        <?php echo esc_html( $checkout_text ); ?> <i class="fa-solid fa-arrow-right"></i>
    </a>
    <?php
}

// ===================================================================
// ৩. Cart Items HTML Generator (With Professional Native Hooks)
// ===================================================================
function hkdev_get_cart_items_html() {
    ob_start();
    
    // WOOCOMMERCE HOOK: Before Cart Contents
    do_action( 'woocommerce_before_cart_contents' );
    ?>
    <div class="hkdev-cart-items-list">
        <?php foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) : 
            $_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
            $product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );
            
            if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
                $product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
                
                // Native Class Filter
                $row_class = apply_filters( 'woocommerce_cart_item_class', 'hkdev-cart-item-row', $cart_item, $cart_item_key );
                ?>
                <div class="<?php echo esc_attr( $row_class ); ?>" data-key="<?php echo esc_attr($cart_item_key); ?>">
                    
                    <?php if ( HKDEV_SHOW_ITEM_IMAGE === 'yes' ) : ?>
                    <div class="item-img">
                        <?php
                        $thumbnail = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image('thumbnail'), $cart_item, $cart_item_key );
                        if ( ! $product_permalink ) { echo $thumbnail; } else { printf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $thumbnail ); }
                        ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="item-details">
                        <h4 class="item-title">
                            <?php
                            $item_name = $_product->get_name();
                            $linked_name = $product_permalink ? sprintf( '<a href="%s">%s</a>', esc_url( $product_permalink ), esc_html($item_name) ) : esc_html($item_name);
                            echo apply_filters( 'woocommerce_cart_item_name', $linked_name, $cart_item, $cart_item_key );
                            
                            // WOOCOMMERCE HOOK: Shows product Variations & Add-ons
                            do_action( 'woocommerce_after_cart_item_name', $cart_item, $cart_item_key );
                            echo wc_get_formatted_cart_item_data( $cart_item );
                            
                            // Backorder notification
                            if ( $_product->backorders_require_notification() && $_product->is_on_backorder( $cart_item['quantity'] ) ) {
                                echo wp_kses_post( apply_filters( 'woocommerce_cart_item_backorder_notification', '<p class="backorder_notification">' . esc_html__( 'Available on backorder', 'woocommerce' ) . '</p>', $product_id ) );
                            }
                            ?>
                        </h4>
                        
                        <?php if ( HKDEV_SHOW_ITEM_PRICE === 'yes' ) : ?>
                        <div class="item-price-unit">
                            <?php echo apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key ); ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="hkdev-cart-qty-wrap">
                            <?php if ( $_product->is_sold_individually() ) : ?>
                                <span class="hkdev-qty-val">1</span>
                            <?php else : ?>
                                <div class="hkdev-qty-stepper-ui">
                                    <button type="button" class="hkdev-qty-mod minus" data-act="minus">−</button>
                                    <span class="hkdev-qty-val"><?php echo $cart_item['quantity']; ?></span>
                                    <button type="button" class="hkdev-qty-mod plus" data-act="plus">+</button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ( HKDEV_SHOW_REMOVE_BTN === 'yes' ) : ?>
                                <button type="button" class="hkdev-item-remove-btn" title="<?php echo esc_attr(function_exists('hkdev_t') ? hkdev_t('remove') : 'Remove'); ?>"><i class="fa-solid fa-trash-can"></i></button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ( HKDEV_SHOW_ITEM_SUBTOTAL === 'yes' ) : ?>
                    <div class="item-total-price">
                        <?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); ?>
                    </div>
                    <?php endif; ?>

                </div>
                <?php
            }
        endforeach; ?>
    </div>
    <?php
    // WOOCOMMERCE HOOK: After Cart Contents
    do_action( 'woocommerce_cart_contents' );
    do_action( 'woocommerce_after_cart_contents' );
    
    return ob_get_clean();
}

// ===================================================================
// ৪. Cart Totals HTML Generator
// ===================================================================
function hkdev_get_cart_totals_html() {
    ob_start();
    
    // WOOCOMMERCE HOOK: Before Cart Totals Table
    do_action( 'woocommerce_before_cart_totals' );
    ?>
    <div class="hkdev-cart-calc-wrap">
        
        <?php if ( HKDEV_SHOW_CART_SUBTOTAL === 'yes' ) : ?>
        <div class="calc-line"><span><?php echo function_exists('hkdev_t') ? hkdev_t('subtotal') : 'Subtotal'; ?></span><strong><?php echo WC()->cart->get_cart_subtotal(); ?></strong></div>
        <?php endif; ?>
        
        <?php 
        // Coupon Logic
        foreach ( WC()->cart->get_coupons() as $code => $coupon ) : 
            $coupon_obj = new WC_Coupon($code);
            $discount_type = $coupon_obj->get_discount_type();
            $coupon_amount = $coupon_obj->get_amount();
            
            $display_label = esc_html($code);
            if ($discount_type === 'percent') {
                $display_label .= ' (' . floatval($coupon_amount) . '%)';
            }
        ?>
            <div class="calc-line coupon-line">
                <span><i class="fa-solid fa-tag"></i> <?php echo function_exists('hkdev_t') ? hkdev_t('coupon') : 'Coupon'; ?> <?php echo $display_label; ?></span>
                <span>
                    <strong>-<?php echo wc_price( WC()->cart->get_coupon_discount_amount( $code ) ); ?></strong>
                    <a href="#" class="hkdev-remove-coupon" data-coupon="<?php echo esc_attr( $code ); ?>" style="color:var(--hkdev-brand-secondary, #b32d2e); font-size:13px; margin-left:5px; text-decoration:underline;">[<?php echo function_exists('hkdev_t') ? hkdev_t('remove') : 'Remove'; ?>]</a>
                </span>
            </div>
        <?php endforeach; ?>

        <?php 
        // Fees Loop (Displays BOGO or extra charges)
        foreach ( WC()->cart->get_fees() as $fee ) : ?>
            <div class="calc-line fee-line" style="color: #2e7d32; font-weight: 500;">
                <span><i class="fa-solid fa-gift"></i> <?php echo esc_html( $fee->name ); ?></span>
                <strong><?php echo wc_price( $fee->total ); ?></strong>
            </div>
        <?php endforeach; ?>

        <?php 
        // Setting Control & Native Checking: Shipping
        if ( HKDEV_SHOW_SHIPPING === 'yes' && WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : 
            do_action( 'woocommerce_cart_totals_before_shipping' ); 
        ?>
            <div class="calc-line"><span><?php echo function_exists('hkdev_t') ? hkdev_t('shipping') : 'Shipping Charge'; ?></span><strong><?php echo wc_cart_totals_shipping_html(); ?></strong></div>
        <?php 
            do_action( 'woocommerce_cart_totals_after_shipping' ); 
        endif; 
        ?>

        <?php do_action( 'woocommerce_cart_totals_before_order_total' ); ?>

        <div class="calc-line grand-total-line">
            <span><?php echo function_exists('hkdev_t') ? hkdev_t('grand_total') : 'Grand Total'; ?></span>
            <strong><?php echo WC()->cart->get_total(); ?></strong>
        </div>

        <?php do_action( 'woocommerce_cart_totals_after_order_total' ); ?>
    </div>
    <?php
    // WOOCOMMERCE HOOK: After Cart Totals Table
    do_action( 'woocommerce_after_cart_totals' );
    
    return ob_get_clean();
}

// ===================================================================
// ৫. AJAX Handlers for Cart Update
// ===================================================================
add_action('wp_ajax_hkdev_update_cart_ajax', 'hkdev_ajax_cart_update_handler');
add_action('wp_ajax_nopriv_hkdev_update_cart_ajax', 'hkdev_ajax_cart_update_handler');

function hkdev_ajax_cart_update_handler() {
    check_ajax_referer('hkdev_cart_nonce', 'security');

    $type = isset($_POST['update_type']) ? sanitize_text_field($_POST['update_type']) : '';
    
    if ( $type === 'qty' && isset($_POST['cart_key'], $_POST['new_qty']) ) {
        WC()->cart->set_quantity( sanitize_text_field($_POST['cart_key']), absint($_POST['new_qty']), true );
    } 
    elseif ( $type === 'remove' && isset($_POST['cart_key']) ) {
        WC()->cart->remove_cart_item( sanitize_text_field($_POST['cart_key']) );
    }
    elseif ( $type === 'apply_coupon' && !empty($_POST['coupon_code']) ) {
        WC()->cart->add_discount( sanitize_text_field($_POST['coupon_code']) );
    }
    elseif ( $type === 'remove_coupon' && !empty($_POST['coupon_code']) ) {
        WC()->cart->remove_coupon( sanitize_text_field($_POST['coupon_code']) );
    }

    // Recalculate totals after any modification
    WC()->cart->calculate_totals();

    if ( WC()->cart->is_empty() ) {
        wp_send_json_success( array( 'is_empty' => true ) );
    } else {
        wp_send_json_success( array(
            'items_html'  => hkdev_get_cart_items_html(),
            'totals_html' => hkdev_get_cart_totals_html()
        ));
    }
}