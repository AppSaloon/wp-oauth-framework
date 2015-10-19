<?php

namespace wp_oauth_framework\classes {

    use wp_oauth_framework\Override_Handler;

    defined('ABSPATH') or die("No script kiddies please!");

    /**
     * Class Login_Statistics
     *
     * This class keeps track of the number of logins per user per method (OAuth provider or regular WP login)
     * Adds a shortcode to display statistics
     * @see Login_Statistics::display_login_stats()
     *
     * @package wp_oauth_framework\classes
     */
    class Login_Statistics {

        /**
         * Prefix added to the user_meta key for each statistic
         */
        const META_PREFIX = 'wpof_login_count_';

        /**
         * Creates an instance of Login_Statistics and adds all actions and the shortcode
         */
        public function __construct() {
            add_action( 'wpof_oauth_login', array( $this, 'update_login_counter'), 10,  2 );
            add_action( 'wp_login', array( $this, 'update_normal_wp_login_counter') );
            add_shortcode( 'wpof_login_stats', array( $this, 'display_login_stats') );
        }

        /**
         * Increases the number of logins using regular WP functionality for the given user
         * and increases the total number of logins using regular WP functionality for the site
         *
         * @param $user_login
         */
        public function update_normal_wp_login_counter( $user_login ) {
            $user = get_user_by( 'login', $user_login );
            $this->update_login_counter( $user->ID );
        }

        /**
         *
         * Increases the number of logins using the given OAuth provider for the given user
         * and increases the total number of logins using the given OAuth provider for the site
         *
         * @param $user_id
         * @param string $service_slug
         */
        public function update_login_counter( $user_id, $service_slug = 'wordpress' ) {
            $user_count = (int) get_user_meta( $user_id, self::META_PREFIX . $service_slug );
            update_user_meta( $user_id, self::META_PREFIX . $service_slug, $user_count + 1 );

            $count = (int) get_option( self::META_PREFIX . $service_slug, true );
            update_option( self::META_PREFIX . $service_slug, $count + 1 );
        }

        /**
         * Returns the number of logins using the given OAuth provider for the entire site
         *
         * @param $service_name
         * @return int
         */
        public static function get_wpof_login_count( $service_name ) {
            $service = Oauth_Service::get_service_by_name( $service_name );

            return (int) get_option( self::META_PREFIX . $service->get_submenu_slug() );
        }

        /**
         * Returns the number of logins using the regular WP login function for the entire site
         *
         * @return int
         */
        public static function get_wp_login_count() {
            return (int) get_option( self::META_PREFIX . 'wordpress' );
        }

        /**
         * Displays the logins per provider (OAuth or regular WP) for the entire site
         * Template can be overriden by the theme.
         *
         * To override the template, create a file called template-login-stats.php in a subfolder
         * templates underneath a folder called wp-oauth-framework in your theme.
         *
         * Full path: {your_theme_root_directory}/wp-oauth-framework/templates/template-login-stats.php
         *
         */
        public static function display_login_stats() {
            include Override_Handler::get_file_path_for_theme_override( 'template-login-stats.php' );
        }
    }
}

