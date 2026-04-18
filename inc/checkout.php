<?php
/**
 * Plugin/Snippet Name: HKDEV Custom WooCommerce Checkout
 * Shortcode: [hkdev_bd_checkout]
 * Description: Premium Brand matched Single Page Checkout with Dynamic Coupon, Instant Fees (BOGO) & 100% Native Hooks.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* =========================================================================
 * 1. GLOBAL FIELD CONFIGURATION (YES / NO)
 * ========================================================================= */
$hkdev_field_config = array(
    // ৩টি ম্যান্ডেটরি ফিল্ড (সব 'yes' থাকবে)
    'billing_first_name' => 'yes', 
    'billing_phone'      => 'yes',
    'billing_address_1'  => 'yes',

    // বাকি সব ফিল্ড 'no' থাকবে (যাতে ফর্ম এ না দেখায়)
    'billing_address_2'  => 'no',   
    'billing_last_name'  => 'no',
    'billing_company'    => 'no',
    'billing_country'    => 'no',   
    'billing_city'       => 'no',   
    'billing_state'      => 'no',
    'billing_postcode'   => 'no',
    'billing_email'      => 'no',   
    'order_comments'     => 'no',
);

/* =========================================================================
 * 2. PHONE NUMBER VALIDATION & SANITIZATION (BD Format)
 * ========================================================================= */
add_action( 'woocommerce_checkout_process', 'hkdev_validate_bd_phone_number_mobile' );
function hkdev_validate_bd_phone_number_mobile() {
    if ( isset( $_POST['billing_phone'] ) ) {
        $phone = trim( $_POST['billing_phone'] );
        if ( ! preg_match( '/^(?:\+?88)?01[3-9]\d{8}$/', $phone ) ) {
            wc_add_notice( 'Invalid Phone Number', 'error' );
        }
    }
}

add_filter( 'woocommerce_checkout_posted_data', 'hkdev_sanitize_bd_phone_number_mobile' );
function hkdev_sanitize_bd_phone_number_mobile( $data ) {
    if ( isset( $data['billing_phone'] ) ) {
        $data['billing_phone'] = preg_replace( '/[^0-9+]/', '', $data['billing_phone'] );
    }
    return $data;
}

/* =========================================================================
 * 3. WOOCOMMERCE FIELD FILTER (Native Hooks)
 * ========================================================================= */
add_filter( 'woocommerce_checkout_fields', 'hkdev_custom_checkout_fields_filter', 9999 );
function hkdev_custom_checkout_fields_filter( $fields ) {
    global $hkdev_field_config;

    // ১. Full Name 
    $fields['billing']['billing_first_name']['label']       = 'Full Name';
    $fields['billing']['billing_first_name']['placeholder'] = 'e.g. Md. Abdullah';
    $fields['billing']['billing_first_name']['required']    = true;
    $fields['billing']['billing_first_name']['class']       = array('hkdev-co-form-group');

    // ২. Mobile Number 
    $fields['billing']['billing_phone']['label']            = 'Mobile Number';
    $fields['billing']['billing_phone']['placeholder']      = '017XXXXXXXX';
    $fields['billing']['billing_phone']['required']         = true; 
    $fields['billing']['billing_phone']['class']            = array('hkdev-co-form-group');
    
    // ৩. Full Address 
    $fields['billing']['billing_address_1']['label']        = 'Full Address';
    $fields['billing']['billing_address_1']['placeholder']  = 'House, Road, Thana, District';
    $fields['billing']['billing_address_1']['required']     = true;
    $fields['billing']['billing_address_1']['class']        = array('hkdev-co-form-group');

    // Apply YES/NO toggles
    foreach ( $hkdev_field_config as $field_key => $status ) {
        if ( $status === 'no' ) {
            if ( isset( $fields['billing'][$field_key] ) ) unset( $fields['billing'][$field_key] );
            if ( isset( $fields['order'][$field_key] ) ) unset( $fields['order'][$field_key] );
        }
    }

    return $fields;
}

