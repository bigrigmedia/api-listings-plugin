<?php
/**
 * Plugin Name: Home listings API
 * Plugin URI: https://www.getindio.com/
 * Description: Adds a block for displaying home listings from an API and a page template for displaying listing details.
 * Version: 1.0.0
 * Author: Adrian Figueroa
 * Author URI: https://www.getindio.com
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('BRM_API_LISTINGS_PLUGIN_VERSION', '1.0.0');
define('BRM_API_LISTINGS_PLUGIN_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BRM_API_LISTINGS_PLUGIN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BRM_API_LISTINGS_PLUGIN_PLUGIN_FILE', __FILE__);

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
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('init', array($this, 'register_shortcodes'));

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
    }
    
    /**
     * Settings section callback
     */
    public function settings_section_callback() {
        echo '<p>Configure the plugin settings below.</p>';
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
        }
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts($hook) {
        if ('settings_page_brm-api-listings' !== $hook) {
            return;
        }
        
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
        add_shortcode('api_listings_container', array($this, 'api_shortcode_callback'));
        add_shortcode('api_listings_details', array($this, 'api_listings_details_callback')); // Add this line
    }
    
    /**
     * Shortcode callback
     */
    public function api_shortcode_callback($atts, $content = '') {
        $atts = shortcode_atts(array(
        ), $atts, 'api_listings_container');

        $card_color = get_option('api_listings_card_color', '#26bbe0');
        $button_color = get_option('api_listings_button_color', '#287092');
        $button_text_color = get_option('api_listings_button_text_color', '#ffffff');
        $card_text_white = get_option('api_listings_card_text_white', false) ? 'white-card' : '';
        $section_id = 'api-listings-' . uniqid();
        
        ob_start();
        ?>
        <div id="<?php echo esc_attr($section_id); ?>" class="api-listings">
            <div id="api-listings-container" class="<?php echo esc_attr($card_text_white); ?>">
            </div>
            
            <?php if (!is_front_page()) : ?>
                <div id="pagination-container">
                    <a id="load-more-btn" style="display: none">Load More...</a>
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
    public function api_listings_details_callback($atts, $content = '') {
        $atts = shortcode_atts(array(
            // Define any default attributes here
        ), $atts, 'api_listings_details');

        ob_start();
        include plugin_dir_path(__FILE__) . 'templates/listing-details.php'; // Adjust the path if necessary
        return ob_get_clean();
    }
}

// Initialize the plugin
BrmApiListingsPlugin::get_instance();

/**
 * Helper function to get plugin instance
 */
function my_custom_plugin() {
    return BrmApiListingsPlugin::get_instance();
}