<?php

namespace wp_oauth_framework\classes;

class Main_Page {

    const PAGE_TITLE = 'WP OAuth Framework';
    const MENU_TITLE = 'WP OAuth Framework';
    const REQUIRED_CAPABILITY = 'manage_options';
    const MENU_SLUG = 'wpof-main-page';

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_to_menu' ) );
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
    }

    public function show_page() {
        include_once __DIR__ . '/../templates/main-page.php';
    }
}