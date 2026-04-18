<?php
/**
 * Plugin/Snippet Name: HKDEV Category Slider
 * Shortcode: [hkdev_category_slider]
 * 
 * Attributes:
 * - limit: Number of categories to show (Default: 10, use -1 for all)
 * - hide_empty: Hide categories without products (yes/no, Default: yes)
 * - show_child: Show sub-categories (yes/no, Default: no)
 * - include: Comma separated Category SLUGS to strictly show (e.g. "honey,ghee,nuts-seeds")
 * - exclude: Comma separated Category SLUGS to hide (e.g. "uncategorized,others")
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function hkdev_category_slider_shortcode( $atts ) {
    if ( ! class_exists( 'WooCommerce' ) ) return '';

    $atts = shortcode_atts( array(
        'limit'      => 10,
        'hide_empty' => 'yes',
        'show_child' => 'no',   // Default: Only parent categories
        'include'    => '',     // Specific SLUGS to show
        'exclude'    => '',     // Specific SLUGS to hide
    ), $atts, 'hkdev_category_slider' );

    $hide_empty = ( $atts['hide_empty'] === 'yes' || $atts['hide_empty'] == 1 );
    $show_child = ( $atts['show_child'] === 'yes' || $atts['show_child'] == 1 );

    $args = array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => $hide_empty,
        'number'     => $atts['limit'] == -1 ? 0 : intval( $atts['limit'] ),
    );

    // 1. Show/Hide Child Categories (If 'no', only get top-level parents)
    if ( ! $show_child ) {
        $args['parent'] = 0;
    }

    // 2. Include specific Categories by SLUG
    if ( ! empty( $atts['include'] ) ) {
        $slugs = array_filter( array_map( 'trim', explode( ',', $atts['include'] ) ) );
        $include_ids = array();
        
        foreach ( $slugs as $slug ) {
            $term = get_term_by( 'slug', $slug, 'product_cat' );
            if ( $term ) {
                $include_ids[] = $term->term_id;
            }
        }

        if ( ! empty( $include_ids ) ) {
            $args['include'] = $include_ids;
            $args['orderby'] = 'include'; // Maintain the exact order given in the shortcode
            unset( $args['parent'] ); // Ignore parent rule if specific slugs are requested
        } else {
            return '<p style="text-align:center; color:#999; padding:20px;">' . hkdev_t('no_specific_cat') . '</p>';
        }
    }

    // 3. Exclude specific Categories by SLUG
    if ( ! empty( $atts['exclude'] ) ) {
        $slugs = array_filter( array_map( 'trim', explode( ',', $atts['exclude'] ) ) );
        $exclude_ids = array();
        
        foreach ( $slugs as $slug ) {
            $term = get_term_by( 'slug', $slug, 'product_cat' );
            if ( $term ) {
                $exclude_ids[] = $term->term_id;
            }
        }

        if ( ! empty( $exclude_ids ) ) {
            $args['exclude'] = $exclude_ids;
        }
    }

    $categories = get_terms( $args );

    if ( empty( $categories ) || is_wp_error( $categories ) ) {
        return '<p style="text-align:center; color:#999; padding:20px;">' . hkdev_t('no_cat_found') . '</p>';
    }

    $unique_id = 'hkdev-cat-slider-' . wp_rand( 1000, 9999 );

    ob_start();
    ?>
    <!-- Swiper & Icons CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    <div class="hkdev-cat-section-wrapper" id="<?php echo esc_attr( $unique_id ); ?>">
        <div class="hkdev-cat-container">
            <div class="swiper hkdev-category-swiper">
                <div class="swiper-wrapper">
                    <?php foreach ( $categories as $category ) : 
                        $thumbnail_id = get_term_meta( $category->term_id, 'thumbnail_id', true );
                        if ( $thumbnail_id ) {
                            $image_url = wp_get_attachment_image_url( $thumbnail_id, 'thumbnail' );
                        } else {
                            $image_url = wc_placeholder_img_src(); // Fallback image
                        }
                        $category_link = get_term_link( $category );
                    ?>
                        <div class="swiper-slide hkdev-cat-slide">
                            <a href="<?php echo esc_url( $category_link ); ?>" class="hkdev-cat-link">
                                <div class="hkdev-cat-img-box">
                                    <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $category->name ); ?>">
                                </div>
                                <span class="hkdev-cat-name"><?php echo esc_html( $category->name ); ?></span>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Custom Navigation Arrows -->
            <div class="hkdev-cat-nav-btn hkdev-cat-prev"><i class="fa-solid fa-chevron-left"></i></div>
            <div class="hkdev-cat-nav-btn hkdev-cat-next"><i class="fa-solid fa-chevron-right"></i></div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'hkdev_category_slider', 'hkdev_category_slider_shortcode' );