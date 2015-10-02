<?php

namespace wp_oauth_framework\classes;

defined( 'ABSPATH' ) or die( "No script kiddies please!" );

class Admin_Menu {

    const PAGE_TITLE = 'WP OAuth Framework';
    const MENU_TITLE = 'WP OAuth Framework';
    const REQUIRED_CAPABILITY = 'manage_options';
    const MENU_SLUG = 'wpof-main-page';

    const OPTION_GROUP = 'wpof-settings';
    const OPTION_NAME = 'wpof-settings';
    const OPTIONS_SECTION = 'wpof-settings-section';
    const SETTINGS_SECTION_TITLE = 'WP OAuth settings';

    protected $registered_services;

    public function __construct() {
        $this->registered_services = apply_filters( 'wpof_registered_services', array() );
        add_action( 'admin_menu', array( $this, 'add_to_menu' ) );
        add_action( 'admin_init', array( $this, 'admin_init' ) );
    }

    public function admin_init() {
        $this->add_redirect_settings();
        foreach( $this->registered_services as $index => $service ) {
            $service->add_settings();
        }
    }

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

    public function show_page() {
        include_once __DIR__ . '/../templates/main-settings-page.php';
    }

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

    public function show_settings_section() {
        //echo '<p>Settings to enable ' . $this->service_name . ' login.</p>';
    }

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

    public function sanitize_settings($settings) {
        $settings = apply_filters('wpof_sanitize_general_settings' , $settings);
        return $settings;
    }

    public static function get_success_login_url() {
        $options = get_option( self::OPTION_NAME, true );

        if( isset( $options['wpof-on-success-url'] ) ) {
            return $options['wpof-on-success-url'];
        } else {
            return home_url();
        }
    }

    public static function get_error_login_url() {
        $options = get_option( self::OPTION_NAME, true );

        if( isset( $options['wpof-on-error-url'] ) ) {
            return $options['wpof-on-error-url'];
        } else {
            return wp_login_url();
        }
    }
}