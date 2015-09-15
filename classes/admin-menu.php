<?php

namespace wp_oauth_framework\classes;

defined( 'ABSPATH' ) or die( "No script kiddies please!" );

class Admin_Menu {

    const PAGE_TITLE = 'WP OAuth Framework';
    const MENU_TITLE = 'WP OAuth Framework';
    const REQUIRED_CAPABILITY = 'manage_options';
    const MENU_SLUG = 'wpof-main-page';

    protected $registered_services;

    public function __construct() {
        $this->registered_services = apply_filters( 'wpof_registered_services', array() );
        add_action( 'admin_menu', array( $this, 'add_to_menu' ) );
        add_action( 'admin_init', array( $this, 'admin_init' ) );
    }

    public function admin_init() {
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
}