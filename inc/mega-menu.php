<?php
/* --- HKDEV Style Mega Menu --- */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

function hkdev_final_mega_menu($atts) {

    // Shortcode Attributes
    $atts = shortcode_atts(array(
        'show_child'    => 'no', // Default: no (change to 'yes' via shortcode)
        'include_slugs' => '',   // Specific category slugs separated by comma
        'exclude_slugs' => 'uncategorized,test-slug', // Default exclude
    ), $atts, 'hkdev_mega_menu');

    // Process Exclude Slugs
    $exclude_slugs = array_map('trim', explode(',', $atts['exclude_slugs']));
    $exclude_ids = array();
    foreach ($exclude_slugs as $slug) {
        if (!empty($slug)) {
            $term = get_term_by('slug', $slug, 'product_cat');
            if ($term) $exclude_ids[] = $term->term_id;
        }
    }

    // Process Include Slugs (Specific Categories)
    $include_ids = array();
    if (!empty($atts['include_slugs'])) {
        $include_slugs = array_map('trim', explode(',', $atts['include_slugs']));
        foreach ($include_slugs as $slug) {
            if (!empty($slug)) {
                $term = get_term_by('slug', $slug, 'product_cat');
                if ($term) $include_ids[] = $term->term_id;
            }
        }
    }

    // Main Parent Category Arguments
    $args = array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
        'exclude'    => $exclude_ids,
        'orderby'    => 'menu_order',
        'order'      => 'ASC',
        'parent'     => 0 // Only fetch top level categories first
    );

    // If specific categories requested, override parent constraint
    if (!empty($include_ids)) {
        $args['include'] = $include_ids;
        unset($args['parent']);
    }

    $categories = get_terms($args);

    if (empty($categories) || is_wp_error($categories)) {
        return '';
    }

    ob_start();
    ?>

    <div class="hkdev-mm-wrapper">

        <button class="hkdev-mm-btn hkdev-mm-toggle-btn" type="button">
            ☰ <?php echo esc_html( hkdev_t('all_categories') ); ?>
        </button>

        <div class="hkdev-mm-content">
            <div class="hkdev-mm-grid">
                <?php foreach ($categories as $category): ?>
                    <div class="hkdev-mm-col">
                        <!-- Parent Category -->
                        <a href="<?php echo esc_url(get_term_link($category)); ?>" class="hkdev-mm-item">
                            <span class="hkdev-mm-name"><?php echo esc_html($category->name); ?></span>
                            <small class="hkdev-mm-count"><?php echo absint($category->count); ?> <?php echo esc_html( hkdev_t('items') ); ?></small>
                        </a>

                        <!-- Child Categories -->
                        <?php 
                        if ($atts['show_child'] === 'yes') {
                            $children = get_terms(array(
                                'taxonomy'   => 'product_cat',
                                'parent'     => $category->term_id,
                                'hide_empty' => false,
                                'exclude'    => $exclude_ids
                            ));

                            if (!empty($children) && !is_wp_error($children)): ?>
                                <ul class="hkdev-mm-children">
                                    <?php foreach ($children as $child): ?>
                                        <li>
                                            <a href="<?php echo esc_url(get_term_link($child)); ?>">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                                                <?php echo esc_html($child->name); ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; 
                        } 
                        ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

    <?php
    return ob_get_clean();
}

add_shortcode('hkdev_mega_menu', 'hkdev_final_mega_menu');