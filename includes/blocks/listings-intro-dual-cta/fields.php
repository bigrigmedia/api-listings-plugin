<?php
/**
 * Field group: intro copy + dual CTAs (listings found / no listings) with configurable backgrounds.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('acf_add_local_field_group')) {
    return;
}

acf_add_local_field_group(array(
    'key' => 'group_listings_api_intro_dual_cta',
    'title' => 'Listings API — Intro & dual CTAs',
    'fields' => array(
        array(
            'key' => 'field_listings_api_intro_tab_intro',
            'label' => 'Intro',
            'name' => '',
            'type' => 'tab',
            'placement' => 'top',
        ),
        array(
            'key' => 'field_listings_api_intro_main',
            'label' => 'Intro text',
            'name' => 'intro_content',
            'type' => 'wysiwyg',
            'instructions' => 'Full-width section above the listing results.',
            'tabs' => 'all',
            'toolbar' => 'full',
            'media_upload' => 1,
            'delay' => 0,
        ),
        array(
            'key' => 'field_listings_api_intro_tab_found',
            'label' => 'CTA — listings found',
            'name' => '',
            'type' => 'tab',
            'placement' => 'top',
        ),
        array(
            'key' => 'field_listings_api_intro_found_bg_type',
            'label' => 'Background type',
            'name' => 'found_cta_bg_type',
            'type' => 'radio',
            'choices' => array(
                'color' => 'Color',
                'image' => 'Image',
            ),
            'default_value' => 'color',
            'layout' => 'horizontal',
            'return_format' => 'value',
        ),
        array(
            'key' => 'field_listings_api_intro_found_bg_color',
            'label' => 'Background color',
            'name' => 'found_cta_bg_color',
            'type' => 'color_picker',
            'default_value' => '',
            'conditional_logic' => array(
                array(
                    array(
                        'field' => 'field_listings_api_intro_found_bg_type',
                        'operator' => '==',
                        'value' => 'color',
                    ),
                ),
            ),
        ),
        array(
            'key' => 'field_listings_api_intro_found_bg_image',
            'label' => 'Background image',
            'name' => 'found_cta_bg_image',
            'type' => 'image',
            'return_format' => 'url',
            'preview_size' => 'medium',
            'library' => 'all',
            'conditional_logic' => array(
                array(
                    array(
                        'field' => 'field_listings_api_intro_found_bg_type',
                        'operator' => '==',
                        'value' => 'image',
                    ),
                ),
            ),
        ),
        array(
            'key' => 'field_listings_api_intro_found_white_text',
            'label' => 'White text',
            'name' => 'found_cta_white_text',
            'type' => 'true_false',
            'default_value' => 0,
            'ui' => 1,
            'message' => 'Make text within this CTA white',
        ),
        array(
            'key' => 'field_listings_api_intro_found_content',
            'label' => 'CTA content',
            'name' => 'found_cta_content',
            'type' => 'wysiwyg',
            'instructions' => 'Shown when listings are returned. Outer element uses class <code>listings-found-element</code> for your script.',
            'tabs' => 'all',
            'toolbar' => 'full',
            'media_upload' => 1,
            'delay' => 0,
        ),
        array(
            'key' => 'field_listings_api_intro_tab_none',
            'label' => 'CTA — no listings',
            'name' => '',
            'type' => 'tab',
            'placement' => 'top',
        ),
        array(
            'key' => 'field_listings_api_intro_none_bg_type',
            'label' => 'Background type',
            'name' => 'none_cta_bg_type',
            'type' => 'radio',
            'choices' => array(
                'color' => 'Color',
                'image' => 'Image',
            ),
            'default_value' => 'color',
            'layout' => 'horizontal',
            'return_format' => 'value',
        ),
        array(
            'key' => 'field_listings_api_intro_none_bg_color',
            'label' => 'Background color',
            'name' => 'none_cta_bg_color',
            'type' => 'color_picker',
            'default_value' => '',
            'conditional_logic' => array(
                array(
                    array(
                        'field' => 'field_listings_api_intro_none_bg_type',
                        'operator' => '==',
                        'value' => 'color',
                    ),
                ),
            ),
        ),
        array(
            'key' => 'field_listings_api_intro_none_bg_image',
            'label' => 'Background image',
            'name' => 'none_cta_bg_image',
            'type' => 'image',
            'return_format' => 'url',
            'preview_size' => 'medium',
            'library' => 'all',
            'conditional_logic' => array(
                array(
                    array(
                        'field' => 'field_listings_api_intro_none_bg_type',
                        'operator' => '==',
                        'value' => 'image',
                    ),
                ),
            ),
        ),
        array(
            'key' => 'field_listings_api_intro_none_white_text',
            'label' => 'White text',
            'name' => 'none_cta_white_text',
            'type' => 'true_false',
            'default_value' => 0,
            'ui' => 1,
            'message' => 'Make text within this CTA white',
        ),
        array(
            'key' => 'field_listings_api_intro_none_content',
            'label' => 'CTA content',
            'name' => 'none_cta_content',
            'type' => 'wysiwyg',
            'instructions' => 'Shown when no listings match. Outer element uses class <code>no-listings-element</code>.',
            'tabs' => 'all',
            'toolbar' => 'full',
            'media_upload' => 1,
            'delay' => 0,
        ),
        array(
            'key' => 'field_listings_api_intro_tab_no_brokered',
            'label' => 'CTA — no brokered listings',
            'name' => '',
            'type' => 'tab',
            'placement' => 'top',
        ),
        array(
            'key' => 'field_listings_api_intro_no_brokered_bg_type',
            'label' => 'Background type',
            'name' => 'no_brokered_cta_bg_type',
            'type' => 'radio',
            'choices' => array(
                'color' => 'Color',
                'image' => 'Image',
            ),
            'default_value' => 'color',
            'layout' => 'horizontal',
            'return_format' => 'value',
        ),
        array(
            'key' => 'field_listings_api_intro_no_brokered_bg_color',
            'label' => 'Background color',
            'name' => 'no_brokered_cta_bg_color',
            'type' => 'color_picker',
            'default_value' => '',
            'conditional_logic' => array(
                array(
                    array(
                        'field' => 'field_listings_api_intro_no_brokered_bg_type',
                        'operator' => '==',
                        'value' => 'color',
                    ),
                ),
            ),
        ),
        array(
            'key' => 'field_listings_api_intro_no_brokered_bg_image',
            'label' => 'Background image',
            'name' => 'no_brokered_cta_bg_image',
            'type' => 'image',
            'return_format' => 'url',
            'preview_size' => 'medium',
            'library' => 'all',
            'conditional_logic' => array(
                array(
                    array(
                        'field' => 'field_listings_api_intro_no_brokered_bg_type',
                        'operator' => '==',
                        'value' => 'image',
                    ),
                ),
            ),
        ),
        array(
            'key' => 'field_listings_api_intro_no_brokered_white_text',
            'label' => 'White text',
            'name' => 'no_brokered_cta_white_text',
            'type' => 'true_false',
            'default_value' => 0,
            'ui' => 1,
            'message' => 'Make text within this CTA white',
        ),
        array(
            'key' => 'field_listings_api_intro_no_brokered_content',
            'label' => 'CTA content',
            'name' => 'no_brokered_cta_content',
            'type' => 'wysiwyg',
            'instructions' => 'Shown when no brokered listings are found. Outer element uses class <code>no-brokered-element</code>.',
            'tabs' => 'all',
            'toolbar' => 'full',
            'media_upload' => 1,
            'delay' => 0,
        ),
    ),
    'location' => array(
        array(
            array(
                'param' => 'block',
                'operator' => '==',
                'value' => 'acf/listings-intro-dual-cta',
            ),
        ),
    ),
    'active' => true,
));
