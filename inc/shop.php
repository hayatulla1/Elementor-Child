<?php
/**
 * ============================================================================
 * SHORTCODE DOCUMENTATION & USAGE GUIDE
 * ============================================================================
 * * 1. [hkdev_shop] - Main Versatile Shop Shortcode
 * Attributes:
 * - limit:            (int) Number of products to show. (Default: 12)
 * - columns:          (int) Grid columns. (Default: 4, Supports: 2, 3, 4, 5, 6)
 * - category:         (string) Product category slugs to include. (Auto loads child categories in tabs)
 * - exclude:          (string) Product category slugs to exclude, comma separated.
 * - type:             (string) Filter mode: "recent", "best_selling", or "trending".
 * - days:             (int) Days to look back for "trending" type.
 * - order_by:         (string) Sorting order: "ASC" or "DSC". (Default: "DSC")
 * - show_tabs:        (string) Show/hide category tabs. (Values: "yes", "no")
 * - include_children: (string) Show products from child categories. (Values: "yes", "no", Default: "yes")
 * - style:            (string) Layout style: "grid" or "carousel". (Default: "grid")
 * * * Example: [hkdev_shop category="grocery" columns="4"]
 *******[hkdev_shop is_related="yes" show_tabs="no"]
 * ============================================================================
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// 1. Redirect Handler (Buy Now)
if (!function_exists('hkdev_buy_now_redirect_handler')) {
    add_filter('woocommerce_add_to_cart_redirect', 'hkdev_buy_now_redirect_handler');
    function hkdev_buy_now_redirect_handler($url) {
        if (isset($_REQUEST['hkdev_buy_now']) && $_REQUEST['hkdev_buy_now'] == 'yes') {
            return wc_get_checkout_url();
        }
        return $url;
    }
}

/**
 * Helper: Calculate product sales for a specific number of days
 */
function hkdev_get_sales_by_period($product_id, $days = 7) {
    if ($days <= 0) return 0;
    $date_from = gmdate('Y-m-d', strtotime("-{$days} days", current_time('timestamp', true)));
    $args = array(
        'status' => array('wc-completed', 'wc-processing'),
        'date_created' => '>' . $date_from,
        'return' => 'ids',
        'limit' => -1
    );
    $orders = wc_get_orders($args);
    $total_qty = 0;
    foreach ($orders as $order_id) {
        $order = wc_get_order($order_id);
        foreach ($order->get_items() as $item) {
            if ($item->get_product_id() == $product_id) {
                $total_qty += $item->get_quantity();
            }
        }
    }
    return $total_qty;
}

