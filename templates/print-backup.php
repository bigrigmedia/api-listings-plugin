<section class="unit-api-print-container">
    <?php
    $branding_image_id = get_option('api_listings_branding_image');

    //Get image from ID
    $branding_image = wp_get_attachment_image_url($branding_image_id, 'full');

    $website_url = get_bloginfo('url');
    $website_url = str_replace('http://', '', $website_url);
    $website_url = str_replace('https://', '', $website_url);
    $website_url = str_replace('www.', '', $website_url);

    $gallery_empty_class = '';

    //Check if gallery is an array then check if it's empty
    if (is_array($gallery)) {
        if (empty($gallery)) {
            $gallery_empty_class = 'gallery-empty';
        }
    }
    ?>

    <div class="api-print-container">
        <div class="property-api-inner">
            <div class="print-header">
                <?php if($branding_image): ?>
                    <img class="print-logo" src="<?= $branding_image ?>" alt="<?= get_bloginfo('name') ?>"> 
                <?php endif; ?>
                <strong class="print-website-url"><?= $website_url ?></strong>
            </div>
            <div class="print-gallery">
            <?php if($gallery || $featured_image): ?>
                <?php if ($featured_image): ?>
                <div class="print-gallery__item <?= $gallery_empty_class ?>">
                    <div class="print-gallery__item__image" style="background-image: url('<?= $featured_image ?>');"></div>
                </div>
                <?php endif; ?>
                <?php foreach ($gallery as $gallery_item): ?>
                <div class="print-gallery__item">
                    <div class="print-gallery__item__image" style="background-image: url('<?= $gallery_item['url'] ?>');"></div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="print-gallery__item <?= $gallery_empty_class ?>">
                    <div class="print-gallery__item__image" style="background-image: url('https://www.legacymhc.com/app/themes/sage/assets/images/2026-coming-soon.png');"></div>
                </div>
            <?php endif; ?>
            </div>

            <div class="print-details__container">
                <div class="print-contact-details">
                    <div class="print-contact-info">
                    <strong><?= $sales_name ?></strong>
                    <a class="print-contact-info__phone" href="tel:<?= $phone_link ?>"><?= $phone_link ?></a>
                    <a class="print-contact-info__email" href="mailto:<?= $sales_email ?>"><?= $sales_email ?></a>
                    </div>
                    <div class="print-details">
                    <?php foreach ($print_details as $key => $detail): ?>
                    <div class="print-details__item">
                        <span class="print-details__item__key"><?= $key ?>: </span>
                        <span class="print-details__item__value"><?= $detail ?></span>
                    </div>
                    <?php endforeach; ?>
                    </div>
                </div>

                <div class="print-description">
                    <strong class="print-title"><?= get_bloginfo('name') ?></strong>
                    <h1 class="print-title"><?= $title ?></h1>
                    <div class="print-description__content">
                    <?= $content ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>