/* =========================================================================
 * 4. MAIN CHECKOUT SHORTCODE
 * ========================================================================= */
add_shortcode('hkdev_bd_checkout', 'hkdev_custom_checkout_shortcode');
function hkdev_custom_checkout_shortcode() {
    if ( ! class_exists( 'WooCommerce' ) ) return 'WooCommerce plugin is not active.';
    if ( is_admin() ) return;

    ob_start();

    // --- A. THANK YOU PAGE LOGIC ---
    $order_id = isset($_GET['order-received']) ? absint($_GET['order-received']) : get_query_var('order-received');
    if ( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            echo '<div class="hkdev-co-error-msg">Order Not Found</div>';
            return ob_get_clean();
        }

        $payment_method_title = $order->get_payment_method_title();
        if ( empty( $payment_method_title ) ) {
            $payment_method_title = 'Cash On Delivery';
        }
        
        $qr_code_url = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($order->get_checkout_order_received_url());
        ?>
        <div class="hkdev-co-thankyou-container fade-in">
            <div class="hkdev-co-success-hero">
                <div class="success-anim-icon"><i class="fa-solid fa-circle-check"></i></div>
                <h2>Order Successful!</h2>
                <p>Order # <strong>#<?php echo esc_html( $order->get_order_number() ); ?></strong></p>
                <p class="sub-text">Thank you for shopping with us.</p>
            </div>

            <div class="hkdev-co-info-cards-grid">
                <div class="hkdev-co-info-card"><span class="label">Date</span><span class="val"><?php echo wc_format_datetime( $order->get_date_created() ); ?></span></div>
                <div class="hkdev-co-info-card"><span class="label">Total</span><span class="val"><?php echo $order->get_formatted_order_total(); ?></span></div>
                <div class="hkdev-co-info-card"><span class="label">Payment Method</span><span class="val"><?php echo esc_html($payment_method_title); ?></span></div>
            </div>

            <div class="hkdev-co-thankyou-content-grid">
                <div class="hkdev-co-thankyou-section card-summary">
                    <h3>Order Summary</h3>
                    <div class="summary-table-wrap">
                        <?php 
                        foreach ( $order->get_items() as $item_id => $item ) : 
                            $product   = $item->get_product(); 
                            $item_name = apply_filters( 'woocommerce_order_item_name', esc_html( $item->get_name() ), $item, false );
                        ?>
                            <div class="hkdev-co-summary-table-row">
                                <div class="prod-img"><?php echo $product ? wp_kses_post( $product->get_image( 'thumbnail' ) ) : ''; ?></div>
                                <div class="prod-info">
                                    <span class="name"><?php echo $item_name; ?></span>
                                    <span class="qty">Qty: <?php echo $item->get_quantity(); ?></span>
                                </div>
                                <div class="prod-total"><?php echo $order->get_formatted_line_subtotal( $item ); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="hkdev-co-summary-totals-footer">
                        <div class="foot-row"><span>Subtotal:</span><strong><?php echo $order->get_subtotal_to_display(); ?></strong></div>
                        
                        <?php foreach ( $order->get_items('coupon') as $coupon_item ) : $coupon_code = $coupon_item->get_name(); ?>
                            <div class="foot-row" style="color: #e11d48;">
                                <span><i class="fa-solid fa-tag"></i> Coupon: <?php echo esc_html($coupon_code); ?></span>
                                <strong>-<?php echo wc_price( $coupon_item->get_discount() ); ?></strong>
                            </div>
                        <?php endforeach; ?>

                        <?php foreach ( $order->get_fees() as $fee_id => $fee ) : ?>
                            <div class="foot-row" style="color: #2e7d32;">
                                <span><i class="fa-solid fa-gift"></i> <?php echo esc_html( $fee->get_name() ); ?></span>
                                <strong><?php echo wc_price( $fee->get_total() ); ?></strong>
                            </div>
                        <?php endforeach; ?>

                        <div class="foot-row"><span>Shipping:</span><strong><?php echo $order->get_shipping_to_display(); ?></strong></div>
                        <div class="foot-row grand-total"><span>Grand Total:</span><strong><?php echo $order->get_formatted_order_total(); ?></strong></div>
                    </div>
                </div>

                <div class="hkdev-co-thankyou-section customer-sidebar">
                    <h3>Delivery Details</h3>
                    <div class="hkdev-co-address-box">
                        <p><strong>Name: <?php echo esc_html($order->get_billing_first_name()); ?></strong></p>
                        <p><i class="fa-solid fa-phone-volume"></i> <?php echo esc_html($order->get_billing_phone()); ?></p>
                        <p><i class="fa-solid fa-location-dot"></i> <?php echo esc_html($order->get_billing_address_1()); ?></p>
                    </div>
                    <div class="hkdev-co-qr-block">
                        <img src="<?php echo esc_url_raw($qr_code_url); ?>" alt="Order QR Code">
                        <p>Scan QR for details</p>
                    </div>
                    <a href="<?php echo get_permalink( wc_get_page_id( 'shop' ) ); ?>" class="hkdev-co-shop-more-btn">Continue Shopping</a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    // --- B. EMPTY CART LOGIC ---
    if ( WC()->cart->is_empty() ) {
        ?>
        <div class="hkdev-co-empty-cart-wrap">
            <div class="empty-icon-circle"><i class="fa-solid fa-cart-arrow-down"></i></div>
            <h3>Your Cart is Empty</h3>
            <a href="<?php echo get_permalink( wc_get_page_id( 'shop' ) ); ?>" class="hkdev-co-confirm-btn" style="display: inline-block; width: auto; padding: 15px 40px;">Start Shopping</a>
        </div>
        <?php
        return ob_get_clean();
    }

    // --- C. INITIALIZE CHECKOUT TOTALS ---
    WC()->cart->calculate_totals();
    WC()->cart->calculate_shipping();
    
    $rates = WC()->shipping()->get_packages()[0]['rates'] ?? [];
    $chosen_shipping = WC()->session->get('chosen_shipping_methods')[0] ?? '';
    ?>
    
    <!-- --- D. MAIN CHECKOUT UI --- -->
    <div class="hkdev-co-container" id="hkdev-co-root">
        <?php do_action( 'woocommerce_before_checkout_form', WC()->checkout() ); ?>

        <form id="hkdev-co-process-order" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" method="post">
            <div class="hkdev-co-checkout-grid">
                
                <div class="checkout-section left-column">
                    <div class="hkdev-co-section-card">
                        <div class="hkdev-co-card-header">
                            <span class="hkdev-co-step-icon"><i class="fa-solid fa-basket-shopping"></i></span>
                            <h3>Order Summary</h3>
                        </div>
                        
                        <div id="hkdev-co-items-ajax">
                            <?php echo hkdev_co_get_items_html(); ?>
                        </div>

                        <div class="hkdev-co-delivery-selection-wrap">
                            <div class="hkdev-co-card-header mini-header">
                                <span class="hkdev-co-step-icon mini"><i class="fa-solid fa-truck-fast"></i></span>
                                <h4>Delivery Area</h4>
                            </div>
                            <div class="radio-stack-custom">
                                <?php foreach ( $rates as $rate_id => $rate ) : $active = ($chosen_shipping === $rate_id) ? 'active' : ''; ?>
                                    <label class="hkdev-co-radio-row <?php echo $active; ?>">
                                        <input type="radio" name="shipping_method[0]" value="<?php echo esc_attr($rate_id); ?>" <?php checked($chosen_shipping, $rate_id); ?>>
                                        <div class="hkdev-co-radio-box-ui">
                                            <div class="hkdev-co-radio-indicator"><i class="fa-solid fa-check"></i></div>
                                            <span class="label-text"><?php echo esc_html($rate->label); ?></span>
                                            <span class="price-text"><?php echo wc_price($rate->cost); ?></span>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="checkout-section right-column">
                    <div class="hkdev-co-section-card hkdev-co-sticky-sidebar">
                        <div class="hkdev-co-card-header">
                            <span class="hkdev-co-step-icon"><i class="fa-solid fa-map-location-dot"></i></span>
                            <h3>Delivery Details & Payment</h3>
                        </div>
                        
                        <div class="form-body-wrap hkdev-co-form-area">
                            <?php 
                            $checkout = WC()->checkout();
                            $billing_fields = $checkout->get_checkout_fields( 'billing' );
                            foreach ( $billing_fields as $key => $field ) {
                                woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
                            }
                            ?>
                            <input type="hidden" name="billing_email" value="<?php
                            // Derive a stable, non-predictable placeholder email from the
                            // WooCommerce session ID so the same value is used throughout
                            // the current checkout session.
                            $guest_email = 'guest_' . substr( md5( WC()->session->get_customer_id() ), 0, 8 ) . '@order.local';
                            echo esc_attr( $guest_email );
                        ?>">
                        </div>

                        <div id="hkdev-co-totals-ajax">
                            <?php echo hkdev_co_get_totals_html(); ?>
                        </div>

                        <button type="submit" id="hkdev-co-submit-btn" class="hkdev-co-confirm-btn">
                            <i class="fa-solid fa-lock" style="margin-right: 8px;"></i> Confirm Order
                        </button>
                    </div>
                </div>
            </div>
            <input type="hidden" name="action" value="hkdev_co_place_order_action">
            <?php wp_nonce_field('hkdev_co_place_order_nonce', 'hkdev_co_nonce'); ?>
        </form>
        
        <?php do_action( 'woocommerce_after_checkout_form', WC()->checkout() ); ?>
        
        <div id="hkdev-co-toast" class="hkdev-co-toast"><div class="toast-icon"><i class="fa-solid fa-circle-check"></i></div><div class="toast-msg">Message</div></div>
        <div id="hkdev-co-global-loader"><div class="loader-box-inner"><i class="fa-solid fa-circle-notch fa-spin"></i> Processing...</div></div>
    </div>
    <?php
    return ob_get_clean();
}


