<?php
/**
 * Feature: WooCommerce Product Size Chart Meta Field (With Image Uploader)
 * Description: Adds a custom media upload field in the WooCommerce Product Data meta box.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// ===================================================================
// 1. ADD SIZE CHART UPLOAD FIELD IN WOOCOMMERCE PRODUCT DATA
// ===================================================================
add_action( 'woocommerce_product_options_general_product_data', 'hkdev_add_size_chart_field' );
function hkdev_add_size_chart_field() {
    global $post;
    
    // Get currently saved image URL
    $image_url = get_post_meta( $post->ID, '_hkdev_size_chart_url', true );
    
    echo '<div class="options_group">';
    ?>
    <p class="form-field _hkdev_size_chart_url_field">
        <label for="_hkdev_size_chart_url">Size Chart Image</label>
        <span class="hkdev-size-chart-wrapper" style="display:block; margin-left: 162px;">
            
            <!-- Image Preview -->
            <img src="<?php echo esc_url( $image_url ); ?>" id="hkdev-size-chart-preview" style="max-width:150px; height:auto; display: <?php echo $image_url ? 'block' : 'none'; ?>; margin-bottom: 10px; border: 1px solid #ccc; padding: 5px; border-radius: 4px;" />
            
            <!-- Hidden Input to store URL -->
            <input type="hidden" id="_hkdev_size_chart_url" name="_hkdev_size_chart_url" value="<?php echo esc_attr( $image_url ); ?>" />
            
            <!-- Upload & Remove Buttons -->
            <button type="button" class="button button-primary hkdev-upload-size-chart">Upload / Choose Image</button>
            <button type="button" class="button hkdev-remove-size-chart" style="color: #b32d2e; border-color: #b32d2e; <?php echo $image_url ? '' : 'display:none;'; ?>">Remove Image</button>
            <br>
            <span class="description">এখানে সাইজ চার্ট আপলোড করুন। সাইজ চার্ট না দিতে চাইলে "Remove Image" এ ক্লিক করুন।</span>
        </span>
    </p>
    <?php
    echo '</div>';
}

// ===================================================================
// 2. SAVE THE SIZE CHART URL
// ===================================================================
add_action( 'woocommerce_process_product_meta', 'hkdev_save_size_chart_field' );
function hkdev_save_size_chart_field( $post_id ) {
    if ( isset( $_POST['_hkdev_size_chart_url'] ) ) {
        $val = sanitize_text_field( $_POST['_hkdev_size_chart_url'] );
        update_post_meta( $post_id, '_hkdev_size_chart_url', $val );
    }
}

// ===================================================================
// 3. ENQUEUE WORDPRESS MEDIA UPLOADER & JAVASCRIPT
// ===================================================================
add_action( 'admin_enqueue_scripts', 'hkdev_size_chart_admin_scripts' );
function hkdev_size_chart_admin_scripts( $hook ) {
    global $post;
    if ( ! $post ) return;
    if ( ( $hook === 'post-new.php' || $hook === 'post.php' ) && 'product' === $post->post_type ) {
        wp_enqueue_media(); // Load WP Media Library system
    }
}

add_action('admin_footer', 'hkdev_size_chart_admin_js');
function hkdev_size_chart_admin_js() {
    global $post;
    if ( ! $post || 'product' !== $post->post_type ) return;
    ?>
    <script>
    jQuery(document).ready(function($){
        var mediaUploader;
        
        // Open Media Library
        $('.hkdev-upload-size-chart').on('click', function(e) {
            e.preventDefault();
            
            // If the uploader object has already been created, reopen the dialog
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }
            
            // Extend the wp.media object
            mediaUploader = wp.media.frames.file_frame = wp.media({
                title: 'Choose a Size Chart Image',
                button: { text: 'Use this image' },
                multiple: false // Only allow single file selection
            });
            
            // When a file is selected, grab the URL and set it as the text field's value
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#_hkdev_size_chart_url').val(attachment.url);
                $('#hkdev-size-chart-preview').attr('src', attachment.url).show();
                $('.hkdev-remove-size-chart').show();
            });
            
            // Open the uploader dialog
            mediaUploader.open();
        });

        // Remove Image
        $('.hkdev-remove-size-chart').on('click', function(e){
            e.preventDefault();
            $('#_hkdev_size_chart_url').val('');
            $('#hkdev-size-chart-preview').attr('src', '').hide();
            $(this).hide();
        });
    });
    </script>
    <?php
}