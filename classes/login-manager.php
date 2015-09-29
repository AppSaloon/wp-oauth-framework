<?php

namespace wp_oauth_framework {
    defined( 'ABSPATH' ) or die( "No script kiddies please!" );

    use fkooman\OAuth\Client\Callback;
    use fkooman\OAuth\Client\Guzzle3Client;
    use wp_oauth_framework\classes\Oauth_Service;

    require_once __DIR__ . '/../vendor/autoload.php';

    class Login_Manager
    {

        protected $service_name;

        public function __construct()
        {
            add_action('login_form', array($this, 'display_login_buttons'));
            add_action('login_init', array($this, 'login_init'));
            add_action('wp_ajax_wpof_callback', array($this, 'oauth_callback'));
            add_action('wp_ajax_nopriv_wpof_callback', array($this, 'oauth_callback'));

            add_action( 'login_enqueue_scripts', array( $this, 'enqueue_scripts_and_styles') );

        }

        public static function display_login_buttons() {
            include Override_Handler::get_file_path_for_theme_override( 'template-social-logins.php' );
        }

        public static function get_registered_services() {
            $registered_services = array();
            foreach (apply_filters('wpof_registered_services', array()) as $reqistered_service) {
                if( $reqistered_service->has_valid_api_credentials() ) {
                    $registered_services[] = $reqistered_service;
                }
            }
            return $registered_services;
        }

        public function login_init()
        {
            if( isset( $_GET['wpof_error'] ) ) {
                $this->service_name = $_GET['wpof_error'];
                add_filter( 'login_message', array( $this, 'login_error_message') );
            } else {
                remove_filter( 'login_message', array( $this, 'login_error_message') );
            }

            if (isset($_GET['oauth'])) {
                $this->start_oauth_service($_GET['oauth']);
            }
        }

        public function start_oauth_service($service_name)
        {
            $service = \wp_oauth_framework\classes\Oauth_Service::get_service_by_name($service_name);

            if ($service) {
                $service->start();
            } else {
                echo 'No script kiddies!';
                die;
            }
        }

        public function enqueue_scripts_and_styles() {
            wp_register_script( 'wpof-position-social-logins', plugins_url( 'js/position-social-logins.js', __DIR__  ), array( 'jquery'), '1.0', true );
            wp_enqueue_script( 'wpof-position-social-logins' );

            wp_register_style( 'wpof-social-logins', Override_Handler::get_style_url_for_theme_override( 'social-logins.css' ) );
            wp_enqueue_style( 'wpof-social-logins' );

            foreach( self::get_registered_services() as $registered_service ) {
                $registered_service->enqueue_style();
            }
        }

        public function oauth_callback() {
            $service = Oauth_Service::get_service_by_name( $_GET['service'] );

            if( $service ) {
                try {
                    /* initialize the Callback */
                    $cb = new WPOF_Callback( $service->get_submenu_slug(), $service->get_client_config(), $service->get_token_storage(), new Guzzle3Client() );
                    /* handle the callback */
                    $access_token = $cb->handleCallback($_GET);
                    $service->handle_access_token( $access_token );
                } catch (\Exception $e) {
                    header( 'Location: ' . wp_login_url() . '?wpof_error=' . $_GET['service'] );
                    die;
                }
            } else {
                echo 'No script kiddies';
                die;
            }
        }

        public function login_error_message( $message ) {
            if ( empty($message) ){
                return '<div id="login_error">	<strong>CONFIGURATION ERROR FOR '. $this->service_name . '</strong>:<br>' . __('Contact administrator') . '<br></div>';
            } else {
                return $message;
            }
        }
    }
}