/* =========================================================================
 * 5. HTML GENERATION HELPERS
 * ========================================================================= */
function hkdev_co_get_items_html() {
    ob_start();
    ?>
    <div class="hkdev-co-summary-items-list">
        <?php 
        foreach ( WC()->cart->get_cart() as $key => $item ) : 
            $_prod = $item['data']; 
            $item_name = apply_filters( 'woocommerce_cart_item_name', $_prod->get_name(), $item, $key );
            $line_total = apply_filters( 'woocommerce_cart_item_subtotal', wc_price($item['line_total']), $item, $key );
        ?>
            <div class="hkdev-co-summary-item" data-key="<?php echo esc_attr( $key ); ?>" data-free-count="<?php echo intval($item['hkdev_free_count'] ?? 0); ?>">
                <div class="item-img-box"><?php echo wp_kses_post( $_prod->get_image() ); ?></div>
                <div class="item-meta">
                    <h4 class="item-name"><?php echo $item_name; ?></h4>
                    <div class="item-price-bottom"><span class="item-price-val"><?php echo $line_total; ?></span></div>
                    
                    <!-- Quantity Stepper and Delete Button (RESTORED) -->
                    <div class="hkdev-co-item-qty-row">
                        <div class="hkdev-co-qty-stepper-ui">
                            <button type="button" class="hkdev-co-qty-mod" data-act="minus">−</button>
                            <span class="hkdev-co-qty-val"><?php echo $item['quantity']; ?></span>
                            <button type="button" class="hkdev-co-qty-mod" data-act="plus">+</button>
                        </div>
                        <button type="button" class="hkdev-co-item-remove-trigger" title="Remove"><i class="fa-regular fa-trash-can"></i></button>
                    </div>

                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php
    $hkdev_total_qty  = WC()->cart->get_cart_contents_count();
    $hkdev_total_free = function_exists('hkdev_get_total_free_items_in_cart') ? hkdev_get_total_free_items_in_cart() : 0;
    $hkdev_paid_qty   = max(0, $hkdev_total_qty - $hkdev_total_free);
    ?>
    <div class="hkdev-co-items-count-summary">
        <span class="paid-count">Paid Items: <strong><?php echo esc_html( $hkdev_paid_qty ); ?></strong></span>
        <span class="separator"> | </span>
        <span class="free-count">Free items: <strong><?php echo esc_html( $hkdev_total_free ); ?></strong></span>
    </div>

    <div class="hkdev-co-coupon-wrap">
        <div class="hkdev-co-coupon-box">
            <i class="fa-solid fa-ticket"></i>
            <input type="text" id="hkdev-co-coupon-code" name="coupon_code" placeholder="Enter coupon code">
            <button type="button" id="hkdev-co-apply-coupon">Apply</button>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function hkdev_co_get_totals_html() {
    ob_start();
    ?>
    <div class="hkdev-co-calculation-wrap">
        <div class="hkdev-co-calc-line"><span>Subtotal</span><strong><?php echo WC()->cart->get_cart_subtotal(); ?></strong></div>
        
        <?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
            <div class="hkdev-co-calc-line coupon-line">
                <span class="coupon-title"><i class="fa-solid fa-tag"></i> Coupon: <?php echo esc_html($code); ?></span>
                <span class="coupon-val">
                    <strong>-<?php echo wc_price( WC()->cart->get_coupon_discount_amount( $code ) ); ?></strong>
                    <a href="#" class="hkdev-co-remove-coupon" data-coupon="<?php echo esc_attr( $code ); ?>"><i class="fa-solid fa-xmark"></i></a>
                </span>
            </div>
        <?php endforeach; ?>

        <?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
            <div class="hkdev-co-calc-line fee-line" style="color: #2e7d32;">
                <span><i class="fa-solid fa-gift"></i> <?php echo esc_html( $fee->name ); ?></span>
                <strong><?php echo wc_price( $fee->total ); ?></strong>
            </div>
        <?php endforeach; ?>

        <div class="hkdev-co-calc-line"><span>Shipping</span><strong><?php echo wc_price(WC()->cart->get_shipping_total()); ?></strong></div>
        
        <div class="hkdev-co-payment-method-selector-wrap">
            <h4 class="hkdev-co-mini-title"><i class="fa-regular fa-credit-card"></i> PAYMENT METHOD</h4>
            <div class="hkdev-co-payment-pill-list">
                <?php $gateways = WC()->payment_gateways->get_available_payment_gateways(); $chosen = $_POST['payment_method'] ?? array_keys($gateways)[0];
                foreach($gateways as $id => $gateway): ?>
                    <label class="hkdev-co-pay-pill-box <?php echo $id == $chosen ? 'active' : ''; ?>">
                        <input type="radio" name="payment_method" value="<?php echo $id; ?>" <?php checked($id, $chosen); ?>>
                        <div class="pay-radio-icon"><i class="fa-solid fa-circle-check"></i></div>
                        <span><?php echo $gateway->get_title(); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="hkdev-co-calc-line grand-total-line">
            <span>GRAND TOTAL</span>
            <strong class="total-amt"><?php echo WC()->cart->get_total(); ?></strong>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/* =========================================================================
 * 6. AJAX HANDLERS 
 * ========================================================================= */
add_action('wp_ajax_hkdev_co_checkout_update_cart', 'hkdev_co_ajax_update_cart_handler');
add_action('wp_ajax_nopriv_hkdev_co_checkout_update_cart', 'hkdev_co_ajax_update_cart_handler');
function hkdev_co_ajax_update_cart_handler() {
    check_ajax_referer( 'hkdev_co_nonce', 'security' );

    if ( isset( $_POST['type'] ) ) {
        $type = sanitize_text_field( $_POST['type'] );
        if ( $type === 'qty' && isset( $_POST['key'], $_POST['qty'] ) ) {
            WC()->cart->set_quantity( sanitize_key( $_POST['key'] ), absint( $_POST['qty'] ), true );
        } elseif ( $type === 'remove' && isset( $_POST['key'] ) ) {
            WC()->cart->remove_cart_item( sanitize_key( $_POST['key'] ) );
        }
    }
    if ( isset( $_POST['shipping_method'] ) && is_array( $_POST['shipping_method'] ) ) {
        $sanitized = array_map( 'sanitize_text_field', $_POST['shipping_method'] );
        WC()->session->set( 'chosen_shipping_methods', $sanitized );
    }

    WC()->cart->calculate_totals();
    if ( WC()->cart->is_empty() ) wp_send_json_success( [ 'cart_empty' => true ] );

    wp_send_json_success( [
        'items_html'         => hkdev_co_get_items_html(),
        'totals_html'        => hkdev_co_get_totals_html(),
        'cart_count'         => WC()->cart->get_cart_contents_count(),
        'minicart_body_html' => '<div class="hkdev-minicart-body">' . hkdev_mc_get_cart_html() . '</div>',
    ] );
}

add_action('wp_ajax_hkdev_co_apply_coupon', 'hkdev_co_apply_coupon_handler');
add_action('wp_ajax_nopriv_hkdev_co_apply_coupon', 'hkdev_co_apply_coupon_handler');
function hkdev_co_apply_coupon_handler() {
    check_ajax_referer( 'hkdev_co_nonce', 'security' );

    $coupon_code = isset( $_POST['coupon_code'] ) ? sanitize_text_field( $_POST['coupon_code'] ) : '';
    if ( empty( $coupon_code ) ) wp_send_json_error( [ 'message' => 'Enter Coupon' ] );

    $applied = WC()->cart->add_discount($coupon_code);
    WC()->cart->calculate_totals();

    if ($applied) {
        wc_clear_notices();
        wp_send_json_success([
            'message'            => 'Coupon Applied!',
            'items_html'         => hkdev_co_get_items_html(),
            'totals_html'        => hkdev_co_get_totals_html(),
            'cart_count'         => WC()->cart->get_cart_contents_count(),
            'minicart_body_html' => '<div class="hkdev-minicart-body">' . hkdev_mc_get_cart_html() . '</div>',
        ]);
    } else {
        $errors = wc_get_notices('error');
        $error_msg = !empty($errors) ? strip_tags($errors[0]['notice']) : 'Invalid Coupon';
        wc_clear_notices();
        wp_send_json_error(['message' => $error_msg]);
    }
}

add_action('wp_ajax_hkdev_co_remove_coupon', 'hkdev_co_remove_coupon_handler');
add_action('wp_ajax_nopriv_hkdev_co_remove_coupon', 'hkdev_co_remove_coupon_handler');
function hkdev_co_remove_coupon_handler() {
    check_ajax_referer( 'hkdev_co_nonce', 'security' );

    $coupon_code = isset( $_POST['coupon_code'] ) ? sanitize_text_field( $_POST['coupon_code'] ) : '';
    if ( ! empty( $coupon_code ) ) WC()->cart->remove_coupon( $coupon_code ); 
    WC()->cart->calculate_totals();
    wc_clear_notices();
    wp_send_json_success([
        'message'            => 'Coupon Removed',
        'items_html'         => hkdev_co_get_items_html(),
        'totals_html'        => hkdev_co_get_totals_html(),
        'cart_count'         => WC()->cart->get_cart_contents_count(),
        'minicart_body_html' => '<div class="hkdev-minicart-body">' . hkdev_mc_get_cart_html() . '</div>',
    ]);
}

/* =========================================================================
 * 7. ORDER PLACEMENT HANDLER 
 * ========================================================================= */
add_action('wp_ajax_hkdev_co_place_order_action', 'hkdev_co_ajax_place_order_handler');
add_action('wp_ajax_nopriv_hkdev_co_place_order_action', 'hkdev_co_ajax_place_order_handler');
function hkdev_co_ajax_place_order_handler() {
    check_ajax_referer('hkdev_co_place_order_nonce', 'hkdev_co_nonce');

    if ( WC()->cart->is_empty() ) { wp_send_json_error(['message' => 'Cart is empty']); }

    $phone = isset( $_POST['billing_phone'] ) ? sanitize_text_field( $_POST['billing_phone'] ) : '';
    if ( empty( $phone ) || ! preg_match( '/^(?:\+?88)?01[3-9]\d{8}$/', $phone ) ) {
        wp_send_json_error(['message' => 'Invalid Phone Number']);
    }

    try {
        WC()->cart->calculate_totals();
        $order = wc_create_order();
        
        // 1. Add Products
        foreach ( WC()->cart->get_cart() as $cart_item ) {
            $order->add_product( $cart_item['data'], $cart_item['quantity'], [
                'variation' => $cart_item['variation'], 
                'totals' => array('subtotal' => $cart_item['line_subtotal'], 'total' => $cart_item['line_total'])
            ]);
        }

        // 2. EXPLICITLY set Address Fields
        $order->set_billing_first_name( sanitize_text_field($_POST['billing_first_name'] ?? '') );
        $order->set_billing_phone( $phone );
        $order->set_billing_address_1( sanitize_textarea_field($_POST['billing_address_1'] ?? '') );
        $order->set_billing_email( sanitize_email($_POST['billing_email'] ?? 'guest@website.com') );
        $order->set_billing_country( 'BD' );
        $order->set_billing_city( 'Dhaka' );

        $order->set_shipping_first_name( sanitize_text_field($_POST['billing_first_name'] ?? '') );
        $order->set_shipping_phone( $phone );
        $order->set_shipping_address_1( sanitize_textarea_field($_POST['billing_address_1'] ?? '') );
        $order->set_shipping_country( 'BD' );
        $order->set_shipping_city( 'Dhaka' );

        // 3. Add Shipping
        $shipping_methods = $_POST['shipping_method'] ?? [];
        if ( isset( $shipping_methods[0] ) ) {
            $chosen_rate_id = sanitize_text_field($shipping_methods[0]);
            WC()->session->set('chosen_shipping_methods', array($chosen_rate_id));
            WC()->cart->calculate_shipping();
            $packages = WC()->shipping()->get_packages();
            $rates = $packages[0]['rates'] ?? [];
            
            if ( isset( $rates[$chosen_rate_id] ) ) {
                $rate = $rates[$chosen_rate_id];
                $item = new WC_Order_Item_Shipping();
                $item->set_method_id( $rate->get_method_id() );
                $item->set_method_title( $rate->label );
                $item->set_total( $rate->cost );
                $order->add_item( $item );
            }
        }

        // 4. Add Coupons & Fees
        foreach ( WC()->cart->get_coupons() as $code => $coupon ) {
            $item = new WC_Order_Item_Coupon();
            $item->set_props(array('code' => $code, 'discount' => $coupon->get_amount(), 'discount_tax' => 0));
            $order->add_item( $item );
        }

        foreach ( WC()->cart->get_fees() as $fee_key => $fee ) {
            $item = new WC_Order_Item_Fee();
            $item->set_props(array('name' => $fee->name, 'tax_class' => $fee->taxable ? $fee->tax_class : 0, 'total' => $fee->amount, 'total_tax' => $fee->tax));
            $order->add_item( $item );
        }

        $order->set_payment_method( sanitize_text_field($_POST['payment_method']) );
        
        $order->save();
        $order->calculate_totals();
        $order->update_status( 'processing', 'Order placed from custom checkout.' );
        
        do_action( 'woocommerce_checkout_update_order_meta', $order->get_id(), $_POST );
        do_action( 'woocommerce_checkout_order_processed', $order->get_id(), $_POST, $order );
        
        WC()->cart->empty_cart();

        wp_send_json_success( array('redirect' => $order->get_checkout_order_received_url()) );
    } catch ( Exception $e ) {
        wp_send_json_error( array( 'message' => $e->getMessage() ) );
    }
}