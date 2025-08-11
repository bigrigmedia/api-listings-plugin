<?php
/**
 * Template for displaying listing details
 */

$section_id = 'single-api-listings-' . uniqid();
?>


<section id="<?php echo esc_attr($section_id); ?>" class="api-plugin-single-listing">
    <div class="single-listing-container">
        <?php
        //Check if GET paramter called 'id' is set
        $id = 0;

        if (isset($_GET['id'])) {
            $id = $_GET['id'];
        }
        ?>

        <?php if ($id == 0) { ?>

            <div>
            <h1 class="text-center">No Property Selected</h1>
            </div>

        <?php } ?>

        <?php

        function makeAPICall($url)
        {
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            $resp = curl_exec($curl);
            curl_close($curl);
            $data = json_decode($resp, true);

            return $data;
        }

        $homedata = makeAPICall("https://www.legacymhc.com/wp-json/wp/v2/properties/" . $id . "?_embed");

        $acf_fields = $homedata['acf'] ?? '';
        $title = $homedata['title']['rendered'] ?? '';
        $content = $homedata['content']['rendered'] ?? '';

        if ($acf_fields['agents_info'] ?? '') {
            $agents_info = $acf_fields['agents_info'][0];
            $sales_name = $agents_info['names'];
            $sales_email = $agents_info['emails'];
            $sales_phone = $agents_info['phones'];
            $phone_link = preg_replace('/[^0-9]/', '', $sales_phone);
        }

        $purchase_type_map = array(
            '100' => 'Active',
            '240' => 'In Contract',
            '500' => 'Closed',
            '400' => 'Rented Lease',
            '610' => 'Off Market',
            '600' => 'Withdrawn',
        );

        $number = $acf_fields['listing_number'] ?? '';
        $reduced = $acf_fields['reduced'] ?? '';
        $address = $acf_fields['address_home'] ?? '';
        $street_address = $acf_fields['address_home'] ?? '';
        $city_name = $acf_fields['city_home'] ?? '';
        $state_name = $acf_fields['state_home'] ?? '';
        $zipcode = $acf_fields['zipcode_home'] ?? '';
        $price = $acf_fields['price_home'];
        $price = preg_replace('/[^0-9.]/', '', $price);
        $price = number_format(floatval($price), 0);
        $phone_property = $acf_fields['property_phone'] ?? '';
        $bedrooms = $acf_fields['bedrooms_home'] ?? '';
        $bathrooms = $acf_fields['bathrooms_home'] ?? '';
        $gallery = $acf_fields['gallery_homes'] ?? '';
        $video_tour = $acf_fields['video_tour'] ?? '';
        $property_n = $acf_fields['lot_number'];
        $listdate = $acf_fields['listdate'] ?? '';
        $make = $acf_fields['make_home'] ?? '';
        $community = $acf_fields['property_community_type'] ?? '';
        $purchase = $acf_fields['property_purchase_type'] ?? '';
        $purchase = $purchase_type_map[$purchase] ?? '';
        $year_built = $acf_fields['year_make'] ?? '';
        $square_feet = $acf_fields['property_square_footage'];
        $width = $acf_fields['property_width'] ?? '';
        $length = $acf_fields['property_length'] ?? '';
        $vin = $acf_fields['vin_number'] ?? '';

        if ($square_feet) {
            $width = $square_feet / 2;
            $length = $square_feet / 2;
        }


        $sos = $acf_fields['sos_number'] ?? '';
        if ($sos === 'Community Owned - New') {
            $sos = 'CO-N';
        } elseif ($sos === 'Community Owned - Used') {
            $sos = 'CO-U';
        } elseif ($sos === 'Brokered') {
            $sos = 'BRK';
        }

        $details = [
            'Lot Number' => $property_n,
            'Listing Number'    => $property_n . '/' . $listdate,
            'Address'   => $address . ', ' . $city_name . ', ' . $state_name . ' ' . $zipcode,
            'Price'     => '$' . $price,
            'Make'    => $make,
            'Year Built'   => $year_built,
            'Bedrooms'  => $bedrooms,
            'Bathrooms' => $bathrooms,
            'Listing Type' => $sos,
            'Community'   => $community,
            'Purchase Type'   => $purchase,
            'Square Feet'   => $square_feet,
            'Width'   => $width,
            'Length'   => $length,
            'Phone'     => $phone_property,
            'VIN'       => $vin,
        ];

        $print_details = [
            'Lot Number' => $property_n,
            'Listing Number'    => $property_n . '/' . $listdate,
            'Address'   => $address . ', ' . $city_name . ', ' . $state_name . ' ' . $zipcode,
            'Price'     => '$' . $price,
            'Year Built'   => $year_built,
            'Bedrooms'  => $bedrooms,
            'Bathrooms' => $bathrooms,
            'Square Feet'   => $square_feet,
            'Listing Type' => $sos
        ];

        $form_settings = [
            'form_action' => get_option('api_listings_contact_form_action', ''),
            'contact_method_field_id' => get_option('api_listings_contact_method_field_id', ''),
            'move_in_date_field_id' => get_option('api_listings_move_in_date_field_id', ''),
            'referral_source_field_id' => get_option('api_listings_referral_source_field_id', ''),
            'message_field_id' => get_option('api_listings_message_field_id', ''),
            'hidden_field_id' => get_option('api_listings_hidden_field_id', ''),
            'recaptcha_site_key' => get_option('api_listings_recaptcha_site_key', '6LfwgyAUAAAAADiQCNEsyGu26Wi1yqJ8zyzUli8W'),
        ];

        // Meta
        $tagline = $acf_fields['property_tagline'] ?? '';
        $community = $acf_fields['property_community_type'] ?? '';
        $purchase = $acf_fields['property_purchase_type'] ?? '';
        $square_feet = $acf_fields['property_square_feet'] ?? '';
        $property_brochure = $acf_fields['brochure_field'] ?? '';

        $property_plan = $acf_fields['floor_plan'] ?? '';

        $contact_white = get_option('api_listings_contact_form_text_white', false) ? 'white-contact-form' : '';
        ?>

        <div class="property">
            <h1 class="api-property-title"><?= $title ?></h1>
            <?php if($property_n): ?>
            <p class="api-property-listing-number">Listing #: <?= $property_n ?>/<?= $listdate ?></p>
            <?php endif; ?>
            <div class="api-property-meta">
                <?php if($reduced === 'yes'): ?>
                    <span class="ttu">Just Reduced!</span>
                <?php endif; ?>
                <?php if($price): ?>
                    <span>&#36;<?= $price ?></span>
                <?php endif; ?>
                <?php if($bedrooms): ?>
                    <span>BR: <?= $bedrooms ?></span>
                <?php endif; ?>
                <?php if($bathrooms): ?>
                    <span>BA: <?= $bathrooms ?></span>
                <?php endif; ?>
            </div>
            <div class="api-property-content">
                <div class="api-property-gallery">
                    <?php if ($gallery): ?>
                    <div class="api-property-gallery__large js-carousel-gallery">
                        <?php foreach ($gallery as $gallery_item): ?>
                        <div class="api-property-gallery__item px-2">
                            <div class="gallery-bg-img" style="background-image: url(<?= $gallery_item['url'] ?>);"></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="api-property-gallery__nav js-carousel-nav">
                        <?php foreach ($gallery as $gallery_item): ?>
                        <div class="api-property-gallery__item px-2">
                            <div class="gallery-bg-img" style="background-image: url(<?= $gallery_item['url'] ?>);"></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="api-property-gallery__large js-carousel-gallery">
                        <div class="api-property-gallery__item px-2">
                            <div class="gallery-bg-img" style="background-image: url('https://www.legacymhc.com/app/uploads/2023/12/placeholder.png');">
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <!---   details  --->
                    <div class="api-property-description">
                        <?php if ($reduced === 'yes'): ?>
                            <div class="property__reduced">
                            <h2>Just Reduced!</h2>
                            </div>
                        <?php endif; ?>
                        <div class="api-property-description-container">
                            <?php if ($property_plan || $property_brochure): ?>
                            <div class="">
                                <div class="property-documents">
                                <h5>Available Property Documents:</h5>
                                <?php if ($property_plan): ?>
                                    <p><a href="<?= $property_plan ?>" class="btn btn-o" target="_blank">View Floor Plan</a></p>
                                <?php endif; ?>
                                <?php if ($property_brochure): ?>
                                    <p><a class="btn btn-o" href="<?= $property_brochure ?>" target="_blank">View Brochure</a></p>
                                <?php endif; ?>
                                <hr>
                                </div><!-- End of property-documents -->
                                <!-- Possible Ad Placement  -->
                            </div>
                            <?php endif; ?>

                            <?php if ($details): ?>
                            <h5 class="">Property Details</h5>
                            <ul class="api-property-details">
                                <?php foreach ($details as $key => $detail): ?>
                                    <?php if ($detail): ?>
                                        <li class="property__detail <?= str_replace( " ", "_", $key ) ?>">
                                        <?php if ($key !== 'number'): ?>
                                            <span class="ttu"><strong><?= $key; ?>:</strong></span>
                                        <?php else: ?>
                                            <span class="ttu"><strong>Listing <?= $key; ?>:</strong></span>
                                        <?php endif; ?>
                                        <span>
                                            <?php if ($key !== 'phone'): ?>
                                            <?= $detail ?>
                                            <?php else: ?>
                                            <a href="tel:<?= preg_replace('/[^0-9]/', '', $detail); ?>"><?= $detail; ?></a>
                                            <?php endif; ?>
                                        </span>
                                        </li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                            <div class="api-property-description--inner">
                            <?php if ($content): ?>
                                <?php
                                    $content = strip_tags($content);
                                ?>

                                <p><?= $content ?></p>
                            <?php endif; ?>
                            <button class="button-api-listing" onclick="window.print()">Price Sheet</button>

                            <?php if ($video_tour): ?>
                            <hr class=" mt-0 mb-15">
                            <div class="property__video ">
                                <h5>Video Tour</h5>
                                <p>Watch the video below for a full walkthrough of the property, showcasing its layout, features, and flow.</p>
                                <video class="w-full h-auto" src="<?= $video_tour ?>" controls></video>
                            </div>
                            <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="api-property-contact <?= $contact_white ?>">
                    <p class="contact-intro">Contact Our Sales Office</p>
                    <p class="agent-info">
                        <strong class="agent-name"><?= $sales_name ?></strong><br>
                        <a class="agent-phone" href="tel:<?= $phone_link ?>"><?= $sales_phone ?></a><br>
                        <a class="agent-email" href="mailto:<?= $sales_email ?>"><?= $sales_email ?></a>
                    </p>
                    <script type="text/javascript">
                        let form_settings = <?= json_encode($form_settings) ?>;
                        
                        function checkForm(form) {
                            if (form.first.value == '') {
                                alert('Please complete required fields');
                                return false;
                            }
                            if (form.last.value == '') {
                                alert('Please complete required fields');
                                return false;
                            }
                            if (form.phone.value == '') {
                                alert('Please complete required fields');
                                return false;
                            }
                            var emailRegex = /^.+@.+\..{2,6}$/;
                            if (form.email) {
                                if (form.email.value && !emailRegex.test(form.email.value)) {
                                    alert('Please enter a valid email address');
                                    form.email.focus();
                                    return false;
                                }
                            }
                            if (form.email.value == '') {
                                alert('Please complete required fields');
                                return false;
                            }
                            if (form[form_settings['contact_method_field_id']].value == '') {
                                alert('Please complete required fields');
                                return false;
                            }
                            if (form[form_settings['move_in_date_field_id']].value == '') {
                                alert('Please complete required fields');
                                return false;
                            }
                            if (form[form_settings['referral_source_field_id']].value == '') {
                                alert('Please complete required fields');
                                return false;
                            }
                    
                            return true;
                        }
                    </script>
                    <form name="openleads" method="post" action="<?= $form_settings['form_action'] ?>" onsubmit="return checkForm(this);">
                        <div class="api-property-form-field">
                            <label for="first">First Name *</label>
                            <input type="text" size="40" maxlength="200" id="first" name="first" value="" required="">
                        </div>

                        <div class="api-property-form-field">
                            <label for="last">Last Name *</label>
                            <input type="text" size="40" maxlength="200" id="last" name="last" value="" required="">
                        </div>
                        <div class="api-property-form-field">
                            <label for="phone">Phone *</label>
                            <input type="text" size="40" maxlength="200" id="phone" name="phone" value="" required="">
                        </div>
                        <div class="api-property-form-field">
                            <label for="email">Email</label>
                            <input type="text" size="40" maxlength="200" id="email" name="email" value="">
                        </div>

                        <div class="api-property-form-field">
                            <label class="form-check-label" for="<?= $form_settings['contact_method_field_id'] ?>_0">Preferred Contact Method <span class="required">*</span></label>

                            <div class="form-check">
                                <label>Phone Call</label>
                                <input class="form-check-input" type="checkbox" name="<?= $form_settings['contact_method_field_id'] ?>[]" id="<?= $form_settings['contact_method_field_id'] ?>_0" value="Phone Call" >
                            </div>
                            <div class="form-check">
                                <label>Email</label>
                                <input class="form-check-input" type="checkbox" name="<?= $form_settings['contact_method_field_id'] ?>[]" id="<?= $form_settings['contact_method_field_id'] ?>_1" value="Email" >
                            </div>
                            <div class="form-check">
                                <label>Text Message</label>
                                <input class="form-check-input" type="checkbox" name="<?= $form_settings['contact_method_field_id'] ?>[]" id="<?= $form_settings['contact_method_field_id'] ?>_2" value="Text Message" >
                            </div>
                        </div>

                        <div class="api-property-form-field">
                            <label for="<?= $form_settings['move_in_date_field_id'] ?>">How soon are you looking to move? <span class="required">*</span></label>
                            <select id="<?= $form_settings['move_in_date_field_id'] ?>" name="<?= $form_settings['move_in_date_field_id'] ?>">
                                <option value=""></option>
                                <option value="1-3 Months">1-3 Months</option>
                                <option value="3-6 Months">3-6 Months</option>
                                <option value="6-9 Months">6-9 Months</option>
                                <option value="9-12 Months">9-12 Months</option>
                                <option value="Unknown">Unknown</option>
                            </select>
                        </div>

                        <div class="api-property-form-field">
                            <label for="<?= $form_settings['referral_source_field_id'] ?>">How did you hear about us? <span class="required">*</span></label>
                            <select id="<?= $form_settings['referral_source_field_id'] ?>" name="<?= $form_settings['referral_source_field_id'] ?>">
                                <option value=""></option>
                                <option value="Google">Google</option>
                                <option value="MH Village">MH Village</option>
                                <option value="Retirenet.com">Retirenet.com</option>
                                <option value="Zillow">Zillow</option>
                                <option value="Social Media">Social Media</option>
                                <option value="Drive By">Drive By</option>
                                <option value="TV">TV</option>
                                <option value="Realtor">Realtor</option>
                                <option value="Newspaper/Magazine">Newspaper/Magazine</option>
                                <option value="Resident Referral">Resident Referral</option>
                                <option value="Event">Event</option>
                                <option value="MLS">MLS</option>
                                <option value="Radio">Radio</option>
                                <option value="RV'r">RV'r</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="api-property-form-field">
                            <label for="<?= $form_settings['message_field_id'] ?>">Message</label>
                            <textarea id="<?= $form_settings['message_field_id'] ?>" name="<?= $form_settings['message_field_id'] ?>" cols="50" rows="10" style="height: 200px;"></textarea>
                        </div>
                        <input type="hidden" size="20" id="<?= $form_settings['hidden_field_id'] ?>" name="<?= $form_settings['hidden_field_id'] ?>" value="<?= $property_n . '/' . $listdate ?>">
                        <script src='https://www.google.com/recaptcha/api.js'></script>
                        <div class="g-recaptcha" data-sitekey="<?= $form_settings['recaptcha_site_key'] ?>"></div>
                        <input type="submit" class="button-api-listing" name="send" value="Send" id="check">
                        <style>
                            .x-oh{
                                /* IE 8 */
                                -ms-filter: "progid:DXImageTransform.Microsoft.Alpha(Opacity=0)";

                                /* IE 5-7 */
                                filter: alpha(opacity=0);

                                /* Netscape */
                                -moz-opacity: 0;

                                /* Safari 1.x */
                                -khtml-opacity: 0;

                                /* Good browsers */
                                opacity: 0;

                                position: absolute;
                                top: 0;
                                left: 0;
                                height: 0;
                                width: 0;
                                z-index: -1;
                            }
                        </style>
                        <label class="x-oh" for="name"></label>
                        <input class="x-oh" autocomplete="off" type="text" id="xo-name" name="xoname" placeholder="Your name here">
                        <label class="x-oh" for="email"></label>
                        <input class="x-oh" autocomplete="off" type="email" id="xo-email" name="xoemail" placeholder="Your e-mail here">
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>



