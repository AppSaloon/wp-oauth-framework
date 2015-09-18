<?php

namespace wp_oauth_framework {
    defined('ABSPATH') or die("No script kiddies please!");

    class Override_Handler {

        public static function get_file_path_for_theme_override( $file_name ) {
            $theme_folder = get_template_directory() . '/wp-oauth-framework/templates';
            $framework_folder = __DIR__ . '/../templates';

            if( file_exists( $theme_folder . '/' . $file_name ) ) {
                return $theme_folder . '/' . $file_name;
            }else {
                return $framework_folder . '/' . $file_name;
            }
        }

        public static function get_template_path_for_theme_or_extension_override( $file_name, $extension_slug, $extension_folder ) {
            $theme_folder = get_template_directory() . '/wp-oauth-framework/' . $extension_slug .'/templates';
            $plugin_folder = $extension_folder . '/templates';
            $framework_folder = __DIR__ . '/../templates/';

            if( file_exists( $theme_folder . '/' . $file_name ) ) {
                return $theme_folder . '/' . $file_name;
            }elseif( file_exists( $plugin_folder . '/' . $file_name ) ) {
                return $plugin_folder . '/' . $file_name;
            }else {
                return $framework_folder . '/' . $file_name;
            }
        }

        public static function get_style_url_for_theme_override( $file_name ) {
            $theme_folder = get_template_directory() . '/wp-oauth-framework/css';

            if( file_exists( $theme_folder . '/' . $file_name ) ) {
                return get_template_directory_uri() . '/wp-oauth-framework/css/' . $file_name;
            }else {
                return plugins_url( 'css/social-logins.css', __DIR__ );
            }
        }
    }
}