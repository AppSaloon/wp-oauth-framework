<?php

namespace wp_oauth_framework\classes;

class Main_Page {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_to_menu' ) );
    }

    public function add_to_menu() {
        add_menu_page(
            'WP OAuth Framework',
            'WP OAuth Framework',
            'manage_options',
            'wpof-main-page',
            array( $this, 'show_page' ),
            '',
            81
        );
    }

    public function show_page() {
        include_once __DIR__ . '/../templates/main-page.php';
    }
}