<?php

namespace wp_oauth_framework\classes;

class Oauth_Service {

    protected $service_name;

    protected $submenu_slug;
    protected $option_group;
    protected $option_name;
    protected $option_page;
    protected $options_section;

    protected $settings_fields = array(
        'client_id', // input in settings
        'client_secret', // input in settings
        'authorize_endpoint', // defined by child plugin
        'token_endpoint', // defined by child plugin
        'redirect_uri', // defined by master plugin
        'credentials_in_request_body', // boolean defined by child plugin
        'use_comma_separated_scope', // boolean defined by child plugin
        'default_token_type', // defined by child plugin
    );

    public function __construct( $service_name ) {
        $this->service_name = $service_name;
        $this->submenu_slug = 'wpof-' . sanitize_title( $service_name );
        $this->option_group = $this->submenu_slug . '-settings';
        $this->option_name = $this->submenu_slug . '-settings';
        $this->option_page = $this->submenu_slug . '-options';
        $this->options_section = $this->submenu_slug . '-settings-section';
    }

    public function add_settings() {
        register_setting(
            $this->option_group,
            $this->option_name,
            array( $this, 'sanitize_settings' )
        );

        add_settings_section(
            $this->options_section,
            $this->service_name,
            array( $this, 'show_settings_section' ),
            $this->option_page
        );

        add_settings_field(
            $this->submenu_slug . '-client_id',
            'Client ID',
            array( $this, 'show_input_field' ),
            $this->option_page,
            $this->options_section,
            array( 'client_id' )
        );

        add_settings_field(
            $this->submenu_slug . '-client_secret',
            'Client Secret',
            array( $this, 'show_input_field' ),
            $this->option_page,
            $this->options_section,
            array( 'client_secret' )
        );
    }

    public function add_to_menu() {
        add_submenu_page(
            Admin_Menu::MENU_SLUG,
            $this->service_name,
            $this->service_name,
            Admin_Menu::REQUIRED_CAPABILITY,
            $this->submenu_slug,
            array( $this, 'show_settings_page' )
        );
    }

    public function show_settings_page() {
        include_once __DIR__ . '/../templates/settings-sub-page.php';
    }

    public function show_settings_section() {
        echo '<p>Settings to enable ' . $this->service_name . ' login.</p>';
    }

    public function show_input_field( $args ) {
        $options = get_option( $this->option_name );
        if( isset( $options[$args[0]] ) ) {
            $value = $options[$args[0]];
        } else {
            $value = '';
        }
        ?>
        <input id="<?php echo $this->submenu_slug . '-' . $args[0];?>" name="<?php echo $this->option_name . '[' . $args[0] . ']' ?>" value="<?php echo $value;?>">
    <?php
    }

    public function sanitize_settings( $settings ) {
        $settings = apply_filters( 'wpof_sanitize_settings_' . $this->service_name, $settings );
        return $settings;
    }

    public function get_service_name() {
        return $this->service_name;
    }
}