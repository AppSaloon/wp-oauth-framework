<?php

namespace wp_oauth_framework\classes {

    use wp_oauth_framework\Override_Handler;

    defined('ABSPATH') or die("No script kiddies please!");

    class Login_Statistics {

        const META_PREFIX = 'wpof_login_count_';

        public function __construct() {
            add_action( 'wpof_oauth_login', array( $this, 'update_login_counter'), 10,  2 );
            add_action( 'wp_login', array( $this, 'update_normal_wp_login_counter') );
            add_shortcode( 'wpof_login_stats', array( $this, 'display_login_stats') );
        }

        public function update_normal_wp_login_counter( $user_login ) {
            $user = get_user_by( 'login', $user_login );
            $this->update_login_counter( $user->ID );
        }

        public function update_login_counter( $user_id, $service_slug = 'wordpress' ) {
            $user_count = (int) get_user_meta( $user_id, self::META_PREFIX . $service_slug );
            update_user_meta( $user_id, self::META_PREFIX . $service_slug, $user_count + 1 );

            $count = (int) get_option( self::META_PREFIX . $service_slug, true );
            update_option( self::META_PREFIX . $service_slug, $count + 1 );
        }

        public static function get_wpof_login_count( $service_name ) {
            $service = Oauth_Service::get_service_by_name( $service_name );

            return (int) get_option( self::META_PREFIX . $service->get_submenu_slug() );
        }

        public static function get_wp_login_count() {
            return (int) get_option( self::META_PREFIX . 'wordpress' );
        }

        public static function display_login_stats() {
            include Override_Handler::get_file_path_for_theme_override( 'template-login-stats.php' );
        }
    }
}