// 2. Product Card Rendering Helper
function hkdev_render_single_product_card($post_id, $trending_days = 0, $is_carousel = false) {
    global $product;
    $product = wc_get_product($post_id);
    if (!$product) return;

    $wc_defaults = [
        ['woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10],
        ['woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10],
        ['woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10],
        ['woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10],
        ['woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10],
        ['woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5],
        ['woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10],
    ];
    foreach ($wc_defaults as $hook) { remove_action($hook[0], $hook[1], $hook[2]); }

    $stock_status = $product->get_stock_status();
    $is_variable  = $product->is_type('variable');
    $permalink    = get_permalink($post_id);
    $checkout_url = wc_get_checkout_url();
    
    $total_sales    = (int)$product->get_total_sales();
    $trending_sales = ($trending_days > 0) ? hkdev_get_sales_by_period($post_id, $trending_days) : 0;

    $card_class = $is_carousel ? 'hkdev-product-card swiper-slide' : 'hkdev-product-card';
    ?>
    <div class="<?php echo esc_attr($card_class); ?> product type-product">
        <?php do_action( 'woocommerce_before_shop_loop_item' ); ?>

        <div class="hkdev-img-box" style="position: relative;">
            <?php do_action( 'woocommerce_before_shop_loop_item_title' ); ?>

            <a href="<?php echo esc_url($permalink); ?>">
                <?php echo $product->get_image('woocommerce_thumbnail'); ?>
            </a>
            
            <div class="hkdev-badge-container">
                <?php 
                if ($product->is_on_sale()) {
                    $percentage = 0;
                    if ($is_variable) {
                        $prices = $product->get_variation_prices();
                        $percentages = array();
                        foreach ($prices['regular_price'] as $key => $regular_price) {
                            $sale_price = $prices['sale_price'][$key];
                            if ($sale_price < $regular_price && (float)$regular_price > 0) {
                                $percentages[] = round((($regular_price - $sale_price) / $regular_price) * 100);
                            }
                        }
                        $percentage = !empty($percentages) ? max($percentages) : 0;
                    } else {
                        $regular_price = (float)$product->get_regular_price();
                        $sale_price = (float)$product->get_sale_price();
                        if ($regular_price > 0) {
                            $percentage = round((($regular_price - $sale_price) / $regular_price) * 100);
                        }
                    }
                    if ($percentage > 0) {
                        echo '<span class="hkdev-badge hkdev-sale-badge">' . $percentage . '% ' . (function_exists('hkdev_t') ? hkdev_t('off') : 'Off') . '</span>';
                    }
                }
                if ($trending_sales >= 3) {
                    $label = ($trending_days == 1) ? (function_exists('hkdev_t') ? hkdev_t('hot_today') : 'Hot Today') : (function_exists('hkdev_t') ? hkdev_t('trending') : 'Trending');
                    echo '<span class="hkdev-badge hkdev-trending-badge">' . $label . '</span>';
                } elseif ($total_sales >= 15) {
                    echo '<span class="hkdev-badge hkdev-best-seller-badge">' . (function_exists('hkdev_t') ? hkdev_t('best_seller') : 'Best Seller') . '</span>';
                }
                ?>
            </div>
        </div>
        
        <div class="hkdev-content-box">
            <div class="hkdev-meta-row">
                <span class="hkdev-cat-label"><?php echo wc_get_product_category_list($post_id, ', ', '', ''); ?></span>
                <span class="hkdev-stock-dot <?php echo esc_attr($stock_status); ?>" title="<?php echo ($stock_status == 'instock') ? esc_attr(function_exists('hkdev_t') ? hkdev_t('in_stock') : 'In Stock') : esc_attr(function_exists('hkdev_t') ? hkdev_t('out_of_stock') : 'Out of Stock'); ?>"></span>
            </div>

            <?php do_action( 'woocommerce_shop_loop_item_title' ); ?>
            <h2 class="hkdev-title"><a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html( get_the_title($post_id) ); ?></a></h2>
            
            <?php do_action( 'woocommerce_after_shop_loop_item_title' ); ?>
            <div class="hkdev-price-container">
                <?php echo $product->get_price_html(); ?>
            </div>

            <?php do_action( 'woocommerce_after_shop_loop_item' ); ?>

            <div class="hkdev-footer-actions">
                <?php if ($stock_status == 'instock') : ?>
                    <?php if ($is_variable) : ?>
                        <a href="<?php echo esc_url($permalink); ?>" class="hkdev-btn-full"><?php echo function_exists('hkdev_t') ? hkdev_t('buy_now') : 'Buy Now'; ?></a>
                    <?php else : ?>
                        <div class="hkdev-action-group">
                            <a href="?add-to-cart=<?php echo $post_id; ?>" 
                               data-product_id="<?php echo $post_id; ?>" 
                               class="hkdev-cart-btn hkdev-ajax-add ajax_add_to_cart add_to_cart_button" 
                               title="<?php echo esc_attr(function_exists('hkdev_t') ? hkdev_t('add_to_cart') : 'Add to Cart'); ?>">
                                 <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="hkdev-svg-icon"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                            </a>
                            <a href="<?php echo esc_url(add_query_arg(array('add-to-cart' => $post_id, 'hkdev_buy_now' => 'yes'), $checkout_url)); ?>" 
                               class="hkdev-order-btn"
                               data-checkout_url="<?php echo esc_url($checkout_url); ?>"><?php echo function_exists('hkdev_t') ? hkdev_t('buy_now') : 'Buy Now'; ?></a>
                        </div>
                    <?php endif; ?>
                <?php else : ?>
                    <button disabled class="hkdev-btn-disabled"><?php echo function_exists('hkdev_t') ? hkdev_t('stock_out') : 'Stock Out'; ?></button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
    foreach ($wc_defaults as $hook) { add_action($hook[0], $hook[1], $hook[2]); }
}

// 3. AJAX Callback (Updated for Stock Sorting)
add_action('wp_ajax_hkdev_filter_products', 'hkdev_ajax_filter_products_callback');
add_action('wp_ajax_nopriv_hkdev_filter_products', 'hkdev_ajax_filter_products_callback');

function hkdev_ajax_filter_products_callback() {
    $cat_slug         = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
    $exclude          = isset($_POST['exclude']) ? sanitize_text_field($_POST['exclude']) : '';
    $limit            = isset($_POST['limit']) ? intval($_POST['limit']) : 12;
    $type             = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'recent';
    $days             = isset($_POST['days']) ? intval($_POST['days']) : 0;
    $style            = isset($_POST['style']) ? sanitize_text_field($_POST['style']) : 'grid';
    $order_by         = isset($_POST['order_by']) ? strtoupper(sanitize_text_field($_POST['order_by'])) : 'DESC';
    $include_children = (isset($_POST['include_children']) && $_POST['include_children'] === 'no') ? false : true;

    $order = ($order_by === 'ASC') ? 'ASC' : 'DESC';

    // Base Args with Stock Status meta key
    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => $limit,
        'post_status'    => 'publish',
        'meta_key'       => '_stock_status', // Required for sorting by stock
    );

    // Dynamic sorting: Stock first, then selected type
    if ($type === 'best_selling') {
        $args['orderby'] = array(
            'meta_value'     => 'ASC', // instock (i) before outofstock (o)
            'meta_value_num' => $order, // sales count
        );
        // Special handle when multiple metas are used
        $args['meta_query'] = array(
            'relation' => 'AND',
            array('key' => '_stock_status'),
            array('key' => 'total_sales')
        );
    } else {
        $args['orderby'] = array(
            'meta_value' => 'ASC', // instock first
            'date'       => $order, // recent first
        );
    }

    $tax_query = array('relation' => 'AND');

    if (!empty($cat_slug)) {
        $slugs = array_map('trim', explode(',', $cat_slug));
        $tax_query[] = array(
            'taxonomy'         => 'product_cat',
            'field'            => 'slug',
            'terms'            => $slugs,
            'include_children' => $include_children,
            'operator'         => 'IN'
        );
    }

    if (!empty($exclude)) {
        $ex_slugs = array_map('trim', explode(',', $exclude));
        $tax_query[] = array(
            'taxonomy'         => 'product_cat',
            'field'            => 'slug',
            'terms'            => $ex_slugs,
            'include_children' => $include_children,
            'operator'         => 'NOT IN'
        );
    }

    if (count($tax_query) > 1) {
        $args['tax_query'] = $tax_query;
    }

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        while ($query->have_posts()) : $query->the_post();
            hkdev_render_single_product_card(get_the_ID(), $days, ($style === 'carousel'));
        endwhile;
        wp_reset_postdata();
    } else {
        echo '<div class="hkdev-no-product-msg">' . (function_exists('hkdev_t') ? hkdev_t('product_not_found') : 'Product Not Found') . '</div>';
    }
    wp_die();
}

// 4. Main Master Shortcode [hkdev_shop] (Updated for Stock Sorting)
function hkdev_master_shop_shortcode($atts) {
    $atts = shortcode_atts(array(
        'limit'            => 12,
        'columns'          => 4,
        'category'         => '', 
        'exclude'          => '', 
        'type'             => 'recent', 
        'days'             => 0,        
        'order_by'         => 'DESC', 
        'show_tabs'        => 'yes',
        'include_children' => 'yes',
        'is_related'       => 'no',
        'style'            => 'grid', 
    ), $atts);

    $unique_id = 'hkdev-shop-' . wp_rand(1000, 9999);
    $order = (strtoupper($atts['order_by']) === 'ASC') ? 'ASC' : 'DESC';
    $include_children_val = ($atts['include_children'] === 'no') ? false : true;

    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => (int)$atts['limit'],
        'post_status'    => 'publish',
        'meta_key'       => '_stock_status',
    );

    // Sorting Logic: Always prioritize Stock Status ASC (i before o)
    if ($atts['type'] === 'best_selling') {
        $args['orderby'] = array(
            'meta_value'     => 'ASC',
            'meta_value_num' => $order,
        );
        $args['meta_query'] = array(
            'relation' => 'AND',
            array('key' => '_stock_status'),
            array('key' => 'total_sales')
        );
    } else {
        $args['orderby'] = array(
            'meta_value' => 'ASC',
            'date'       => $order,
        );
    }

    $current_cat_id = 0;
    $exclude_ids = array();

    if ($atts['is_related'] === 'yes' && is_product()) {
        global $product;
        $current_product_id = get_the_ID();
        $exclude_ids[] = $current_product_id;
        $args['post__not_in'] = $exclude_ids;
        $terms = get_the_terms($current_product_id, 'product_cat');
        if ($terms && !is_wp_error($terms)) {
            $cat_slugs = wp_list_pluck($terms, 'slug');
            $atts['category'] = implode(',', $cat_slugs);
        }
    }

    if (is_product_category()) {
        $current_cat_obj = get_queried_object();
        $current_cat_id = $current_cat_obj->term_id;
        if (empty($atts['category'])) {
            $atts['category'] = $current_cat_obj->slug;
        }
    }

    $tax_query = array('relation' => 'AND');
    if (!empty($atts['category'])) {
        $tax_query[] = array(
            'taxonomy'         => 'product_cat',
            'field'            => 'slug',
            'terms'            => array_map('trim', explode(',', $atts['category'])),
            'include_children' => $include_children_val,
            'operator'         => 'IN'
        );
    }
    if (!empty($atts['exclude'])) {
        $tax_query[] = array(
            'taxonomy'         => 'product_cat',
            'field'            => 'slug',
            'terms'            => array_map('trim', explode(',', $atts['exclude'])),
            'include_children' => $include_children_val,
            'operator'         => 'NOT IN'
        );
    }
    if (count($tax_query) > 1) {
        $args['tax_query'] = $tax_query;
    }

    $query = new WP_Query($args);
    ob_start();
    ?>

    <div class="hkdev-shop-wrapper" id="<?php echo esc_attr($unique_id); ?>"
         data-limit="<?php echo esc_attr($atts['limit']); ?>" 
         data-columns="<?php echo esc_attr($atts['columns']); ?>"
         data-type="<?php echo esc_attr($atts['type']); ?>"
         data-days="<?php echo esc_attr($atts['days']); ?>"
         data-order_by="<?php echo esc_attr($atts['order_by']); ?>"
         data-exclude="<?php echo esc_attr($atts['exclude']); ?>"
         data-include_children="<?php echo esc_attr($atts['include_children']); ?>"
         data-style="<?php echo esc_attr($atts['style']); ?>">
        
        <?php if($atts['show_tabs'] == 'yes') : 
            $get_terms_args = ['taxonomy' => 'product_cat', 'hide_empty' => true];
            
            if (!empty($atts['category'])) {
                $first_slug = array_map('trim', explode(',', $atts['category']))[0];
                $parent_term = get_term_by('slug', $first_slug, 'product_cat');
                if ($parent_term) {
                    $get_terms_args['parent'] = $parent_term->term_id;
                }
            } else {
                if ($current_cat_id > 0) { 
                    $get_terms_args['parent'] = $current_cat_id; 
                } else { 
                    $get_terms_args['parent'] = 0; 
                }
            }
            
            if (!empty($atts['exclude'])) {
                $ex_slugs = array_map('trim', explode(',', $atts['exclude']));
                $ex_ids = array();
                foreach ($ex_slugs as $es) {
                    $t = get_term_by('slug', $es, 'product_cat');
                    if ($t) $ex_ids[] = $t->term_id;
                }
                if (!empty($ex_ids)) {
                    $get_terms_args['exclude'] = $ex_ids;
                }
            }

            $categories = get_terms($get_terms_args);
            if (!empty($categories) && !is_wp_error($categories)) : ?>
                <div class="hkdev-tabs-container">
                    <div class="hkdev-tabs-scroll">
                        <button class="hkdev-tab-item active" data-slug="<?php echo esc_attr($atts['category']); ?>"><?php echo function_exists('hkdev_t') ? hkdev_t('all') : 'All'; ?></button>
                        <?php foreach ($categories as $cat) : ?>
                            <button class="hkdev-tab-item" data-slug="<?php echo esc_attr($cat->slug); ?>">
                                <?php echo esc_html($cat->name); ?> <span class="hkdev-tab-count"><?php echo absint($cat->count); ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="hkdev-grid-container">
            <div class="hkdev-ajax-loader"><div class="hkdev-spinner"><i class="fa-solid fa-spinner fa-spin"></i></div></div>
            
            <?php if($atts['style'] === 'carousel') : ?>
                <div class="swiper hkdev-swiper-container hkdev-loading-carousel">
                    <div class="swiper-wrapper hkdev-shop-grid">
                        <?php if ($query->have_posts()) : ?>
                            <?php while ($query->have_posts()) : $query->the_post(); 
                                hkdev_render_single_product_card(get_the_ID(), (int)$atts['days'], true);
                            endwhile; wp_reset_postdata(); ?>
                        <?php else : ?>
                            <div class="hkdev-no-product-msg"><?php echo function_exists('hkdev_t') ? hkdev_t('product_not_found') : 'Product Not Found'; ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="hkdev-carousel-dots swiper-pagination"></div>
                </div>
                <div class="hkdev-nav-btn hkdev-prev-<?php echo esc_attr($unique_id); ?> kh-prev"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="15 18 9 12 15 6"></polyline></svg></div>
                <div class="hkdev-nav-btn hkdev-next-<?php echo esc_attr($unique_id); ?> kh-next"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="9 18 15 12 9 6"></polyline></svg></div>
            <?php else : ?>
                <div class="hkdev-shop-grid hkdev-columns-<?php echo esc_attr($atts['columns']); ?>">
                    <?php if ($query->have_posts()) : ?>
                        <?php while ($query->have_posts()) : $query->the_post(); 
                            hkdev_render_single_product_card(get_the_ID(), (int)$atts['days'], false);
                        endwhile; wp_reset_postdata(); ?>
                    <?php else : ?>
                        <div class="hkdev-no-product-msg"><?php echo function_exists('hkdev_t') ? hkdev_t('product_not_found') : 'Product Not Found'; ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="hkdev-toast-master"><div class="hkdev-toast-inner"><span class="hkdev-toast-icon"><i class="fa-solid fa-check-circle"></i></span><span class="hkdev-toast-text"><?php echo function_exists('hkdev_t') ? hkdev_t('added_success') : 'Successfully added!'; ?></span></div></div>
    
    <?php return ob_get_clean();
}
add_shortcode('hkdev_shop', 'hkdev_master_shop_shortcode');

function hkdev_trending_shop_shortcode($atts) {
    $atts = shortcode_atts(array('limit' => 8, 'columns' => 4, 'days' => 7, 'cat' => '', 'exclude' => '', 'include_children' => 'yes', 'order_by' => 'DESC', 'style' => 'grid'), $atts);
    return hkdev_master_shop_shortcode(array('limit' => $atts['limit'], 'columns' => $atts['columns'], 'category' => $atts['cat'], 'exclude' => $atts['exclude'], 'include_children' => $atts['include_children'], 'type' => 'trending', 'days' => $atts['days'], 'order_by' => $atts['order_by'], 'show_tabs' => 'no', 'style' => $atts['style']));
}
add_shortcode('hkdev_trending', 'hkdev_trending_shop_shortcode');