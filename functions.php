<?php
/**
 * Plugin Name: Home listings API
 * Plugin URI: https://yourwebsite.com/my-custom-plugin
 * Description: Adds a block for displaying home listings from an API and a page template for displaying listing details.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: my-custom-plugin
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MY_CUSTOM_PLUGIN_VERSION', '1.0.0');
define('MY_CUSTOM_PLUGIN_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MY_CUSTOM_PLUGIN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MY_CUSTOM_PLUGIN_PLUGIN_FILE', __FILE__);

// Include the logger class
require_once MY_CUSTOM_PLUGIN_PLUGIN_DIR . 'includes/logger.php';

/**
 * Main Plugin Class
 */
class MyCustomPlugin {
    
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

        //Register block
        add_action('init', array($this, 'create_block_api_listings_block_init'));
        
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
        add_option('my_custom_plugin_version', MY_CUSTOM_PLUGIN_VERSION);
        
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
        // Load text domain for translations
        load_plugin_textdomain('my-custom-plugin', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Initialize components
        $this->load_dependencies();
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Include additional files
        require_once MY_CUSTOM_PLUGIN_PLUGIN_DIR . 'includes/class-helper.php';
        require_once MY_CUSTOM_PLUGIN_PLUGIN_DIR . 'includes/class-ajax.php';
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('API Listings Settings', 'my-custom-plugin'),
            __('API Listings', 'my-custom-plugin'),
            'manage_options',
            'my-custom-plugin',
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
            'my_custom_plugin_section',
            __('Plugin Settings', 'my-custom-plugin'),
            array($this, 'settings_section_callback'),
            'api_listings_plugin_settings'
        );

        register_setting('api_listings_plugin_settings', 'api_listings_property_id');
        
        add_settings_field(
            'api_listings_property_id_field',
            __('Property ID', 'my-custom-plugin'),
            array($this, 'settings_field_callback'),
            'api_listings_plugin_settings',
            'my_custom_plugin_section'
        );

        // Settings for the card color
        register_setting('api_listings_plugin_settings', 'api_listings_card_color');

        add_settings_field(
            'api_listings_card_color_field',
            __('Listing Card Color', 'my-custom-plugin'),
            array($this, 'card_color_picker_field_callback'),
            'api_listings_plugin_settings',
            'my_custom_plugin_section'
        );

        //Settings for the button color
        register_setting('api_listings_plugin_settings', 'api_listings_button_color');

        add_settings_field(
            'api_listings_button_color_field',
            __('Listing Card Button Color', 'my-custom-plugin'),
            array($this, 'button_color_picker_field_callback'),
            'api_listings_plugin_settings',
            'my_custom_plugin_section'
        );

        //Settings for the button text color
        register_setting('api_listings_plugin_settings', 'api_listings_button_text_color');

        add_settings_field(
            'api_listings_button_text_color_field',
            __('Listing Card Button Text Color', 'my-custom-plugin'),
            array($this, 'button_text_color_picker_field_callback'),
            'api_listings_plugin_settings',
            'my_custom_plugin_section'
        );

        // Settings for the card text color
        register_setting('api_listings_plugin_settings', 'api_listings_card_text_white');

        add_settings_field(
            'api_listings_card_text_white_field',
            __('White Text On Card', 'my-custom-plugin'),
            array($this, 'card_text_white_field_callback'),
            'api_listings_plugin_settings',
            'my_custom_plugin_section'
        );


        //Settings for single listing page contact form background color
        register_setting('api_listings_plugin_settings', 'api_listings_contact_form_color');

        add_settings_field(
            'api_listings_contact_form_color_field',
            __('Contact Form Background Color', 'my-custom-plugin'),
            array($this, 'contact_form_background_color_picker_field_callback'),
            'api_listings_plugin_settings',
            'my_custom_plugin_section'
        );

        //Settings for single listing page contact form button color
        register_setting('api_listings_plugin_settings', 'api_listings_contact_form_button_color');

        add_settings_field(
            'api_listings_contact_form_button_color_field',
            __('Contact Form Button Color', 'my-custom-plugin'),
            array($this, 'contact_form_button_color_picker_field_callback'),
            'api_listings_plugin_settings',
            'my_custom_plugin_section'
        );

        //Settings for single listing page contact form button text color
        register_setting('api_listings_plugin_settings', 'api_listings_contact_form_button_text_color');