<section class="unit-api-print-container">
    <?php
    $branding_image_id = get_option('api_listings_branding_image');

    //Get image from ID
    $branding_image = wp_get_attachment_image_url($branding_image_id, 'full');

    $website_url = get_bloginfo('url');
    $website_url = str_replace('http://', '', $website_url);
    $website_url = str_replace('https://', '', $website_url);
    $website_url = str_replace('www.', '', $website_url);

    $featured_image = '';
    if (isset($homedata['_embedded']['wp:featuredmedia'][0]['source_url'])) {
        $featured_image = $homedata['_embedded']['wp:featuredmedia'][0]['source_url'];
    }
    ?>

    <div class="container">
        <div class="property">
            <div class="print-header">
                <?php if($branding_image): ?>
                    <img class="print-logo" src="<?= $branding_image ?>" alt="<?= get_bloginfo('name') ?>"> 
                <?php endif; ?>
                <strong class="print-website-url"><?= $website_url ?></strong>
            </div>
            <div class="print-gallery">
            <?php if($gallery): ?>
                <?php if ($featured_image): ?>
                <div class="print-gallery__item">
                    <div class="print-gallery__item__image" style="background-image: url('<?= $featured_image ?>');"></div>
                </div>
                <?php endif; ?>
                <?php foreach ($gallery as $gallery_item): ?>
                <div class="print-gallery__item">
                    <div class="print-gallery__item__image" style="background-image: url('<?= $gallery_item['url'] ?>');"></div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="print-gallery__item">
                <div class="print-gallery__item__image" style="background-image: url('https://www.legacymhc.com/app/uploads/2025/01/Photo-placeholder-300x251.png');"></div>
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

<?php
    $contact_form_color = get_option('api_listings_contact_form_color', '#26bbe0');
    $contact_form_button_color = get_option('api_listings_contact_form_button_color', '#287092');
    $contact_form_button_text_color = get_option('api_listings_contact_form_button_text_color', '#ffffff');
?>

<style>
    #<?php echo esc_attr($section_id); ?> {
        --contact-form-color: <?php echo esc_attr($contact_form_color); ?>;
        --contact-form-button-color: <?php echo esc_attr($contact_form_button_color); ?>;
        --contact-form-button-text-color: <?php echo esc_attr($contact_form_button_text_color); ?>;
    }

    .api-plugin-single-listing {
        margin-top: 45px;
    }
</style>