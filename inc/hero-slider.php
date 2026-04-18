<?php
/**
 * Plugin/Snippet Name: HKDEV Hero Banner Slider with Admin Menu
 * Shortcode: [hkdev_hero_slider location="slug"]
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// =======================================================
// 1. Register Custom Post Type & Taxonomy for Dashboard Menu
// =======================================================
add_action( 'init', 'hkdev_register_banner_post_type' );
function hkdev_register_banner_post_type() {
    // Post Type
    register_post_type( 'hkdev_banner', array(
        'labels' => array(
            'name'          => 'Banners',
            'singular_name' => 'Banner',
            'add_new'       => 'Add New Banner',
            'add_new_item'  => 'Add New Banner Image',
            'edit_item'     => 'Edit Banner',
            'all_items'     => 'All Banners',
        ),
        'public'        => false, // We don't need single pages for banners
        'show_ui'       => true,
        'show_in_menu'  => true,
        'menu_icon'     => 'dashicons-images-alt2', // Icon in dashboard
        'supports'      => array( 'title', 'thumbnail' ), // Title & Featured Image
    ) );

    // Taxonomy (Location / Category)
    register_taxonomy( 'hkdev_banner_loc', 'hkdev_banner', array(
        'labels' => array(
            'name'          => 'Locations',
            'singular_name' => 'Location',
            'menu_name'     => 'Locations',
        ),
        'hierarchical'      => true,
        'show_admin_column' => true,
    ) );
}

// =======================================================
// 2. Add Custom Field for Banner Link
// =======================================================
add_action( 'add_meta_boxes', 'hkdev_banner_link_meta_box' );
function hkdev_banner_link_meta_box() {
    add_meta_box( 'hkdev_banner_link_box', 'Banner Link / URL', 'hkdev_banner_link_callback', 'hkdev_banner', 'normal', 'high' );
}

function hkdev_banner_link_callback( $post ) {
    wp_nonce_field( 'hkdev_save_banner_link', 'hkdev_banner_meta_nonce' );
    $value = get_post_meta( $post->ID, '_hkdev_banner_link', true );
    echo '<input type="url" name="hkdev_banner_link" value="' . esc_attr( $value ) . '" style="width:100%; padding: 10px;" placeholder="https://example.com/product/..." />';
    echo '<p class="description">Optional: Enter the URL where users will go when they click this banner.</p>';
}

add_action( 'save_post', 'hkdev_save_banner_link_data' );
function hkdev_save_banner_link_data( $post_id ) {
    if ( ! isset( $_POST['hkdev_banner_meta_nonce'] ) || ! wp_verify_nonce( $_POST['hkdev_banner_meta_nonce'], 'hkdev_save_banner_link' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    if ( isset( $_POST['hkdev_banner_link'] ) ) {
        update_post_meta( $post_id, '_hkdev_banner_link', esc_url_raw( $_POST['hkdev_banner_link'] ) );
    }
}

// =======================================================
// 3. Display Image in Admin Columns for better UI
// =======================================================
add_filter( 'manage_hkdev_banner_posts_columns', 'hkdev_banner_columns_head' );
function hkdev_banner_columns_head( $columns ) {
    $new_columns = array();
    $new_columns['cb'] = $columns['cb'];
    $new_columns['thumbnail'] = 'Image';
    $new_columns['title'] = $columns['title'];
    $new_columns['taxonomy-hkdev_banner_loc'] = 'Location';
    $new_columns['date'] = $columns['date'];
    return $new_columns;
}

add_action( 'manage_hkdev_banner_posts_custom_column', 'hkdev_banner_columns_content', 10, 2 );
function hkdev_banner_columns_content( $column, $post_id ) {
    if ( $column == 'thumbnail' ) {
        $thumb = get_the_post_thumbnail( $post_id, array( 80, 80 ) );
        echo $thumb ? $thumb : 'No Image';
    }
}

// =======================================================
// 4. Shortcode to Display Banner Slider
// =======================================================
function hkdev_hero_slider_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'location' => '',    // specific location slug (e.g. "home")
        'limit'    => -1,    // Show all by default
        'autoplay' => 'yes',
    ), $atts, 'hkdev_hero_slider' );

    $args = array(
        'post_type'      => 'hkdev_banner',
        'posts_per_page' => intval( $atts['limit'] ),
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    if ( ! empty( $atts['location'] ) ) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'hkdev_banner_loc',
                'field'    => 'slug',
                'terms'    => sanitize_text_field( $atts['location'] ),
            )
        );
    }

    $query = new WP_Query( $args );

    if ( ! $query->have_posts() ) {
        return '<p style="text-align:center; padding: 20px; color:#999;">' . hkdev_t('no_banner_found') . '</p>';
    }

    $unique_id = 'hkdev-hero-' . wp_rand( 10000, 99999 );
    $autoplay_data = ( $atts['autoplay'] === 'yes' ) ? 'true' : 'false';

    ob_start();
    ?>
    <div class="hkdev-hero-section-wrapper" id="<?php echo esc_attr( $unique_id ); ?>">
        <div class="swiper hkdev-hero-swiper" data-autoplay="<?php echo esc_attr( $autoplay_data ); ?>">
            <div class="swiper-wrapper">
                <?php 
                while ( $query->have_posts() ) : $query->the_post(); 
                    if ( ! has_post_thumbnail() ) continue; // Skip if no image

                    $image_url = get_the_post_thumbnail_url( get_the_ID(), 'full' );
                    $link = get_post_meta( get_the_ID(), '_hkdev_banner_link', true );
                ?>
                    <div class="swiper-slide hkdev-hero-slide">
                        <?php if ( ! empty( $link ) ) : ?>
                            <a href="<?php echo esc_url( $link ); ?>" class="hkdev-hero-link">
                                <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php the_title_attribute(); ?>">
                            </a>
                        <?php else : ?>
                            <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php the_title_attribute(); ?>">
                        <?php endif; ?>
                    </div>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
            
            <!-- Pagination Dots -->
            <div class="hkdev-hero-pagination swiper-pagination"></div>
            
            <!-- Navigation Arrows -->
            <div class="hkdev-hero-nav-btn hkdev-hero-prev"><i class="fa-solid fa-chevron-left"></i></div>
            <div class="hkdev-hero-nav-btn hkdev-hero-next"><i class="fa-solid fa-chevron-right"></i></div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'hkdev_hero_slider', 'hkdev_hero_slider_shortcode' );