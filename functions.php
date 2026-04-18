<?php
/**
 * Theme functions and definitions.
 *
 * For additional information on potential customization options,
 * read the developers' documentation:
 *
 * https://developers.elementor.com/docs/hello-elementor-theme/
 *
 * @package HelloElementorChild
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

define( 'HELLO_ELEMENTOR_CHILD_VERSION', '2.0.0' );

/**
 * ===================================================================
 * 1. ENQUEUE SCRIPTS & STYLES (Auto Load Assets)
 * ===================================================================
 */
function hello_elementor_child_scripts_styles() {

    // Load Parent and Child Theme Styles
    wp_enqueue_style(
        'hello-elementor-child-style',
        get_stylesheet_directory_uri() . '/style.css',
        [ 'hello-elementor-theme-style' ],
        HELLO_ELEMENTOR_CHILD_VERSION
    );

    // ---------------------------------------------------------------
    // Register & Enqueue Shared External CDN Assets
    // (Registered once here so individual modules never inline them)
    // ---------------------------------------------------------------
    wp_register_style(
        'hkdev-fontawesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
        [],
        '6.5.1'
    );
    wp_register_style(
        'hkdev-google-fonts',
        'https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap',
        [],
        null
    );
    wp_register_style(
        'hkdev-swiper-css',
        'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css',
        [],
        '11.0.0'
    );
    wp_register_script(
        'hkdev-swiper-js',
        'https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js',
        [],
        '11.0.0',
        true
    );

    wp_enqueue_style( 'hkdev-fontawesome' );
    wp_enqueue_style( 'hkdev-google-fonts' );
    wp_enqueue_style( 'hkdev-swiper-css' );
    wp_enqueue_script( 'hkdev-swiper-js' );

    // Auto-load All CSS Files from /assets/css/
    $css_dir = get_stylesheet_directory() . '/assets/css/';
    if ( is_dir( $css_dir ) ) {
        $css_files = glob( $css_dir . '*.css' );
        if ( ! empty( $css_files ) ) {
            foreach ( $css_files as $file ) {
                $filename = basename( $file );
                $handle   = 'hkdev-' . basename( $filename, '.css' ) . '-style';

                wp_enqueue_style(
                    $handle,
                    get_stylesheet_directory_uri() . '/assets/css/' . $filename,
                    [],
                    filemtime( $file )
                );
            }
        }
    }

    // Auto-load All JS Files from /assets/js/
    $js_dir = get_stylesheet_directory() . '/assets/js/';
    if ( is_dir( $js_dir ) ) {
        $js_files = glob( $js_dir . '*.js' );
        if ( ! empty( $js_files ) ) {
            foreach ( $js_files as $file ) {
                $filename = basename( $file );
                $handle   = 'hkdev-' . basename( $filename, '.js' ) . '-js';

                wp_enqueue_script(
                    $handle,
                    get_stylesheet_directory_uri() . '/assets/js/' . $filename,
                    [ 'jquery' ],
                    filemtime( $file ),
                    true
                );
            }
        }
    }

    // Global AJAX Object: URL + nonces used by inline JS across modules.
    // JS files must send the matching nonce as the `security` POST field.
    wp_localize_script( 'jquery', 'hkdev_ajax_obj', [
        'ajax_url'   => admin_url( 'admin-ajax.php' ),
        'mc_nonce'   => wp_create_nonce( 'hkdev_mc_nonce' ),
        'co_nonce'   => wp_create_nonce( 'hkdev_co_nonce' ),
        'cart_nonce' => wp_create_nonce( 'hkdev_cart_nonce' ),
    ] );

}
add_action( 'wp_enqueue_scripts', 'hello_elementor_child_scripts_styles' );


/**
 * ===================================================================
 * 2. AUTO-INCLUDE MODULES (Load all PHP files from /inc/)
 * ===================================================================
 */
$inc_dir = get_stylesheet_directory() . '/inc/';
if ( is_dir( $inc_dir ) ) {
    $php_files = glob( $inc_dir . '*.php' );
    if ( ! empty( $php_files ) ) {
        foreach ( $php_files as $file ) {
            require_once $file;
        }
    }
}