        add_settings_field(
            'api_listings_contact_form_button_text_color_field',
            __('Contact Form Button Text Color', 'my-custom-plugin'),
            array($this, 'contact_form_button_text_color_picker_field_callback'),
            'api_listings_plugin_settings',
            'my_custom_plugin_section'
        );


        //Setting for the form text color
        register_setting('api_listings_plugin_settings', 'api_listings_contact_form_text_white');

        add_settings_field(
            'api_listings_contact_form_text_white_field',
            __('White Text On Contact Form', 'my-custom-plugin'),
            array($this, 'contact_form_text_white_field_callback'),
            'api_listings_plugin_settings',
            'my_custom_plugin_section'
        );
    }
    
    /**
     * Settings section callback
     */
    public function settings_section_callback() {
        echo '<p>' . __('Configure the plugin settings below.', 'my-custom-plugin') . '</p>';
    }
    
    /**
     * Settings fields callbacks
     */
    public function settings_field_callback() {
        $option = get_option('api_listings_property_id', '');
        echo '<input type="text" name="api_listings_property_id" value="' . esc_attr($option) . '" class="regular-text" />';
    }

    public function card_text_white_field_callback() {
        $option = get_option('api_listings_card_text_white', false);
        echo '<input type="checkbox" name="api_listings_card_text_white" value="1" ' . checked(1, $option, false) . ' />';
    }

    public function card_color_picker_field_callback() {
        $option = get_option('api_listings_card_color', '#26bbe0');
        echo '<input type="text" name="api_listings_card_color" value="' . esc_attr($option) . '" class="api-plugin-color-picker" data-default-color="#26bbe0" />';
    }
    
    public function button_color_picker_field_callback() {
        $option = get_option('api_listings_button_color', '#287092');
        echo '<input type="text" name="api_listings_button_color" value="' . esc_attr($option) . '" class="api-plugin-color-picker" data-default-color="#287092" />';
    }

    public function button_text_color_picker_field_callback() {
        $option = get_option('api_listings_button_text_color', '#ffffff');
        echo '<input type="text" name="api_listings_button_text_color" value="' . esc_attr($option) . '" class="api-plugin-color-picker" data-default-color="#ffffff" />';
    }

    public function contact_form_background_color_picker_field_callback() {
        $option = get_option('api_listings_contact_form_color', '#8c9d66');
        echo '<input type="text" name="api_listings_contact_form_color" value="' . esc_attr($option) . '" class="api-plugin-color-picker" data-default-color="#8c9d66" />';
    }

    public function contact_form_button_color_picker_field_callback() {
        $option = get_option('api_listings_contact_form_button_color', '#8c9d66');
        echo '<input type="text" name="api_listings_contact_form_button_color" value="' . esc_attr($option) . '" class="api-plugin-color-picker" data-default-color="#8c9d66" />';
    }

    public function contact_form_button_text_color_picker_field_callback() {
        $option = get_option('api_listings_contact_form_button_text_color', '#ffffff');
        echo '<input type="text" name="api_listings_contact_form_button_text_color" value="' . esc_attr($option) . '" class="api-plugin-color-picker" data-default-color="#ffffff" />';
    }

    public function contact_form_text_white_field_callback() {
        $option = get_option('api_listings_contact_form_text_white', false);
        echo '<input type="checkbox" name="api_listings_contact_form_text_white" value="1" ' . checked(1, $option, false) . ' />';
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts($hook) {
        if ('settings_page_my-custom-plugin' !== $hook) {
            return;
        }
        
        wp_enqueue_style(
            'my-custom-plugin-admin',
            MY_CUSTOM_PLUGIN_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            MY_CUSTOM_PLUGIN_VERSION
        );
        
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script(
            'my-custom-plugin-admin',
            MY_CUSTOM_PLUGIN_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-color-picker'),
            MY_CUSTOM_PLUGIN_VERSION,
            true
        );
        
        // Localize script for AJAX
        wp_localize_script('my-custom-plugin-admin', 'my_custom_plugin_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('my_custom_plugin_nonce')
        ));
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_style(
            'my-custom-plugin-frontend',
            MY_CUSTOM_PLUGIN_PLUGIN_URL . 'dist/index.scss.css',
            array(),
            MY_CUSTOM_PLUGIN_VERSION
        );

        wp_enqueue_style(
            'my-custom-plugin-print',
            MY_CUSTOM_PLUGIN_PLUGIN_URL . 'assets/css/print.css',
            array(),
            MY_CUSTOM_PLUGIN_VERSION
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
            'my-custom-plugin-frontend',
            MY_CUSTOM_PLUGIN_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery', 'slick-slider'),
            MY_CUSTOM_PLUGIN_VERSION,
            true
        );

        wp_enqueue_script(
            'my-custom-plugin-shortcode',
            MY_CUSTOM_PLUGIN_PLUGIN_URL . 'assets/js/shortcode.js',
            array('jquery'),
            MY_CUSTOM_PLUGIN_VERSION,
            true
        );

        wp_localize_script('my-custom-plugin-shortcode', 'api_listings_plugin_settings', array(
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
        add_shortcode('my_custom_shortcode', array($this, 'shortcode_callback'));
        add_shortcode('api_listings_container', array($this, 'api_shortcode_callback'));
    }
    
    /**
     * Shortcode callback
     */
    public function shortcode_callback($atts, $content = '') {
        $atts = shortcode_atts(array(
            'title' => 'Default Title',
            'class' => 'my-custom-shortcode'
        ), $atts, 'my_custom_shortcode');
        
        ob_start();
        ?>
        <div class="<?php echo esc_attr($atts['class']); ?>">
            <h3><?php echo esc_html($atts['title']); ?></h3>
            <div class="content">
                <?php echo wp_kses_post($content); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

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

    public function add_attributes_to_block( $attributes = [], $content = '' ) {
        $escaped_data_attributes = [];
    
        foreach ( $attributes as $key => $value ) {
            if ( is_bool( $value ) ) {
                $value = $value ? 'true' : 'false';
            }
            if ( ! is_scalar( $value ) ) {
                $value = wp_json_encode( $value );
            }
            $escaped_data_attributes[] = 'data-' . esc_attr( strtolower( preg_replace( '/(?<!\ )[A-Z]/', '-$0', $key ) ) ) . '="' . esc_attr( $value ) . '"';
        }
    
        return preg_replace( '/^<div /', '<div ' . implode( ' ', $escaped_data_attributes ) . ' ', trim( $content ) );
    }

    public function enqueue_frontend_script() {
        $script_path       = 'build/frontend.js';
        $script_asset_path = 'build/frontend.asset.php';
        $script_asset      = require( $script_asset_path );
        $script_url = plugins_url( $script_path, __FILE__ );
        wp_enqueue_script( 'script', $script_url, $script_asset['dependencies'], $script_asset['version'] );
    }   

    public function render_block_with_attribures( $attributes = [], $content = '' ) {
        if ( ! is_admin() ) {
            $this->enqueue_frontend_script();
        }

        return $this->add_attributes_to_block($attributes, $content);
    }

    /**
     * Registers the block using a `blocks-manifest.php` file, which improves the performance of block type registration.
     * Behind the scenes, it also registers all assets so they can be enqueued
     * through the block editor in the corresponding context.
     *
     * @see https://make.wordpress.org/core/2025/03/13/more-efficient-block-type-registration-in-6-8/
     * @see https://make.wordpress.org/core/2024/10/17/new-block-type-registration-apis-to-improve-performance-in-wordpress-6-7/
     */
    public function create_block_api_listings_block_init() {
        /**
         * Registers the block(s) metadata from the `blocks-manifest.php` file.
         * Added to WordPress 6.7 to improve the performance of block type registration.
         *
         * @see https://make.wordpress.org/core/2024/10/17/new-block-type-registration-apis-to-improve-performance-in-wordpress-6-7/
         */
        if ( function_exists( 'wp_register_block_metadata_collection' ) ) {
            wp_register_block_metadata_collection( __DIR__ . '/build', __DIR__ . '/build/blocks-manifest.php' );
        }
        /**
         * Registers the block type(s) in the `blocks-manifest.php` file.
         *
         * @see https://developer.wordpress.org/reference/functions/register_block_type/
         */
        $manifest_data = require __DIR__ . '/build/blocks-manifest.php';
        foreach ( array_keys( $manifest_data ) as $block_type ) {
            register_block_type( __DIR__ . "/build/{$block_type}", array(
                'render_callback' => array( $this, 'render_block_with_attribures' ),
            ) );
        }
    }
}

// Initialize the plugin
MyCustomPlugin::get_instance();

/**
 * Helper function to get plugin instance
 */
function my_custom_plugin() {
    return MyCustomPlugin::get_instance();
}