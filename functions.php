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
                    ['jquery'], 
                    filemtime( $file ),
                    true
                );
            }
        }
    }

    // Global AJAX Object for JS files
    wp_localize_script( 'jquery', 'hkdev_ajax_obj', [
        'ajax_url' => admin_url( 'admin-ajax.php' )
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