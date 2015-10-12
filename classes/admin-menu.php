<?php

namespace wp_oauth_framework\classes;

defined( 'ABSPATH' ) or die( "No script kiddies please!" );

/**
 * Class Admin_Menu
 * @package wp_oauth_framework\classes
 */
class Admin_Menu {

    /**
     * Title for the admin menu page
     */
    const PAGE_TITLE = 'WP OAuth Framework';

    /**
     * Title shown in the menu
     */
    const MENU_TITLE = 'WP OAuth Framework';

    /**
     * WP capability a user is required to have to access the settings
     */
    const REQUIRED_CAPABILITY = 'manage_options';

    /**
     * Unique slug for the menu
     */
    const MENU_SLUG = 'wpof-main-page';

    /**
     * Group used for the settings
     */
    const OPTION_GROUP = 'wpof-settings';

    /**
     * Name of the settings (as saved in wp_options)
     */
    const OPTION_NAME = 'wpof-settings';

    /**
     * The slug of the options section which is shown
     */
    const OPTIONS_SECTION = 'wpof-settings-section';

    /**
     * Title of the options section
     */
    const SETTINGS_SECTION_TITLE = 'WP OAuth settings';

    /**
     * Array of Oauth_Service instances returned by the 'wpof_registered_services' filter
     *
     * @var mixed|void
     */
    protected $registered_services;

    /**
     * Creates an instance of Admin_Menu and hooks into necessary WP actions
     */
    public function __construct() {
        $this->registered_services = apply_filters( 'wpof_registered_services', array() );
        add_action( 'admin_menu', array( $this, 'add_to_menu' ) );
        add_action( 'admin_init', array( $this, 'admin_init' ) );
    }

    /**
     * Adds the general settings, adds the settings of all registered OAuth services
     */
    public function admin_init() {
        $this->add_redirect_settings();
        foreach( $this->registered_services as $index => $service ) {
            $service->add_settings();
        }
    }

    /**
     * Adds the general page to the menu and a settings page for each registered service
     */
    public function add_to_menu() {
        add_menu_page(
            self::PAGE_TITLE,
            self::MENU_TITLE,
            self::REQUIRED_CAPABILITY,
            self::MENU_SLUG,
            array( $this, 'show_page' ),
            '',
            81
        );

        foreach( $this->registered_services as $service ) {
            $service->add_to_menu();
        }
    }

    /**
     * Callback function to show the general settings page
     */
    public function show_page() {
        include_once __DIR__ . '/../templates/main-settings-page.php';
    }

    /**
     * Registers settings and adds section and fields for the general options page
     */
    public function add_redirect_settings() {
        register_setting(
            self::OPTION_GROUP,
            self::OPTION_NAME,
            array( $this, 'sanitize_settings' )
        );

        add_settings_section(
            self::OPTIONS_SECTION,
            self::SETTINGS_SECTION_TITLE,
            array( $this, 'show_settings_section' ),
            self::MENU_SLUG
        );

        add_settings_field(
            'wpof-on-success-url',
            'Redirect URL on success',
            array( $this, 'show_input_field' ),
            self::MENU_SLUG,
            self::OPTIONS_SECTION,
            array( 'wpof-on-success-url' )
        );

        add_settings_field(
            'wpof-on-error-url',
            'Redirect URL on error',
            array( $this, 'show_input_field' ),
            self::MENU_SLUG,
            self::OPTIONS_SECTION,
            array( 'wpof-on-error-url' )
        );
    }

    /**
     * Does nothing but required to have a callback function for add_settings_section
     * See add_redirect_settings()
     */
    public function show_settings_section() {
        //echo '<p>Settings to enable ' . $this->service_name . ' login.</p>';
    }

    /**
     * Generic function to show an input field
     *
     * @param $args
     */
    public function show_input_field($args)
    {
        $options = get_option( self::OPTION_NAME );
        if (isset($options[$args[0]])) {
            $value = $options[$args[0]];
        } else {
            $value = '';
        }
        ?>
        <input id="<?php echo $args[0]; ?>"
               name="<?php echo self::OPTION_NAME . '[' . $args[0] . ']' ?>" value="<?php echo $value; ?>">
    <?php
    }

    /**
     * Sanitize general setting using filter 'wpof_sanitize_general_settings'
     *
     * @param $settings
     * @return mixed|void
     */
    public function sanitize_settings($settings) {
        $settings = apply_filters('wpof_sanitize_general_settings' , $settings);
        return $settings;
    }

    /**
     * Returns the redirect URL after successful login, taken from the options if set, otherwise returns the home_url
     * @return string|void
     */
    public static function get_success_login_url() {
        $options = get_option( self::OPTION_NAME, true );

        if( isset( $options['wpof-on-success-url'] ) ) {
            return $options['wpof-on-success-url'];
        } else {
            return home_url();
        }
    }

    /**
     * Returns the redirect URL after failed login, taken from the options if set, otherwise returns the wp_login_url()
     * @return string
     */
    public static function get_error_login_url() {
        $options = get_option( self::OPTION_NAME, true );

        if( isset( $options['wpof-on-error-url'] ) ) {
            return $options['wpof-on-error-url'];
        } else {
            return wp_login_url();
        }
    }
}