<?php
/**
 * Markup API
 *
 * Provides a password-protected REST endpoint that accepts arbitrary HTML
 * markup, stores it in the database, and renders it on the front end through
 * a shortcode.
 *
 * @package ListingsAPI
 * @since 2.61
 */

namespace ListingsAPI;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Markup API handler
 */
class MarkupApi {

    /**
     * Option that stores the hashed endpoint password.
     */
    const PASSWORD_OPTION = 'brm_markup_api_password';

    /**
     * Prefix used for the option(s) that store the saved markup.
     *
     * The slug/key supplied by the request is appended so multiple,
     * independent markup blocks can be stored and displayed.
     */
    const CONTENT_OPTION_PREFIX = 'brm_markup_api_content_';

    /**
     * REST namespace for the endpoint.
     */
    const REST_NAMESPACE = 'listings-api/v1';

    /**
     * Settings group / page slug used by the options page.
     */
    const SETTINGS_GROUP = 'brm_markup_api_settings';

    /**
     * Singleton instance.
     *
     * @var MarkupApi|null
     */
    private static $instance = null;

    /**
     * Get the singleton instance.
     *
     * @return MarkupApi
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Register WordPress hooks.
     */
    private function init_hooks() {
        add_action('rest_api_init', array($this, 'register_routes'));
        add_action('init', array($this, 'register_shortcode'));

        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('admin_init', array($this, 'register_settings'));
            add_action('admin_post_brm_markup_api_save', array($this, 'handle_admin_save'));
        }
    }

    /**
     * Register the REST route used to save markup.
     */
    public function register_routes() {
        register_rest_route(
            self::REST_NAMESPACE,
            '/markup',
            array(
                'methods'             => \WP_REST_Server::CREATABLE, // POST
                'callback'            => array($this, 'handle_save_markup'),
                'permission_callback' => '__return_true', // Auth handled via password in the callback.
                'args'                => array(
                    'password' => array(
                        'required' => true,
                        'type'     => 'string',
                    ),
                    'markup'   => array(
                        'required' => true,
                        'type'     => 'string',
                    ),
                    'key'      => array(
                        'required' => false,
                        'type'     => 'string',
                        'default'  => 'default',
                    ),
                ),
            )
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/markup',
            array(
                'methods'             => \WP_REST_Server::READABLE, // GET
                'callback'            => array($this, 'handle_get_markup'),
                'permission_callback' => '__return_true', // Public, read-only endpoint.
                'args'                => array(
                    'key' => array(
                        'required' => false,
                        'type'     => 'string',
                        'default'  => 'default',
                    ),
                ),
            )
        );
    }

    /**
     * Handle a public request to retrieve stored markup.
     *
     * @param \WP_REST_Request $request The incoming request.
     * @return \WP_REST_Response|\WP_Error
     */
    public function handle_get_markup(\WP_REST_Request $request) {
        $key         = $this->sanitize_key_param($request->get_param('key'));
        $option_name = $this->get_content_option_name($key);

        // Distinguish "no such snippet" from an intentionally empty snippet.
        $markup = get_option($option_name, null);

        if (null === $markup) {
            return new \WP_Error(
                'markup_api_not_found',
                'No markup found for the requested key.',
                array('status' => 404)
            );
        }

        return new \WP_REST_Response(
            array(
                'success' => true,
                'key'     => $key,
                'markup'  => (string) $markup,
            ),
            200
        );
    }

    /**
     * Handle a request to save markup.
     *
     * @param \WP_REST_Request $request The incoming request.
     * @return \WP_REST_Response|\WP_Error
     */
    public function handle_save_markup(\WP_REST_Request $request) {
        $stored_password = get_option(self::PASSWORD_OPTION, '');

        // Reject if no password has been configured yet.
        if (empty($stored_password)) {
            api_listings_log_warning('Markup API request rejected: no password configured.', 'markup-api');
            return new \WP_Error(
                'markup_api_not_configured',
                'The markup API password has not been configured.',
                array('status' => 403)
            );
        }

        $provided_password = (string) $request->get_param('password');

        if (!wp_check_password($provided_password, $stored_password)) {
            api_listings_log_warning('Markup API request rejected: invalid password.', 'markup-api');
            return new \WP_Error(
                'markup_api_invalid_password',
                'Invalid password.',
                array('status' => 401)
            );
        }

        $key    = $this->sanitize_key_param($request->get_param('key'));
        $markup = (string) $request->get_param('markup');

        // Markup is supplied by a trusted, authenticated source and is meant to
        // be rendered verbatim, so it is stored without further sanitization.
        update_option($this->get_content_option_name($key), $markup, false);

        api_listings_log_info(sprintf('Markup saved for key "%s".', $key), 'markup-api');

        return new \WP_REST_Response(
            array(
                'success' => true,
                'key'     => $key,
                'message' => 'Markup saved.',
            ),
            200
        );
    }

    /**
     * Register the shortcode used to display saved markup.
     */
    public function register_shortcode() {
        add_shortcode('api_listings_markup', array($this, 'render_markup_shortcode'));
    }

    /**
     * Shortcode callback that outputs the stored markup.
     *
     * Usage: [api_listings_markup key="default"]
     *
     * @param array $atts Shortcode attributes.
     * @return string
     */
    public function render_markup_shortcode($atts) {
        $atts = shortcode_atts(
            array(
                'key' => 'default',
            ),
            $atts,
            'api_listings_markup'
        );

        $key    = $this->sanitize_key_param($atts['key']);
        $markup = (string) get_option($this->get_content_option_name($key), '');

        return sprintf(
            '<div class="api-listings-markup" data-markup-key="%s">%s</div>',
            esc_attr($key),
            $markup
        );
    }

    /**
     * Add the settings page under the "Settings" menu.
     */
    public function add_admin_menu() {
        add_options_page(
            'API Markup',
            'API Markup',
            'manage_options',
            'brm-api-markup',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Register the password setting and field.
     */
    public function register_settings() {
        register_setting(
            self::SETTINGS_GROUP,
            self::PASSWORD_OPTION,
            array(
                'sanitize_callback' => array($this, 'sanitize_password'),
                'default'           => '',
            )
        );

        add_settings_section(
            'brm_markup_api_section',
            'Markup API Settings',
            array($this, 'settings_section_callback'),
            self::SETTINGS_GROUP
        );

        add_settings_field(
            'brm_markup_api_password_field',
            'API Password',
            array($this, 'password_field_callback'),
            self::SETTINGS_GROUP,
            'brm_markup_api_section'
        );
    }

    /**
     * Sanitize and hash the password before saving.
     *
     * If the field is submitted empty, the existing password is preserved so a
     * blank submission never wipes the configured password.
     *
     * @param string $value Raw submitted value.
     * @return string Hashed password to store.
     */
    public function sanitize_password($value) {
        $value = is_string($value) ? trim($value) : '';

        if ('' === $value) {
            // Keep the existing password unchanged.
            return get_option(self::PASSWORD_OPTION, '');
        }

        return wp_hash_password($value);
    }

    /**
     * Render the settings section description.
     */
    public function settings_section_callback() {
        $endpoint = esc_url_raw(rest_url(self::REST_NAMESPACE . '/markup'));
        ?>
        <p>Set the password required to save markup through the API endpoint.</p>
        <p>
            <strong>Save endpoint (POST):</strong>
            <code><?php echo esc_html($endpoint); ?></code>
        </p>
        <p>
            Send a JSON body with <code>password</code>, <code>markup</code>, and an optional
            <code>key</code> (defaults to <code>default</code>). Display the saved markup with
            <code>[api_listings_markup key="default"]</code>.
        </p>
        <p>
            <strong>Retrieve endpoint (GET):</strong>
            <code><?php echo esc_html($endpoint); ?>?key=default</code>
        </p>
        <p>
            This endpoint is public and requires no password. It returns the stored markup
            for the requested <code>key</code>.
        </p>
        <?php
    }

    /**
     * Render the password input field.
     */
    public function password_field_callback() {
        $is_set = (bool) get_option(self::PASSWORD_OPTION, '');
        ?>
        <input
            type="password"
            name="<?php echo esc_attr(self::PASSWORD_OPTION); ?>"
            value=""
            class="regular-text"
            autocomplete="new-password"
            placeholder="<?php echo $is_set ? esc_attr('Password is set — leave blank to keep') : esc_attr('Enter a password'); ?>"
        />
        <p class="description">
            <?php
            echo $is_set
                ? 'A password is currently set. Enter a new value to change it, or leave blank to keep the current one.'
                : 'No password is set yet. The API endpoint is disabled until a password is configured.';
            ?>
        </p>
        <?php
    }

    /**
     * Render the settings page.
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields(self::SETTINGS_GROUP);
                do_settings_sections(self::SETTINGS_GROUP);
                submit_button();
                ?>
            </form>
            <?php $this->render_saved_snippets(); ?>
        </div>
        <?php
    }

    /**
     * Render a list of the markup snippets that have been saved via the API.
     */
    private function render_saved_snippets() {
        $snippets = $this->get_saved_snippets();
        $action   = esc_url(admin_url('admin-post.php'));
        ?>
        <hr />
        <h2>Saved Snippets</h2>
        <?php $this->render_snippet_notice(); ?>
        <?php if (empty($snippets)) : ?>
            <p>No markup has been submitted yet.</p>
        <?php else : ?>
            <p>Edit the markup below and save your changes, or delete a snippet entirely.</p>
            <?php foreach ($snippets as $key => $markup) : ?>
                <h3>
                    <code><?php echo esc_html($key); ?></code>
                    &mdash;
                    <code>[api_listings_markup key="<?php echo esc_attr($key); ?>"]</code>
                </h3>
                <form method="post" action="<?php echo $action; ?>">
                    <input type="hidden" name="action" value="brm_markup_api_save" />
                    <input type="hidden" name="key" value="<?php echo esc_attr($key); ?>" />
                    <?php wp_nonce_field('brm_markup_api_save_' . $key); ?>
                    <textarea
                        name="markup"
                        rows="8"
                        class="large-text code"
                    ><?php echo esc_textarea($markup); ?></textarea>
                    <p>
                        <?php submit_button('Save Changes', 'primary', 'save', false); ?>
                        <?php
                        submit_button(
                            'Delete',
                            'delete',
                            'delete',
                            false,
                            array('onclick' => "return confirm('Delete this snippet? This cannot be undone.');")
                        );
                        ?>
                    </p>
                </form>
            <?php endforeach; ?>
        <?php endif; ?>
        <?php
    }

    /**
     * Render an admin notice reflecting the result of a save/delete action.
     */
    private function render_snippet_notice() {
        $status = isset($_GET['brm_markup_status']) ? sanitize_key(wp_unslash($_GET['brm_markup_status'])) : '';

        if ('saved' === $status) {
            echo '<div class="notice notice-success is-dismissible"><p>Snippet updated.</p></div>';
        } elseif ('deleted' === $status) {
            echo '<div class="notice notice-success is-dismissible"><p>Snippet deleted.</p></div>';
        }
    }

    /**
     * Handle the edit/delete form submission from the settings page.
     */
    public function handle_admin_save() {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to manage markup snippets.');
        }

        $key = $this->sanitize_key_param(isset($_POST['key']) ? wp_unslash($_POST['key']) : '');

        check_admin_referer('brm_markup_api_save_' . $key);

        $option_name = $this->get_content_option_name($key);

        if (isset($_POST['delete'])) {
            delete_option($option_name);
            api_listings_log_info(sprintf('Markup deleted for key "%s" via admin.', $key), 'markup-api');
            $this->redirect_after_save('deleted');
        }

        // Markup is edited by a trusted administrator and rendered verbatim, so
        // it is stored without further sanitization (matching the API behavior).
        $markup = isset($_POST['markup']) ? wp_unslash($_POST['markup']) : '';
        update_option($option_name, $markup, false);
        api_listings_log_info(sprintf('Markup edited for key "%s" via admin.', $key), 'markup-api');
        $this->redirect_after_save('saved');
    }

    /**
     * Redirect back to the settings page with a status flag.
     *
     * @param string $status Status slug appended to the URL.
     */
    private function redirect_after_save($status) {
        $url = add_query_arg(
            array(
                'page'               => 'brm-api-markup',
                'brm_markup_status'  => $status,
            ),
            admin_url('options-general.php')
        );

        wp_safe_redirect($url);
        exit;
    }

    /**
     * Retrieve all saved markup snippets keyed by their slug.
     *
     * @return array<string, string>
     */
    private function get_saved_snippets() {
        global $wpdb;

        $like = $wpdb->esc_like(self::CONTENT_OPTION_PREFIX) . '%';

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_name ASC",
                $like
            )
        );

        $snippets = array();

        if ($rows) {
            $prefix_length = strlen(self::CONTENT_OPTION_PREFIX);
            foreach ($rows as $row) {
                $key            = substr($row->option_name, $prefix_length);
                $snippets[$key] = $row->option_value;
            }
        }

        return $snippets;
    }

    /**
     * Build the option name used to store markup for a given key.
     *
     * @param string $key Sanitized key.
     * @return string
     */
    private function get_content_option_name($key) {
        return self::CONTENT_OPTION_PREFIX . $key;
    }

    /**
     * Sanitize a key parameter, falling back to "default" when empty.
     *
     * @param mixed $key Raw key value.
     * @return string
     */
    private function sanitize_key_param($key) {
        $key = sanitize_key((string) $key);
        return '' === $key ? 'default' : $key;
    }
}

// Bootstrap the handler.
MarkupApi::get_instance();
