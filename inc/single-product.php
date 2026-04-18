<?php
/**
 * Custom WooCommerce Single Product Page Shortcode
 * 100% NATIVE WOOCOMMERCE HOOK SUPPORTED (Safe integration without duplicates)
 * Features: Dynamic Sale Badge, Variable Product Support, AJAX Add to Cart, Size Chart Modal
 */

if (!defined('ABSPATH')) {
    exit;
}

function hkdev_custom_single_product_shortcode($atts) {
    if ( is_admin() ) return;

    $atts = shortcode_atts(array(
        'id' => '',
    ), $atts, 'hkdev_single_product');

    global $product, $post;

    if (!empty($atts['id'])) {
        $product_id = $atts['id'];
        $product = wc_get_product($product_id);
    } elseif (is_product() && is_a($product, 'WC_Product')) {
        $product_id = $product->get_id();
    } else {
        $product_id = get_the_ID();
        $product = wc_get_product($product_id);
    }

    if (!$product || !is_a($product, 'WC_Product')) {
        return '<div style="text-align:center; padding: 60px; color: #d32f2f; font-family: \'Hind Siliguri\', sans-serif; background: #fff; border-radius: 12px; border: 1px solid #eee;">' . (function_exists('hkdev_t') ? hkdev_t('product_not_found') : 'Product not found.') . '</div>';
    }
    
    // Set global post to ensure all hooks work perfectly
    $post = get_post($product_id);
    setup_postdata($post);

    // ==============================================================================
    // DUPLICATE PREVENTION: Temporarily remove WC default elements from hooks
    // This allows 3rd-party plugins to inject items without breaking our custom HTML
    // ==============================================================================
    $wc_single_defaults = [
        ['woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10],
        ['woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20],
        ['woocommerce_single_product_summary', 'woocommerce_template_single_title', 5],
        ['woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10],
        ['woocommerce_single_product_summary', 'woocommerce_template_single_price', 10],
        ['woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20],
        ['woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30],
        ['woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40],
        ['woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50],
        ['woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10],
        ['woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15],
        ['woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20],
    ];
    foreach ($wc_single_defaults as $hook) { remove_action($hook[0], $hook[1], $hook[2]); }
    // ==============================================================================

    // Get Size Chart URL from meta
    $size_chart_url = get_post_meta( $product_id, '_hkdev_size_chart_url', true );

    $percentage = 0;
    if ( $product->is_on_sale() && $product->get_type() != 'variable' ) {
        $regular_price = (float) $product->get_regular_price();
        $sale_price    = (float) $product->get_sale_price();
        if ( $regular_price > 0 ) {
            $percentage = round( ( ( $regular_price - $sale_price ) / $regular_price ) * 100 );
        }
    }

    $attachment_ids = $product->get_gallery_image_ids();
    $main_image_id  = $product->get_image_id();
    
    $is_in_cart = false;
    if (WC()->cart) {
        foreach( WC()->cart->get_cart() as $cart_item ){
            if($cart_item['product_id'] == $product_id){
                $is_in_cart = true;
                break;
            }
        }
    }

    $checkout_url = wc_get_checkout_url();

    ob_start();
    ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <div id="product-<?php echo esc_attr($product_id); ?>" <?php wc_product_class('hkdev-sp-wrapper', $product); ?>>
        
        <?php 
        // WOOCOMMERCE HOOK: Top of Single Product
        do_action( 'woocommerce_before_single_product' ); 
        ?>

        <!-- Toast container removed from here -->

        <div class="hkdev-sp-main-container">
            
            <!-- Gallery Section -->
            <div class="hkdev-sp-gallery">
                
                <?php 
                // WOOCOMMERCE HOOK: Before Image Gallery
                do_action( 'woocommerce_before_single_product_summary' ); 
                ?>

                <div class="hkdev-sp-viewport" id="hkdev-sp-viewport">
                    <?php 
                    $off_text = function_exists('hkdev_t') ? hkdev_t('off_text') : 'Off!';
                    $display_percentage = ($percentage > 0) ? $percentage . '% ' . $off_text : $off_text;
                    $badge_style = ($product->is_on_sale()) ? 'display:block;' : 'display:none;';
                    ?>
                    <span class="hkdev-sp-sale-badge" style="<?php echo $badge_style; ?>"><?php echo $display_percentage; ?></span>
                    
                    <button type="button" class="hkdev-sp-zoom-trigger" id="hkdev-sp-zoom-btn" title="<?php echo esc_attr(function_exists('hkdev_t') ? hkdev_t('click_to_zoom') : 'Zoom'); ?>">
                        <i class="fa-solid fa-magnifying-glass-plus"></i>
                    </button>

                    <button type="button" class="hkdev-sp-arrow prev-arrow" id="hkdev-sp-prev-img"><i class="fa-solid fa-chevron-left"></i></button>
                    <button type="button" class="hkdev-sp-arrow next-arrow" id="hkdev-sp-next-img"><i class="fa-solid fa-chevron-right"></i></button>

                    <div class="hkdev-sp-zoom-inner" id="hkdev-sp-zoom-container">
                        <img id="hkdev-sp-main-img" src="<?php echo wp_get_attachment_image_url($main_image_id, 'large'); ?>" alt="<?php echo esc_attr($product->get_name()); ?>">
                    </div>
                </div>

                <div class="hkdev-sp-thumbnails">
                    <?php if ( $main_image_id ) : ?>
                        <div class="hkdev-sp-thumb active" data-full="<?php echo wp_get_attachment_image_url($main_image_id, 'large'); ?>">
                            <?php echo wp_get_attachment_image($main_image_id, 'thumbnail'); ?>
                        </div>
                    <?php endif; ?>
                    <?php foreach ( $attachment_ids as $attachment_id ) : ?>
                        <div class="hkdev-sp-thumb" data-full="<?php echo wp_get_attachment_image_url($attachment_id, 'large'); ?>">
                            <?php echo wp_get_attachment_image($attachment_id, 'thumbnail'); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Details Section -->
            <div class="hkdev-sp-info-wrap">
                <nav class="hkdev-sp-breadcrumb">
                    <a href="<?php echo home_url(); ?>"><?php echo function_exists('hkdev_t') ? hkdev_t('home') : 'Home'; ?></a> <i class="fa-solid fa-angle-right"></i>
                    <a href="<?php echo get_permalink(wc_get_page_id('shop')); ?>"><?php echo function_exists('hkdev_t') ? hkdev_t('shop') : 'Shop'; ?></a> <i class="fa-solid fa-angle-right"></i>
                    <span class="current-crumb"><?php echo $product->get_name(); ?></span>
                </nav>

                <h1 class="hkdev-sp-title"><?php echo $product->get_name(); ?></h1>

                <div class="hkdev-sp-price-box">
                    <?php echo $product->get_price_html(); ?>
                </div>

                <?php 
                // WOOCOMMERCE HOOK: Main Product Info Area
                do_action( 'woocommerce_single_product_summary' ); 
                ?>

                <?php if ( $product->is_type( 'variable' ) ) : 
                    $variations = $product->get_available_variations();
                    foreach($variations as $key => $variation) {
                        $v_obj = wc_get_product($variation['variation_id']);
                        $v_reg = (float)$v_obj->get_regular_price();
                        $v_sale = (float)$v_obj->get_sale_price();
                        $v_perc = 0;
                        if($v_obj->is_on_sale() && $v_reg > 0) {
                            $v_perc = round((($v_reg - $v_sale) / $v_reg) * 100);
                        }
                        $variations[$key]['discount_percentage'] = $v_perc;
                    }
                    $attributes = $product->get_variation_attributes();
                ?>
                    <div class="hkdev-sp-variable-options" data-variations='<?php echo htmlspecialchars(wp_json_encode($variations), ENT_QUOTES, 'UTF-8'); ?>'>
                        <?php foreach ( $attributes as $attribute_name => $options ) : ?>
                            <div class="hkdev-sp-variation-row" data-attribute="attribute_<?php echo sanitize_title($attribute_name); ?>">
                                <span class="attr-label"><?php echo wc_attribute_label($attribute_name); ?>: <span class="selected-val"><?php echo function_exists('hkdev_t') ? hkdev_t('select') : 'Select'; ?></span></span>
                                <div class="attr-swatches">
                                    <?php foreach ( $options as $option ) : ?>
                                        <div class="hkdev-sp-swatch-item" data-value="<?php echo esc_attr($option); ?>">
                                            <?php echo esc_html($option); ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Size Chart Button -->
                <?php if(!empty($size_chart_url)): ?>
                <div style="margin-bottom: 20px;">
                    <button type="button" id="hkdev-size-chart-btn" style="background:none; border:none; color:var(--hkdev-brand-primary, #631f28); font-family:inherit; font-weight:600; cursor:pointer; text-decoration:underline; font-size:15px; padding:0; display:inline-flex; align-items:center; gap:6px;">
                        <i class="fa-solid fa-ruler-combined"></i> <?php echo function_exists('hkdev_t') ? hkdev_t('size_chart') : 'Size Chart'; ?>
                    </button>
                </div>
                <?php endif; ?>

                <!-- Quantity & Buttons -->
                <div class="hkdev-sp-action-row">
                    <div class="hkdev-sp-qty-control">
                        <button type="button" class="hkdev-sp-qty-btn minus">−</button>
                        <input type="number" id="hkdev-sp-qty-field" class="hkdev-sp-qty-input" value="1" min="1">
                        <button type="button" class="hkdev-sp-qty-btn plus">+</button>
                    </div>
                    
                    <div class="hkdev-sp-purchase-buttons">
                        <button type="button" class="hkdev-sp-btn atc-btn" id="hkdev-sp-add-to-cart" 
                                data-product-id="<?php echo $product_id; ?>" 
                                data-variation-id="0">
                            <i class="fa-solid fa-cart-plus"></i> <?php echo function_exists('hkdev_t') ? hkdev_t('add_to_cart') : 'Add to Cart'; ?>
                        </button>
                        
                        <button type="button" class="hkdev-sp-btn buy-now-btn <?php echo $is_in_cart ? 'checkout-active' : ''; ?>" 
                                id="hkdev-sp-buy-now" 
                                data-product-id="<?php echo $product_id; ?>"
                                data-variation-id="0"
                                data-checkout-url="<?php echo $checkout_url; ?>">
                            <i class="fa-solid fa-bolt"></i> 
                            <span class="btn-text"><?php echo $is_in_cart ? (function_exists('hkdev_t') ? hkdev_t('completed_order') : 'Order Completed') : (function_exists('hkdev_t') ? hkdev_t('buy_now') : 'Buy Now'); ?></span>
                        </button>
                    </div>
                </div>

                <div class="hkdev-sp-product-meta">
                    <div class="meta-row"><strong><?php echo function_exists('hkdev_t') ? hkdev_t('sku') : 'SKU'; ?>:</strong> <span class="sku-val"><?php echo $product->get_sku() ? $product->get_sku() : 'N/A'; ?></span></div>
                    <div class="meta-row"><strong><?php echo function_exists('hkdev_t') ? hkdev_t('stock_status') : 'Stock'; ?>:</strong> <span class="stock-val"><?php echo $product->is_in_stock() ? '<span class="in-stock-pill">'.(function_exists('hkdev_t') ? hkdev_t('in_stock') : 'In Stock').'</span>' : '<span class="out-stock-pill">'.(function_exists('hkdev_t') ? hkdev_t('out_of_stock') : 'Out of Stock').'</span>'; ?></span></div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="hkdev-sp-tabs-section">
            <div class="hkdev-sp-tab-headers">
                <button class="hkdev-sp-tab-link active" data-tab="desc"><?php echo function_exists('hkdev_t') ? hkdev_t('description') : 'Description'; ?></button>
                <button class="hkdev-sp-tab-link" data-tab="reviews"><?php echo function_exists('hkdev_t') ? hkdev_t('review') : 'Reviews'; ?> (<?php echo $product->get_review_count(); ?>)</button>
            </div>
            <div id="desc" class="hkdev-sp-tab-content active">
                <div class="entry-content"><?php echo apply_filters('the_content', get_post_field('post_content', $product_id)); ?></div>
            </div>
            <div id="reviews" class="hkdev-sp-tab-content"><?php comments_template(); ?></div>
        </div>

        <?php 
        // WOOCOMMERCE HOOK: After Tabs
        do_action( 'woocommerce_after_single_product_summary' ); 
        ?>

        <!-- Size Chart Modal HTML -->
        <?php if(!empty($size_chart_url)): ?>
        <div id="hkdev-size-chart-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:999999; justify-content:center; align-items:center; backdrop-filter:blur(3px);">
            <div style="background:#fff; border-radius:12px; max-width:600px; width:90%; position:relative; box-shadow:0 25px 50px rgba(0,0,0,0.15); animation: zoomIn 0.3s ease;">
                <div style="display:flex; justify-content:space-between; align-items:center; padding:15px 25px; border-bottom:1px solid #eee;">
                    <h3 style="margin:0; font-size:18px; font-family:'Hind Siliguri', sans-serif;"><?php echo function_exists('hkdev_t') ? hkdev_t('size_chart') : 'Size Chart'; ?></h3>
                    <button type="button" id="hkdev-size-chart-close" style="background:none; border:none; font-size:24px; cursor:pointer; color:#888;">&times;</button>
                </div>
                <div style="padding:20px; text-align:center; overflow-y:auto; max-height:70vh;">
                    <img src="<?php echo esc_url($size_chart_url); ?>" alt="Size Chart" style="max-width:100%; height:auto; border-radius:8px;">
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php 
        // WOOCOMMERCE HOOK: Bottom of Single Product
        do_action( 'woocommerce_after_single_product' ); 
        ?>

    </div>
    <style>@keyframes zoomIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }</style>
    <?php
    
    // ==============================================================================
    // RESTORE DEFAULTS
    // ==============================================================================
    foreach ($wc_single_defaults as $hook) { add_action($hook[0], $hook[1], $hook[2]); }
    wp_reset_postdata();

    return ob_get_clean();
}
add_shortcode('hkdev_single_product', 'hkdev_custom_single_product_shortcode');

add_action('wp_ajax_hkdev_ajax_add_to_cart', 'hkdev_ajax_add_to_cart_handler');
add_action('wp_ajax_nopriv_hkdev_ajax_add_to_cart', 'hkdev_ajax_add_to_cart_handler');

function hkdev_ajax_add_to_cart_handler() {
    $product_id = apply_filters('woocommerce_add_to_cart_product_id', absint($_POST['product_id']));
    $quantity = empty($_POST['quantity']) ? 1 : wc_stock_amount($_POST['quantity']);
    $variation_id = absint($_POST['variation_id']);
    
    $passed_validation = apply_filters('woocommerce_add_to_cart_validation', true, $product_id, $quantity, $variation_id);
    
    if ($passed_validation && WC()->cart->add_to_cart($product_id, $quantity, $variation_id)) {
        ob_start();
        woocommerce_mini_cart();
        $mini_cart = ob_get_clean();
        $fragments = apply_filters('woocommerce_add_to_cart_fragments', array(
            'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>'
        ));
        wp_send_json_success(array('fragments' => $fragments, 'cart_hash' => apply_filters('woocommerce_add_to_cart_hash', WC()->cart->get_cart_hash())));
    } else {
        wp_send_json_error();
    }
    wp_die();
}