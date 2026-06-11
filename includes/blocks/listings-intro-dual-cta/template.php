<?php
/**
 * Intro text + dual CTA sections (listings found vs no listings).
 *
 * @var array $block Block settings and attributes.
 */

if (!defined('ABSPATH')) {
    exit;
}

$intro = get_field('intro_content');

$found_type = get_field('found_cta_bg_type');
$found_type = in_array($found_type, array('color', 'image'), true) ? $found_type : 'color';
$found_color = get_field('found_cta_bg_color');
$found_image = get_field('found_cta_bg_image');
$found_content = get_field('found_cta_content');
$found_white_text = get_field('found_cta_white_text');

$none_type = get_field('none_cta_bg_type');
$none_type = in_array($none_type, array('color', 'image'), true) ? $none_type : 'color';
$none_color = get_field('none_cta_bg_color');
$none_image = get_field('none_cta_bg_image');
$none_content = get_field('none_cta_content');
$none_white_text = get_field('none_cta_white_text');

$no_brokered_type = get_field('no_brokered_cta_bg_type');
$no_brokered_type = in_array($no_brokered_type, array('color', 'image'), true) ? $no_brokered_type : 'color';
$no_brokered_color = get_field('no_brokered_cta_bg_color');
$no_brokered_image = get_field('no_brokered_cta_bg_image');
$no_brokered_content = get_field('no_brokered_cta_content');
$no_brokered_white_text = get_field('no_brokered_cta_white_text');

$found_style = '';
if ($found_type === 'image' && $found_image) {
    $found_style = 'background-image: url(' . esc_url($found_image) . ');';
} elseif ($found_type === 'color' && $found_color) {
    $found_style = 'background-color: ' . esc_attr($found_color) . ';';
}

$none_style = '';
if ($none_type === 'image' && $none_image) {
    $none_style = 'background-image: url(' . esc_url($none_image) . ');';
} elseif ($none_type === 'color' && $none_color) {
    $none_style = 'background-color: ' . esc_attr($none_color) . ';';
}

$no_brokered_style = '';
if ($no_brokered_type === 'image' && $no_brokered_image) {
    $no_brokered_style = 'background-image: url(' . esc_url($no_brokered_image) . ');';
} elseif ($no_brokered_type === 'color' && $no_brokered_color) {
    $no_brokered_style = 'background-color: ' . esc_attr($no_brokered_color) . ';';
}

$wrapper_classes = array('listings-api-intro-block');
if (!empty($block['className'])) {
    $extra = preg_split('/\s+/', $block['className'], -1, PREG_SPLIT_NO_EMPTY);
    foreach ($extra as $c) {
        $wrapper_classes[] = sanitize_html_class($c);
    }
}

$wrapper_attributes = get_block_wrapper_attributes(array(
    'class' => implode(' ', array_unique($wrapper_classes)),
));
?>
<div <?php echo $wrapper_attributes; ?>>
    <?php if ($intro) : ?>
        <div class="listings-api-intro-block__intro">
            <?php echo $intro; ?>
        </div>
    <?php endif; ?>
    <?php if ($found_content) : ?>
    <section class="listings-api-intro-block__cta listings-found-element<?php echo $found_white_text ? ' listings-api-intro-block__cta--white-text' : ''; ?>"<?php echo $found_style ? ' style="' . $found_style . '"' : ''; ?>>
        <div class="listings-api-intro-block__cta-inner">
            <?php echo $found_content; ?>
        </div>
    </section>
    <?php endif; ?>
    <?php if ($none_content) : ?>
    <section class="listings-api-intro-block__cta no-listings-element<?php echo $none_white_text ? ' listings-api-intro-block__cta--white-text' : ''; ?>"<?php echo $none_style ? ' style="' . $none_style . '"' : ''; ?>>
        <div class="listings-api-intro-block__cta-inner">
            <?php echo $none_content; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if ($no_brokered_content) : ?>
    <section class="listings-api-intro-block__cta no-brokered-element<?php echo $no_brokered_white_text ? ' listings-api-intro-block__cta--white-text' : ''; ?>"<?php echo $no_brokered_style ? ' style="' . $no_brokered_style . '"' : ''; ?>>
        <div class="listings-api-intro-block__cta-inner">
            <?php echo $no_brokered_content; ?>
        </div>
    </section>
    <?php endif; ?>
</div>
