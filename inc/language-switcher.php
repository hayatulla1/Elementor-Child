<?php
/**
 * HKDEV Floating Language Switcher
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if (!function_exists('hkdev_global_floating_lang_switcher')) {
    add_action('wp_footer', 'hkdev_global_floating_lang_switcher');
    function hkdev_global_floating_lang_switcher() {
        
        $current = (isset($_COOKIE['hkdev_lang']) && $_COOKIE['hkdev_lang'] === 'en') ? 'en' : 'bn';
        $target  = ($current === 'en') ? 'bn' : 'en';
        $label   = ($current === 'en') ? 'বাংলা' : 'English';
        
        ?>
        <div id="hkdev-floating-lang-wrapper">
            <div id="hkdev-lang-switcher-trigger" class="hkdev-lang-btn-el" data-target-lang="<?php echo esc_attr($target); ?>">
                <!-- Pure SVG Globe Icon -->
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="2" y1="12" x2="22" y2="12"></line>
                    <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                </svg>
                <span><?php echo esc_html($label); ?></span>
            </div>
        </div>
        <?php
    }
}