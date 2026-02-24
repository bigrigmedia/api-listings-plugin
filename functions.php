<?php
/**
 * Plugin Name: Legacy Listings API
 * Plugin URI: https://www.getindio.com/
 * Description: Adds shortcodes for displaying home listings from the Legacy listings API.
 * Version: 2.41
 * Author: Adrian Figueroa
 * Author URI: https://www.getindio.com
 */

namespace ListingsAPI;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('BRM_API_LISTINGS_PLUGIN_VERSION', '2.41');
define('BRM_API_LISTINGS_PLUGIN_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BRM_API_LISTINGS_PLUGIN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BRM_API_LISTINGS_PLUGIN_PLUGIN_FILE', __FILE__);


// Include the updater class
// Temporarily disabled due to issues with site performance
//require_once BRM_API_LISTINGS_PLUGIN_PLUGIN_DIR . 'includes/updater.php';

// Include the logger class
require_once BRM_API_LISTINGS_PLUGIN_PLUGIN_DIR . 'includes/logger.php';

/**
 * Main Plugin Class
 */
class BrmApiListingsPlugin {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Plugin activation and deactivation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Initialize plugin
        add_action('plugins_loaded', array($this, 'init'));
        
        // Admin hooks
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_init', array($this, 'admin_init'));
            add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        }
        
        // Frontend hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), 110);
        add_action('init', array($this, 'register_shortcodes'));

        // Dequeue conflicting scripts - Use large number to ensure it runs last
        add_action('wp_enqueue_scripts', array($this, 'dequeue_conflicting_scripts'), 200);

        // Add page templates
        add_filter('theme_page_templates', array($this, 'register_page_templates'));
        add_filter('template_include', array($this, 'add_page_template'));
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Set default options
        add_option('brm_api_listings_plugin_version', BRM_API_LISTINGS_PLUGIN_VERSION);
        
        // Flush rewrite rules if needed
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up if needed
        flush_rewrite_rules();
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Initialize components
        $this->load_dependencies();
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Include additional files - here
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            'API Listings Settings',
            'API Listings',
            'manage_options',
            'brm-api-listings',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Admin page callback
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('api_listings_plugin_settings');
                do_settings_sections('api_listings_plugin_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Initialize admin settings
     */
    public function admin_init() {
        add_settings_section(
            'api_listings_settings_section',
            'Plugin Settings',
            array($this, 'settings_section_callback'),
            'api_listings_plugin_settings'
        );

        //Settings for the property ID
        register_setting('api_listings_plugin_settings', 'api_listings_property_id');
        
        add_settings_field(
            'api_listings_property_id_field',
            'Property ID',
            array($this, 'modular_settings_field_callback'),
            'api_listings_plugin_settings',
            'api_listings_settings_section',
            array(
                'type' => 'text',
                'option_name' => 'api_listings_property_id',
                'default' => ''
            )
        );

        //Settings for the default listing image
        register_setting('api_listings_plugin_settings', 'api_listings_branding_image');

        add_settings_field(
            'api_listings_branding_image_field',
            'Branding Image',
            array($this, 'modular_settings_field_callback'),
            'api_listings_plugin_settings',
            'api_listings_settings_section',
            array(
                'type' => 'image',
                'option_name' => 'api_listings_branding_image',
                'default' => ''
            )
        );

        //Settings for the card color
        register_setting('api_listings_plugin_settings', 'api_listings_card_color');

        add_settings_field(
            'api_listings_card_color_field',
            'Listing Card Color',
            array($this, 'modular_settings_field_callback'),
            'api_listings_plugin_settings',
            'api_listings_settings_section',
            array(
                'type' => 'color',
                'option_name' => 'api_listings_card_color',
                'default' => '#26bbe0'
            )
        );

        //Settings for the button color
        register_setting('api_listings_plugin_settings', 'api_listings_button_color');

        add_settings_field(
            'api_listings_button_color_field',
            'Listing Card Button Color',
            array($this, 'modular_settings_field_callback'),
            'api_listings_plugin_settings',
            'api_listings_settings_section',
            array(
                'type' => 'color',
                'option_name' => 'api_listings_button_color',
                'default' => '#287092'
            )
        );

        //Settings for the button text color
        register_setting('api_listings_plugin_settings', 'api_listings_button_text_color');

        add_settings_field(
            'api_listings_button_text_color_field',
            'Listing Card Button Text Color',
            array($this, 'modular_settings_field_callback'),
            'api_listings_plugin_settings',
            'api_listings_settings_section',
            array(
                'type' => 'color',
                'option_name' => 'api_listings_button_text_color',
                'default' => '#ffffff'
            )
        );

        //Settings for the card text color
        register_setting('api_listings_plugin_settings', 'api_listings_card_text_white');

        add_settings_field(
            'api_listings_card_text_white_field',
            'White Text On Card',
            array($this, 'modular_settings_field_callback'),
            'api_listings_plugin_settings',
            'api_listings_settings_section',
            array(
                'type' => 'checkbox',
                'option_name' => 'api_listings_card_text_white',
                'default' => false
            )
        );


        //Settings for card drop shadow effect
        register_setting('api_listings_plugin_settings', 'api_listings_card_drop_shadow');

        add_settings_field(
            'api_listings_card_drop_shadow_field',
            'Card Drop Shadow Effect',
            array($this, 'modular_settings_field_callback'),
            'api_listings_plugin_settings',
            'api_listings_settings_section',
            array(
                'type' => 'checkbox',
                'option_name' => 'api_listings_card_drop_shadow',
                'default' => false
            )
        );

        //Settings for single listing page contact form background color
        register_setting('api_listings_plugin_settings', 'api_listings_contact_form_color');

        add_settings_field(
            'api_listings_contact_form_color_field',
            'Contact Form Background Color',
            array($this, 'modular_settings_field_callback'),
            'api_listings_plugin_settings',
            'api_listings_settings_section',
            array(
                'type' => 'color',
                'option_name' => 'api_listings_contact_form_color',
                'default' => '#8c9d66'
            )
        );

        //Settings for single listing page contact form button color
        register_setting('api_listings_plugin_settings', 'api_listings_contact_form_button_color');

        add_settings_field(
            'api_listings_contact_form_button_color_field',
            'Contact Form Button Color',
            array($this, 'modular_settings_field_callback'),
            'api_listings_plugin_settings',
            'api_listings_settings_section',
            array(
                'type' => 'color',
                'option_name' => 'api_listings_contact_form_button_color',
                'default' => '#8c9d66'
            )
        );

        //Settings for single listing page contact form button text color
        register_setting('api_listings_plugin_settings', 'api_listings_contact_form_button_text_color');

        add_settings_field(
            'api_listings_contact_form_button_text_color_field',
            'Contact Form Button Text Color',
            array($this, 'modular_settings_field_callback'),
            'api_listings_plugin_settings',
            'api_listings_settings_section',
            array(
                'type' => 'color',
                'option_name' => 'api_listings_contact_form_button_text_color',
                'default' => '#ffffff'
            )
        );


        //Setting for the form text color
        register_setting('api_listings_plugin_settings', 'api_listings_contact_form_text_white');

        add_settings_field(
            'api_listings_contact_form_text_white_field',
            'White Text On Contact Form',
            array($this, 'modular_settings_field_callback'),
            'api_listings_plugin_settings',
            'api_listings_settings_section',
            array(
                'type' => 'checkbox',
                'option_name' => 'api_listings_contact_form_text_white',
                'default' => false
            )
        );

        //Setting for the form action
        register_setting('api_listings_plugin_settings', 'api_listings_contact_form_action');

        add_settings_field(
            'api_listings_contact_form_action_field',
            'Contact Form Action',
            array($this, 'modular_settings_field_callback'),
            'api_listings_plugin_settings',
            'api_listings_settings_section',
            array(
                'type' => 'text',
                'option_name' => 'api_listings_contact_form_action',
                'default' => ''
            )
        );

        //Setting for the contact method field ID
        register_setting('api_listings_plugin_settings', 'api_listings_contact_method_field_id');

        add_settings_field(
            'api_listings_contact_method_field_id_field',
            'Contact Method Field ID',
            array($this, 'modular_settings_field_callback'),
            'api_listings_plugin_settings',
            'api_listings_settings_section',
            array(
                'type' => 'text',
                'option_name' => 'api_listings_contact_method_field_id',
                'default' => ''
            )
        );

        //Setting for the move in date field ID
        register_setting('api_listings_plugin_settings', 'api_listings_move_in_date_field_id');

        add_settings_field(
            'api_listings_move_in_date_field_id_field',
            'Move In Date Field ID',
            array($this, 'modular_settings_field_callback'),
            'api_listings_plugin_settings',
            'api_listings_settings_section',
            array(
                'type' => 'text',
                'option_name' => 'api_listings_move_in_date_field_id',
                'default' => ''
            )
        );

        //Setting for the referral source field ID
        register_setting('api_listings_plugin_settings', 'api_listings_referral_source_field_id');

        add_settings_field(
            'api_listings_referral_source_field_id_field',
            'Referral Source Field ID',
            array($this, 'modular_settings_field_callback'),
            'api_listings_plugin_settings',
            'api_listings_settings_section',
            array(
                'type' => 'text',
                'option_name' => 'api_listings_referral_source_field_id',
                'default' => ''
            )
        );

        //Setting for the message field ID
        register_setting('api_listings_plugin_settings', 'api_listings_message_field_id');

        add_settings_field(
            'api_listings_message_field_id_field',
            'Message Field ID',
            array($this, 'modular_settings_field_callback'),
            'api_listings_plugin_settings',
            'api_listings_settings_section',
            array(
                'type' => 'text',
                'option_name' => 'api_listings_message_field_id',
                'default' => ''
            )
        );

        //Settings for the hidden field ID
        register_setting('api_listings_plugin_settings', 'api_listings_hidden_field_id');

        add_settings_field(
            'api_listings_hidden_field_id_field',
            'Hidden Field ID',
            array($this, 'modular_settings_field_callback'),
            'api_listings_plugin_settings',
            'api_listings_settings_section',
            array(
                'type' => 'text',
                'option_name' => 'api_listings_hidden_field_id',
                'default' => ''
            )
        );

        //Settings for the recaptcha site key
        register_setting('api_listings_plugin_settings', 'api_listings_recaptcha_site_key');

        add_settings_field(
            'api_listings_recaptcha_site_key_field',
            'Recaptcha Site Key',
            array($this, 'modular_settings_field_callback'),
            'api_listings_plugin_settings',
            'api_listings_settings_section',
            array(
                'type' => 'text',
                'option_name' => 'api_listings_recaptcha_site_key',
                'default' => ''
            )
        );

        //Settings for the details page top margin
        register_setting('api_listings_plugin_settings', 'api_listings_details_page_margin');

        add_settings_field(
            'api_listings_details_page_margin_field',
            'Details Page Top Margin',
            array($this, 'modular_settings_field_callback'),
            'api_listings_plugin_settings',
            'api_listings_settings_section',
            array(
                'type' => 'range',
                'option_name' => 'api_listings_details_page_margin',
                'default' => 0,
                'min' => 0,
                'max' => 400,
                'step' => 5,
                'unit' => 'px',
                'description' => 'Set the top margin of the details page'
            )
        );

        //Settings for the details page top margin on mobile
        register_setting('api_listings_plugin_settings', 'api_listings_details_page_margin_mobile');

        add_settings_field(
            'api_listings_details_page_margin_mobile_field',
            'Details Page Top Margin on Mobile',
            array($this, 'modular_settings_field_callback'),
            'api_listings_plugin_settings',
            'api_listings_settings_section',
            array(
                'type' => 'range',
                'option_name' => 'api_listings_details_page_margin_mobile',
                'default' => 0,
                'min' => 0,
                'max' => 400,
                'step' => 5,
                'unit' => 'px',
                'description' => 'Set the top margin of the details page on mobile'
            )
        );
        
    }
    
    /**
     * Settings section callback
     */
    public function settings_section_callback() {
        echo '
        <p>Configure the plugin settings below.</p>
        <p>Use the shortcode <code>[api_listings_cards]</code> to display the listings cards on your page.</p>
        <p>Use the shortcode <code>[api_listing_details]</code> to display the listing details on your page.</p>';
    }
    
    /**
     * Modular settings field callback
     */
    public function modular_settings_field_callback($args) {
        $option = get_option($args['option_name'], $args['default']);
        
        switch ($args['type']) {
            case 'text':
                echo '<input type="text" name="' . esc_attr($args['option_name']) . '" value="' . esc_attr($option) . '" class="regular-text" />';
                break;
            case 'checkbox':
                echo '<input type="checkbox" name="' . esc_attr($args['option_name']) . '" value="1" ' . checked(1, $option, false) . ' />';
                break;
            case 'color':
                echo '<input type="text" name="' . esc_attr($args['option_name']) . '" value="' . esc_attr($option) . '" class="api-plugin-color-picker" data-default-color="' . esc_attr($args['default']) . '" />';
                break;
            case 'range':
                $min = isset($args['min']) ? $args['min'] : 0;
                $max = isset($args['max']) ? $args['max'] : 100;
                $step = isset($args['step']) ? $args['step'] : 1;
                $unit = isset($args['unit']) ? $args['unit'] : '';
                $description = isset($args['description']) ? $args['description'] : '';
                
                echo '<div class="api-plugin-range-field">';
                echo '<input type="range" name="' . esc_attr($args['option_name']) . '" id="' . esc_attr($args['option_name']) . '" value="' . esc_attr($option) . '" min="' . esc_attr($min) . '" max="' . esc_attr($max) . '" step="' . esc_attr($step) . '" class="api-plugin-range-slider" />';
                echo '<div class="range-display">';
                echo '<span class="range-value">' . esc_html($option) . '</span>';
                if ($unit) {
                    echo '<span class="range-unit">' . esc_html($unit) . '</span>';
                }
                echo '</div>';
                if ($description) {
                    echo '<p class="description">' . esc_html($description) . '</p>';
                }
                echo '</div>';
                break;
            case 'image':
                $image_id = $option;
                $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'medium') : '';
                echo '<div class="api-plugin-image-field">';
                echo '<input type="hidden" name="' . esc_attr($args['option_name']) . '" id="' . esc_attr($args['option_name']) . '" value="' . esc_attr($image_id) . '" />';
                echo '<div class="image-preview" style="margin-bottom: 10px;">';
                if ($image_url) {
                    echo '<img src="' . esc_url($image_url) . '" style="max-width: 200px; max-height: 200px; display: block;" />';
                }
                echo '</div>';
                echo '<button type="button" class="button api-plugin-upload-image" data-field="' . esc_attr($args['option_name']) . '">' . (__('Select Image', 'textdomain')) . '</button>';
                if ($image_id) {
                    echo ' <button type="button" class="button api-plugin-remove-image" data-field="' . esc_attr($args['option_name']) . '">' . (__('Remove Image', 'textdomain')) . '</button>';
                }
                echo '</div>';
                break;
        }
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts($hook) {
        if ('settings_page_brm-api-listings' !== $hook) {
            return;
        }
        
        // Enqueue WordPress media uploader
        wp_enqueue_media();
        
        wp_enqueue_style(
            'brm-api-listings-plugin-admin',
            BRM_API_LISTINGS_PLUGIN_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            BRM_API_LISTINGS_PLUGIN_VERSION
        );
        
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script(
            'brm-api-listings-plugin-admin',
            BRM_API_LISTINGS_PLUGIN_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-color-picker'),
            BRM_API_LISTINGS_PLUGIN_VERSION,
            true
        );
        
        // Localize script for AJAX
        wp_localize_script('brm-api-listings-plugin-admin', 'brm_api_listings_plugin_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('brm_api_listings_plugin_nonce')
        ));
    }

    /**
     * Dequeue conflicting scripts
     */
    public function dequeue_conflicting_scripts() {
        //Dequeue the Sage Unit API scripts to prevent conflicts
        wp_dequeue_script('sage/unit-api.js');
        wp_dequeue_script('sage/unit-api-owner.js');
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_style(
            'brm-api-listings-plugin-frontend',
            BRM_API_LISTINGS_PLUGIN_PLUGIN_URL . 'dist/index.scss.css',
            array(),
            BRM_API_LISTINGS_PLUGIN_VERSION
        );

        wp_enqueue_style(
            'brm-api-listings-plugin-print',
            BRM_API_LISTINGS_PLUGIN_PLUGIN_URL . 'assets/css/print.css',
            array(),
            BRM_API_LISTINGS_PLUGIN_VERSION
        );

        //Enqueue Slick slider from CDN
        wp_enqueue_style(
            'slick-slider',
            'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css',
            array(),
            '1.8.1'
        );

        wp_enqueue_script(
            'slick-slider',
            'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js',
            array('jquery'),
            '1.8.1',
            true
        );

        //Enqueue fancybox from CDN
        wp_enqueue_style(
            'fancybox',
            'https://cdn.jsdelivr.net/npm/@fancyapps/ui@6.0/dist/fancybox/fancybox.css',
            array(),
            '6.0'
        );

        wp_enqueue_script(
            'fancybox',
            'https://cdn.jsdelivr.net/npm/@fancyapps/ui@6.0/dist/fancybox/fancybox.umd.js',
            array('jquery'),
            '6.0',
            true
        );
        
        wp_enqueue_script(
            'brm-api-listings-plugin-frontend',
            BRM_API_LISTINGS_PLUGIN_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery', 'slick-slider'),
            BRM_API_LISTINGS_PLUGIN_VERSION,
            true
        );

        wp_enqueue_script(
            'brm-api-listings-plugin-shortcode',
            BRM_API_LISTINGS_PLUGIN_PLUGIN_URL . 'assets/js/shortcode.js',
            array('jquery'),
            BRM_API_LISTINGS_PLUGIN_VERSION,
            true
        );

        wp_localize_script('brm-api-listings-plugin-shortcode', 'api_listings_plugin_settings', array(
            'property_id' => get_option('api_listings_property_id', '')
        ));
    }

    /**
     * Register plugin page templates
     */
    public function register_page_templates($templates) {
        $templates['listings-api/templates/listing-details.php'] = 'Listing Details';
        return $templates;
    }

    /**
     * Add page template
     */
    public function add_page_template($template) {
        if (is_page()) {
            $template_slug = get_page_template_slug();
            if ($template_slug === 'listings-api/templates/listing-details.php') {
                $custom = plugin_dir_path(__FILE__) . 'templates/listing-details.php';
                if (file_exists($custom)) {
                    return $custom;
                }
            }
        }
        return $template;
    }
    
    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('api_listings_cards', array($this, 'api_listings_cards_callback'));
        add_shortcode('api_listing_details', array($this, 'api_listing_details_callback'));
    }
    
    /**
     * Shortcode callback
     */
    public function api_listings_cards_callback($atts, $content = '') {
        $atts = shortcode_atts(array(
            'new-only' => 'false',
            'featured-homes' => 'false',
            'brokered-only' => 'false',
            'active-only' => 'false',
            'white-notice-text' => 'false',
            'white-card-text' => 'false',
            'hide-filters' => 'true',
            'hide-filters-classic' => 'false',
            'slider' => 'false',
            'sos-number' => '',
            //Optional redirect URL if there are no listings found
            'redirect-url' => ''
        ), $atts, 'api_listings_cards');

        $card_color = get_option('api_listings_card_color', '#26bbe0');
        $button_color = get_option('api_listings_button_color', '#287092');
        $button_text_color = get_option('api_listings_button_text_color', '#ffffff');
        $card_text_white = get_option('api_listings_card_text_white', false) || $atts['white-card-text'] === 'true' ? 'white-card' : '';
        $card_drop_shadow = get_option('api_listings_card_drop_shadow', false) ? 'card-drop-shadow' : '';
        $is_slider = $atts['slider'] === 'true' ? 'is-slider' : 'not-slider';
        $section_id = 'api-listings-' . uniqid();
        $rounded_corners = $card_color ? '' : 'rounded-corners';
        
        //The inner content of the shortcode is what is displayed if there are no listings found
        //Its container is set to display: none by default
        //If there are no listings found its container is set to display: block by shortcode.js
        $content = do_shortcode( $content ) ? do_shortcode( $content ) : '<p>No listings found.</p>';
        ob_start();
        ?>
        <div id="<?php echo esc_attr($section_id); ?>" class="plugin-api-listings">
            <?php if (!is_front_page() && $atts['hide-filters'] === 'false') : ?>
            <form class="sort-form">
                <div style="display: none;">
                    <select id="listing-sos-number" name="sos_number">
                        <option value="">Filter by Home Type</option>
                        <option value="Community Owned - New">New Construction</option>
                        <option value="used">Previously Owned</option>
                    </select>
                </div>
                <div class="bedroom-filter-container">
                    <select id="listing-bedrooms" name="bedrooms">
                        <option value="">Beds</option>
                        <option value="1">1 Bedroom</option>
                        <option value="2">2 Bedrooms</option>
                        <option value="3">3 Bedrooms</option>
                        <option value="4">4 Bedrooms</option>
                    </select>
                </div>
                <div class="bathroom-filter-container">
                    <select id="listing-bathrooms" name="bathrooms">
                        <option value="">Baths</option>
                        <option value="1">1 Bathroom</option>
                        <option value="2">2 Bathrooms</option>
                        <option value="3">3 Bathrooms</option>
                    </select>
                </div>
                <div style="display: none;">
                    <input type="number" id="listing-min-price" name="min_price" placeholder="Min Price">
                    <input type="number" id="listing-max-price" name="max_price" placeholder="Max Price">
                </div>
                <div style="display: none;">
                    <label for="listing-sort-order">Sort by Date:</label>
                    <select id="listing-sort-order" name="sortOrder" style="padding: 0;">
                        <option value="newest">Descending</option>
                        <option value="oldest">Ascending</option>
                    </select>
                </div>
            </form>
            
            <div class="listing-filter-container">
                <div class="listing-filter-pills">
                    <div class="listing-result-count">
                    </div>
                    <div class="listing-filter-pill bedrooms" style="display: none;">
                        <span></span>
                        <img src="https://www.legacymhc.com/app/themes/sage/assets/images/clear-filter.svg">
                    </div>
                    <div class="listing-filter-pill bathrooms" style="display: none;">
                        <span></span>
                        <img src="https://www.legacymhc.com/app/themes/sage/assets/images/clear-filter.svg">
                    </div>
                    <div class="listing-filter-pill min-price" style="display: none;">
                        <span></span>
                        <img src="https://www.legacymhc.com/app/themes/sage/assets/images/clear-filter.svg">
                    </div>
                    <div class="listing-filter-pill max-price" style="display: none;">
                        <span></span>
                        <img src="https://www.legacymhc.com/app/themes/sage/assets/images/clear-filter.svg">
                    </div>
                </div>
                <div class="listing-filter-clear-all">
                    CLEAR FILTERS
                    <img src="https://www.legacymhc.com/app/themes/sage/assets/images/clear-filters.svg">
                </div>
            </div>
            <?php endif; ?>


            <?php if (!is_front_page() && $atts['hide-filters-classic'] === 'false') : ?>
            <form class="sort-form-classic">
                <div>
                    <select id="listing-sos-number" name="sos_number">
                        <option value="">Filter by Home Type</option>
                        <option value="Community Owned - New">New Construction</option>
                        <option value="used">Previously Owned</option>
                    </select>
                </div>
            </form>
            <?php endif; ?>

            <div
            id="plugin-api-listings-container"
            <?php if (is_front_page()) { echo 'data-home="true"'; } ?>
            <?php if ($atts['new-only'] === 'true') { echo 'data-new-only="true"'; } ?>
            <?php if ($atts['active-only'] === 'true') { echo 'data-active-only="true"'; } ?>
            <?php if ($atts['white-notice-text'] === 'true') { echo 'data-white-notice-text="true"'; } ?>
            <?php if ($atts['featured-homes'] === 'true') { echo 'data-featured-homes="true"'; } ?>
            <?php if ($atts['brokered-only'] === 'true') { echo 'data-brokered-only="true"'; } ?>
            <?php if ($atts['slider'] === 'true') { echo 'data-slider="true"'; } ?>
            <?php if ($atts['redirect-url'] !== '') { echo 'data-redirect-url="' . esc_attr($atts['redirect-url']) . '"'; } ?>
            <?php echo 'data-sos-number="' . esc_attr($atts['sos-number']) . '"'; ?>

            class="
            <?php echo esc_attr($card_text_white); ?>
            <?php echo esc_attr($card_drop_shadow); ?>
            <?php echo esc_attr($rounded_corners); ?>
            <?php echo esc_attr($is_slider); ?>"
            >
               
            </div>

            <div id="no-listings-found-container" style="display: none;">
                <div class="no-listings-found" 
                style="<?php echo $atts['white-notice-text'] === 'true' ? 'color: white;' : ''; ?>"
                >
                    <?php echo $content; ?>
                </div>
            </div>

            <div id="api-listings-loading-spinner" style="display: none;">Loading Home Listings...</div>
            
            <?php if (!is_front_page() && $atts['slider'] !== 'true') : ?>
                <div id="listings-pagination-container">
                    <a id="load-listings-btn" class="button-api-listing" style="display: none">Load More...</a>
                </div>
            <?php endif; ?>
        </div>

        <style>
            #<?php echo esc_attr($section_id); ?> {
                --card-color: <?php echo esc_attr($card_color); ?>;
                --button-color: <?php echo esc_attr($button_color); ?>;
                --button-text-color: <?php echo esc_attr($button_text_color); ?>;
            }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode callback for custom template
     */
    public function api_listing_details_callback($atts, $content = '') {
        $atts = shortcode_atts(array(
            // Define any default attributes here
        ), $atts, 'api_listing_details');

        ob_start();
        include plugin_dir_path(__FILE__) . 'templates/listing-details.php'; // Adjust the path if necessary
        return ob_get_clean();
    }
}

// Initialize the plugin
BrmApiListingsPlugin::get_instance();

// Initialize the updater
//Updater::get_instance();

/**
 * Helper function to get plugin instance
 */
function get_api_listings_plugin() {
    return BrmApiListingsPlugin::get_instance();
}