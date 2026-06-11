<?php
/**
 * Listings API — intro text + dual CTA block (listings found / no listings).
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('acf/init', function () {
    if (!function_exists('acf_register_block_type')) {
        return;
    }

    require_once __DIR__ . '/fields.php';

    acf_register_block_type(array(
        'name' => 'listings-intro-dual-cta',
        'title' => __('Listings intro & dual CTAs', 'textdomain'),
        'description' => __('Intro copy plus three CTAs toggled by the listings script (found, no listings, no brokered).', 'textdomain'),
        'category' => 'brm-api-listings',
        'icon' => 'align-wide',
        'keywords' => array('listings', 'cta', 'intro'),
        'mode' => 'preview',
        'supports' => array(
            'align' => array('wide', 'full'),
            'anchor' => true,
            'jsx' => true,
        ),
        'render_template' => __DIR__ . '/template.php',
    ));
});
