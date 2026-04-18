<?php
/**
 * HKDEV Professional Sticky Navigation Menu & Header - Premium Design
 * Optimized for Dynamic CSS/JS Updates
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Note: CSS/JS assets for this module are auto-loaded by functions.php
// from /assets/css/nav-menu.css and /assets/js/nav-menu.js respectively.
// The global hkdev_ajax_obj (including mc_nonce) is also output there.

// Register Custom Menu
add_action( 'after_setup_theme', 'hkdev_register_custom_menu' );
function hkdev_register_custom_menu() {
    register_nav_menus( array( 'hkdev_primary' => esc_html__( 'HKDEV Primary Header Menu', 'hkdev' ) ) );
}

// 2. PRO AJAX MINI CART HTML
function hkdev_mc_get_cart_html() {
    if ( ! class_exists( 'WooCommerce' ) || ! WC()->cart ) return '';
    ob_start();
    
    do_action( 'woocommerce_before_mini_cart' );
    ?>
    <div class="hkdev-mc-cart-list">
        <?php
        if ( WC()->cart->is_empty() ) {
            $empty_msg = function_exists('hkdev_t') ? hkdev_t('cart_empty') : esc_html__( 'Your cart is empty', 'hkdev' );
            echo '<div class="hkdev-mc-empty-msg"><i class="fa-solid fa-cart-arrow-down"></i><p>' . $empty_msg . '</p></div>';
        } else {
            foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
                $_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
                if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 ) {
                    $product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
                    $thumbnail = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key );
                    
                    // GET SINGLE PRICE & SUBTOTAL
                    $product_price = apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key );
                    $product_subtotal = apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key );
                    
                    $item_name = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key );
                    ?>
                    <div class="hkdev-mc-item" data-key="<?php echo esc_attr($cart_item_key); ?>">
                        <div class="hkdev-mc-img">
                            <a href="<?php echo esc_url( $product_permalink ); ?>" aria-hidden="true" tabindex="-1">
                                <?php echo wp_kses_post( $thumbnail ); ?>
                            </a>
                        </div>
                        <div class="hkdev-mc-details">
                            <a href="<?php echo esc_url( $product_permalink ); ?>" class="hkdev-mc-name">
                                <?php echo wp_kses_post( $item_name ); // BOGO HTML allowed here ?>
                            </a>
                            
                            <!-- SEPARATED PRICE AND SUBTOTAL -->
                            <div class="hkdev-mc-price-wrap">
                                <div class="hkdev-mc-unit-price">
                                    <span><?php echo function_exists('hkdev_t') ? hkdev_t('price') : 'Price:'; ?></span> <?php echo $product_price; ?>
                                </div>
                                <div class="hkdev-mc-price-line">
                                    <span><?php echo function_exists('hkdev_t') ? hkdev_t('subtotal') : 'Subtotal:'; ?></span> <?php echo $product_subtotal; ?>
                                </div>
                            </div>
                            
                            <div class="hkdev-pro-qty">
                                <button type="button" class="hkdev-qty-btn minus" data-key="<?php echo esc_attr($cart_item_key); ?>" aria-label="<?php esc_attr_e('Decrease quantity', 'hkdev'); ?>">
                                    <i class="fa-solid fa-minus"></i>
                                </button>
                                <input type="number" class="hkdev-qty-input" value="<?php echo esc_attr($cart_item['quantity']); ?>" min="1" readonly aria-label="<?php esc_attr_e('Product quantity', 'hkdev'); ?>">
                                <button type="button" class="hkdev-qty-btn plus" data-key="<?php echo esc_attr($cart_item_key); ?>" aria-label="<?php esc_attr_e('Increase quantity', 'hkdev'); ?>">
                                    <i class="fa-solid fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="hkdev-mc-remove">
                            <a href="<?php echo esc_url( wc_get_cart_remove_url( $cart_item_key ) ); ?>" class="hkdev-mc-remove-btn remove_from_cart_button" data-cart_item_key="<?php echo esc_attr( $cart_item_key ); ?>" title="<?php esc_attr_e('Remove item', 'hkdev'); ?>">
                                <i class="fa-regular fa-trash-can"></i>
                            </a>
                        </div>
                    </div>
                    <?php
                }
            }
        }
        ?>
    </div>
    
    <?php if ( ! WC()->cart->is_empty() ) : ?>
        <div class="hkdev-mc-footer">
            <div class="hkdev-mc-subtotal">
                <span><?php echo function_exists('hkdev_t') ? hkdev_t('total_text') : esc_html__( 'Total', 'hkdev' ); ?></span> 
                <span class="hkdev-mc-total-price"><?php echo WC()->cart->get_cart_subtotal(); ?></span>
            </div>
            
            <?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
                <div class="hkdev-mc-subtotal fee-line">
                    <span><i class="fa-solid fa-gift"></i> <?php echo esc_html( $fee->name ); ?></span> 
                    <span><?php echo wc_price( $fee->total ); ?></span>
                </div>
            <?php endforeach; ?>

            <div class="hkdev-mc-buttons">
                <a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="hkdev-mc-btn hkdev-mc-btn-view"><?php esc_html_e( 'View Cart', 'hkdev' ); ?></a>
                <a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="hkdev-mc-btn hkdev-mc-btn-checkout"><?php esc_html_e( 'Checkout', 'hkdev' ); ?></a>
            </div>
        </div>
    <?php endif; ?>
    <?php
    return ob_get_clean();
}

// 3. WOOCOMMERCE AJAX FRAGMENTS
add_filter( 'woocommerce_add_to_cart_fragments', 'hkdev_custom_cart_fragments', 99 );
function hkdev_custom_cart_fragments( $fragments ) {
    if ( ! class_exists( 'WooCommerce' ) ) return $fragments;
    $fragments['.hkdev-nav-cart-count'] = '<span class="hkdev-nav-cart-count">' . esc_html(WC()->cart->get_cart_contents_count()) . '</span>';
    $fragments['div.hkdev-minicart-body'] = '<div class="hkdev-minicart-body">' . hkdev_mc_get_cart_html() . '</div>';
    return $fragments;
}

// Qty Update Handler
add_action('wp_ajax_hkdev_mc_update_qty', 'hkdev_mc_update_qty_handler');
add_action('wp_ajax_nopriv_hkdev_mc_update_qty', 'hkdev_mc_update_qty_handler');
function hkdev_mc_update_qty_handler() {
    check_ajax_referer( 'hkdev_mc_nonce', 'security' );

    if ( isset( $_POST['cart_item_key'] ) && isset( $_POST['qty'] ) ) {
        $cart_item_key = sanitize_key( $_POST['cart_item_key'] );
        $quantity      = absint( $_POST['qty'] );

        WC()->cart->set_quantity( $cart_item_key, $quantity );
        WC_AJAX::get_refreshed_fragments();
    }
    wp_die();
}

// 4. AJAX SEARCH
add_action('wp_ajax_hkdev_search_action', 'hkdev_search_callback');
add_action('wp_ajax_nopriv_hkdev_search_action', 'hkdev_search_callback');
function hkdev_search_callback() {
    $keyword = isset($_GET['keyword']) ? sanitize_text_field($_GET['keyword']) : '';
    $query = new WP_Query(array(
        'post_type' => 'product', 
        'post_status' => 'publish', 
        'posts_per_page' => 8, 
        's' => $keyword
    ));

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $product = wc_get_product(get_the_ID());
            
            $link  = get_permalink();
            $title = get_the_title();
            $price = $product->get_price_html();
            $img   = get_the_post_thumbnail_url(get_the_ID(), 'thumbnail');
            
            echo '<a href="' . esc_url($link) . '" class="hkdev-search-item-result">';
            if ($img) {
                echo '<img src="' . esc_url($img) . '" class="hkdev-search-img" alt="' . esc_attr($title) . '">';
            }
            echo '<div class="hkdev-search-info">';
            echo '<span class="hkdev-search-title">' . esc_html($title) . '</span>';
            echo '<span class="hkdev-search-price">' . $price . '</span>';
            echo '</div></a>';
        }
        wp_reset_postdata();
    } else {
        echo '<div style="padding:20px; text-align:center; color:#999;">' . esc_html__( 'No products found.', 'hkdev' ) . '</div>';
    }
    wp_die();
}

// 5. SHORTCODE
add_shortcode( 'hkdev_nav_menu', 'hkdev_nav_menu_shortcode' );
function hkdev_nav_menu_shortcode() {
    ob_start();
    $logo_url  = wp_get_attachment_image_url( get_theme_mod( 'custom_logo' ), 'full' );
    $site_name = get_bloginfo( 'name' );
    if ( $logo_url ) {
        $logo_html = '<img src="' . esc_url( $logo_url ) . '" alt="' . esc_attr( $site_name ) . '">';
    } else {
        $logo_html = '<h2>' . esc_html( $site_name ) . '</h2>';
    }
    $cart_count = class_exists( 'WooCommerce' ) && WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
    ?><header class="hkdev-main-header" id="hkdev-header">
        <div class="hkdev-header-container">
            <div class="hkdev-header-left">
                <button type="button" class="hkdev-mobile-toggle" id="hkdev-mobile-toggle" aria-label="<?php esc_attr_e( 'Toggle Menu', 'hkdev' ); ?>">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="hkdev-nav-logo">
                    <a href="<?php echo esc_url( home_url('/') ); ?>" aria-label="<?php echo esc_attr(get_bloginfo('name')); ?>">
                        <?php echo $logo_html; ?>
                    </a>
                </div>
            </div>
            
            <nav class="hkdev-desktop-nav" aria-label="<?php esc_attr_e( 'Primary Navigation', 'hkdev' ); ?>">
                <?php wp_nav_menu( array('theme_location' => 'hkdev_primary', 'container' => false, 'menu_class' => 'hkdev-menu-ul', 'fallback_cb' => false) ); ?>
            </nav>
            
            <div class="hkdev-nav-actions">
                <a href="#" class="hkdev-action-icon hkdev-search-trigger" id="hkdev-search-trigger" title="<?php esc_attr_e( 'Search', 'hkdev' ); ?>">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </a>
                
                <div class="hkdev-account-wrap">
                    <a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="hkdev-action-icon" title="<?php esc_attr_e( 'Account', 'hkdev' ); ?>">
                        <i class="fa-regular fa-user"></i>
                    </a>
                </div>
                
                <a href="#" class="hkdev-action-icon hkdev-mini-cart-trigger" id="hkdev-mini-cart-trigger" title="<?php esc_attr_e( 'Cart', 'hkdev' ); ?>">
                    <i class="fa-solid fa-cart-shopping"></i> 
                    <span class="hkdev-nav-cart-count"><?php echo esc_html($cart_count); ?></span>
                </a>
            </div>
        </div>
    </header>

    <div class="hkdev-mobile-sidebar" id="hkdev-mobile-sidebar">
        <div class="hkdev-mobile-sidebar-header">
            <div class="hkdev-sidebar-logo">
                <a href="<?php echo esc_url( home_url('/') ); ?>" aria-label="<?php echo esc_attr(get_bloginfo('name')); ?>">
                    <?php echo $logo_html; ?>
                </a>
            </div>
            <button id="hkdev-mobile-close" aria-label="<?php esc_attr_e( 'Close Menu', 'hkdev' ); ?>">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="hkdev-mobile-nav-content">
            <?php wp_nav_menu( array('theme_location' => 'hkdev_primary', 'container' => false, 'menu_class' => 'hkdev-mobile-menu-ul', 'fallback_cb' => false) ); ?>
        </div>
    </div>

    <div class="hkdev-minicart-sidebar" id="hkdev-minicart-sidebar">
        <div class="hkdev-minicart-header">
            <h3><?php esc_html_e( 'YOUR CART', 'hkdev' ); ?></h3>
            <button id="hkdev-minicart-close" aria-label="<?php esc_attr_e( 'Close Cart', 'hkdev' ); ?>">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="hkdev-minicart-body"><?php echo hkdev_mc_get_cart_html(); ?></div>
    </div>
    
    <div class="hkdev-common-overlay" id="hkdev-common-overlay"></div>
    
    <div class="hkdev-search-overlay" id="hkdev-search-overlay">
        <button id="hkdev-search-close" aria-label="<?php esc_attr_e( 'Close Search', 'hkdev' ); ?>">
            <i class="fa-solid fa-xmark"></i>
        </button>
        <div class="hkdev-search-inner-wrap">
            <div class="hkdev-search-form">
                <input type="search" id="hkdev-ajax-search-input" placeholder="<?php esc_attr_e( 'Search products...', 'hkdev' ); ?>" autocomplete="off" aria-label="<?php esc_attr_e( 'Search products', 'hkdev' ); ?>">
            </div>
            <div id="hkdev-ajax-search-output" class="hkdev-results-dropdown"></div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}