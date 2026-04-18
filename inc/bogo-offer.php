<?php
/**
 * Feature: Ultimate AJAX-Friendly BOGO (Buy X Get Y)
 * Buy 1 Get 1 = 1 Paid + 1 Free (Count Fixed)
 * Version: 2.2.0 (Buy X Get X Fixed)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ===================================================================
// 1. ADMIN METABOXES
// ===================================================================
add_action( 'woocommerce_product_options_general_product_data', 'hkdev_add_bogo_fields' );
function hkdev_add_bogo_fields() {
    echo '<div class="options_group"><h4 style="padding:0 12px; margin:0;">🎁 BOGO Offer Settings</h4>';
    woocommerce_wp_checkbox( [ 'id' => '_hkdev_bogo_enabled', 'label' => 'Enable BOGO Offer' ] );
    woocommerce_wp_text_input( [ 'id' => '_hkdev_bogo_buy_qty', 'label' => 'Buy Qty (X)', 'type' => 'number', 'custom_attributes' => ['min'=>'1'] ] );
    woocommerce_wp_text_input( [ 'id' => '_hkdev_bogo_get_qty', 'label' => 'Get Free (Y)', 'type' => 'number', 'custom_attributes' => ['min'=>'1'] ] );
    echo '</div>';
}

add_action( 'woocommerce_admin_process_product_object', 'hkdev_save_bogo_fields' );
function hkdev_save_bogo_fields( $product ) {
    $product->update_meta_data( '_hkdev_bogo_enabled', isset( $_POST['_hkdev_bogo_enabled'] ) ? 'yes' : 'no' );
    if ( isset( $_POST['_hkdev_bogo_buy_qty'] ) ) $product->update_meta_data( '_hkdev_bogo_buy_qty', absint( $_POST['_hkdev_bogo_buy_qty'] ) );
    if ( isset( $_POST['_hkdev_bogo_get_qty'] ) ) $product->update_meta_data( '_hkdev_bogo_get_qty', absint( $_POST['_hkdev_bogo_get_qty'] ) );
}

add_action( 'product_cat_add_form_fields', 'hkdev_add_cat_bogo_fields' );
add_action( 'product_cat_edit_form_fields', 'hkdev_edit_cat_bogo_fields', 10, 2 );

function hkdev_add_cat_bogo_fields() { 
    echo '<div class="form-field"><label>Enable Category BOGO Offer</label><select name="hkdev_cat_bogo_enabled"><option value="no">No</option><option value="yes">Yes (Mix & Match)</option></select></div><div class="form-field"><label>Buy Qty (X)</label><input type="number" name="hkdev_cat_bogo_buy" min="1"></div><div class="form-field"><label>Get Free Qty (Y)</label><input type="number" name="hkdev_cat_bogo_get" min="1"></div>'; 
}

function hkdev_edit_cat_bogo_fields( $term ) {
    $enabled = get_term_meta( $term->term_id, '_hkdev_cat_bogo_enabled', true );
    $buy = get_term_meta( $term->term_id, '_hkdev_cat_bogo_buy', true );
    $get = get_term_meta( $term->term_id, '_hkdev_cat_bogo_get', true );
    echo '<tr class="form-field"><th scope="row"><label>Enable Category BOGO</label></th><td><select name="hkdev_cat_bogo_enabled"><option value="no" '.selected($enabled,'no',false).'>No</option><option value="yes" '.selected($enabled,'yes',false).'>Yes (Mix & Match)</option></select></td></tr><tr class="form-field"><th scope="row"><label>Buy Qty (X)</label></th><td><input type="number" name="hkdev_cat_bogo_buy" value="'.esc_attr($buy).'" min="1"></td></tr><tr class="form-field"><th scope="row"><label>Get Free Qty (Y)</label></th><td><input type="number" name="hkdev_cat_bogo_get" value="'.esc_attr($get).'" min="1"></td></tr>';
}

add_action( 'created_product_cat', 'hkdev_save_cat_bogo_fields' );
add_action( 'edited_product_cat', 'hkdev_save_cat_bogo_fields' );

function hkdev_save_cat_bogo_fields( $term_id ) {
    if ( isset( $_POST['hkdev_cat_bogo_enabled'] ) ) update_term_meta( $term_id, '_hkdev_cat_bogo_enabled', sanitize_text_field( $_POST['hkdev_cat_bogo_enabled'] ) );
    if ( isset( $_POST['hkdev_cat_bogo_buy'] ) ) update_term_meta( $term_id, '_hkdev_cat_bogo_buy', absint( $_POST['hkdev_cat_bogo_buy'] ) );
    if ( isset( $_POST['hkdev_cat_bogo_get'] ) ) update_term_meta( $term_id, '_hkdev_cat_bogo_get', absint( $_POST['hkdev_cat_bogo_get'] ) );
}

// ===================================================================
// 2. HELPER: GET BOGO RULE
// ===================================================================
function hkdev_get_active_bogo_rule( $product_id ) {
    if ( get_post_meta( $product_id, '_hkdev_bogo_enabled', true ) === 'yes' ) {
        return [ 
            'buy' => (int) get_post_meta( $product_id, '_hkdev_bogo_buy_qty', true ), 
            'get' => (int) get_post_meta( $product_id, '_hkdev_bogo_get_qty', true ), 
            'type' => 'product', 
            'id' => $product_id 
        ];
    }
    
    $terms = wc_get_product_terms( $product_id, 'product_cat' );
    if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
        foreach ( $terms as $term ) {
            if ( get_term_meta( $term->term_id, '_hkdev_cat_bogo_enabled', true ) === 'yes' ) {
                return [ 
                    'buy' => (int) get_term_meta( $term->term_id, '_hkdev_cat_bogo_buy', true ), 
                    'get' => (int) get_term_meta( $term->term_id, '_hkdev_cat_bogo_get', true ), 
                    'type' => 'category', 
                    'id' => $term->term_id 
                ];
            }
        }
    }
    return false;
}

// ===================================================================
// 3. ✅ FIXED: CART CALCULATION - BUY X GET X ACCURATE COUNT
// ===================================================================

add_action( 'woocommerce_cart_calculate_fees', 'hkdev_apply_bogo_dynamic_fees', 20, 1 );
function hkdev_apply_bogo_dynamic_fees( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
    if ( empty( $cart->cart_contents ) ) return;

    // ✅ RESET all free counts
    foreach ( $cart->cart_contents as $key => $item ) {
        $cart->cart_contents[$key]['hkdev_free_count'] = 0;
    }

    $pools = array();
    $grouped_fees = array();

    // GROUP items by BOGO rule
    foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
        $rule = hkdev_get_active_bogo_rule( $cart_item['product_id'] );
        
        if ( $rule && $rule['buy'] > 0 && $rule['get'] > 0 ) {
            $pool_key = $rule['type'] . '_' . $rule['id'];
            
            if ( ! isset( $pools[$pool_key] ) ) {
                $pools[$pool_key] = [ 
                    'rule' => $rule, 
                    'items_list' => []
                ];
            }

            $price = (float) $cart_item['data']->get_price();
            $name = $cart_item['data']->get_name();
            $product_id = $cart_item['product_id'];
            $quantity = $cart_item['quantity'];

            // Add entire item (with quantity)
            $pools[$pool_key]['items_list'][] = [
                'cart_key' => $cart_item_key,
                'price' => $price,
                'name' => $name,
                'product_id' => $product_id,
                'quantity' => $quantity
            ];
        }
    }

    // ✅ PROCESS each pool - Calculate FREE items
    foreach ( $pools as $pool ) {
        $rule = $pool['rule'];
        $items = $pool['items_list'];
        
        $buy_qty = $rule['buy'];
        $get_qty = $rule['get'];
        $group_size = $buy_qty + $get_qty;

        // Calculate total quantity in this pool
        $total_qty = 0;
        foreach ( $items as $item ) {
            $total_qty += $item['quantity'];
        }

        // ✅ Calculate how many FREE items
        // Formula: (Total Qty ÷ Group Size) × Get Qty = Free Items
        $num_groups = floor( $total_qty / $group_size );
        $free_items_total = $num_groups * $get_qty;

        // ✅ Only mark the cheapest items as free
        if ( $free_items_total > 0 ) {
            // Sort by price (cheapest first)
            usort($items, function($a, $b) {
                return $a['price'] <=> $b['price'];
            });

            $free_counter = 0;

            foreach ( $items as $item ) {
                $remaining_free = $free_items_total - $free_counter;
                
                if ( $remaining_free <= 0 ) {
                    break;
                }

                $item_qty = $item['quantity'];
                $cart_key = $item['cart_key'];
                $name = $item['name'];
                $price = $item['price'];

                // ✅ How many of this item can be free?
                $free_from_this_item = min($remaining_free, $item_qty);

                // Add to fees
                if ( ! isset($grouped_fees[$name]) ) {
                    $grouped_fees[$name] = [ 'qty' => 0, 'total_discount' => 0 ];
                }

                $grouped_fees[$name]['qty'] += $free_from_this_item;
                $grouped_fees[$name]['total_discount'] += ($free_from_this_item * $price);

                // ✅ Mark in cart
                $cart->cart_contents[$cart_key]['hkdev_free_count'] = $free_from_this_item;

                $free_counter += $free_from_this_item;
            }
        }
    }

    // ✅ ADD FEES
    foreach ( $grouped_fees as $name => $data ) {
        if ( $data['total_discount'] > 0 ) {
            $qty_text = $data['qty'] > 1 ? ' (x' . $data['qty'] . ')' : '';
            $fee_name = function_exists('hkdev_t') ? sprintf( hkdev_t('bogo_fee_name'), $name ) : "🎁 Free: {$name}";
            $cart->add_fee( $fee_name . $qty_text, -$data['total_discount'], true );
        }
    }
}

// ===================================================================
// 4. FRONTEND: CART ITEM BADGE
// ===================================================================
add_filter( 'woocommerce_cart_item_name', 'hkdev_bogo_cart_item_badge', 10, 3 );
function hkdev_bogo_cart_item_badge( $name, $cart_item, $cart_item_key ) {
    $free_count = isset( $cart_item['hkdev_free_count'] ) ? intval($cart_item['hkdev_free_count']) : 0;
    
    if ( $free_count > 0 ) {
        $label = function_exists('hkdev_t') ? sprintf( hkdev_t('bogo_cart_badge'), $free_count ) : "🎁 {$free_count} Item Free!";
        $name .= '<br><small class="hkdev-bogo-cart-badge">' . esc_html($label) . '</small>';
    }
    
    return $name;
}

// ===================================================================
// 5. FRONTEND: SHOP GRID BADGE
// ===================================================================
add_action( 'woocommerce_before_shop_loop_item_title', 'hkdev_shop_grid_bogo_badge', 15 );
function hkdev_shop_grid_bogo_badge() {
    global $product;
    if ( ! $product ) return;
    
    $rule = hkdev_get_active_bogo_rule( $product->get_id() );
    if ( $rule && $rule['buy'] > 0 && $rule['get'] > 0 ) {
        $badge_text = sprintf( hkdev_t('bogo_badge_loop'), $rule['buy'], $rule['get'] );
        echo '<span class="hkdev-bogo-grid-badge"><i class="fa-solid fa-gift"></i> ' . esc_html($badge_text) . '</span>';
    }
}

// ===================================================================
// 6. FRONTEND: SINGLE PRODUCT NOTICE
// ===================================================================
add_action( 'woocommerce_single_product_summary', 'hkdev_single_product_bogo_notice', 15 );
function hkdev_single_product_bogo_notice() {
    global $product;
    if ( ! $product ) return;
    
    $rule = hkdev_get_active_bogo_rule( $product->get_id() );
    if ( $rule && $rule['buy'] > 0 && $rule['get'] > 0 ) {
        $total_req = $rule['buy'] + $rule['get'];
        $notice = sprintf( hkdev_t('bogo_notice'), $rule['buy'], $rule['get'], $total_req );
        echo '<div class="hkdev-bogo-sp-notice">' . wp_kses_post($notice) . '</div>';
    }
}

// ===================================================================
// 7. AJAX: RECALCULATION
// ===================================================================
add_action( 'wp_ajax_hkdev_recalc_bogo', 'hkdev_ajax_recalc_bogo' );
add_action( 'wp_ajax_nopriv_hkdev_recalc_bogo', 'hkdev_ajax_recalc_bogo' );
function hkdev_ajax_recalc_bogo() {
    WC()->cart->calculate_totals();
    
    wp_send_json_success([
        'success' => true,
        'free_count' => hkdev_get_total_free_items_in_cart()
    ]);
}

/**
 * ✅ Get total free items count
 */
function hkdev_get_total_free_items_in_cart() {
    $total = 0;
    foreach ( WC()->cart->get_cart() as $item ) {
        $free_count = isset( $item['hkdev_free_count'] ) ? intval($item['hkdev_free_count']) : 0;
        $total += $free_count;
    }
    return $total;
}

// ============================================================================
// END OF BOGO
// ============================================================================