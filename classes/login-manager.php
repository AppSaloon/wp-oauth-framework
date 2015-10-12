<?php

namespace wp_oauth_framework {
    defined( 'ABSPATH' ) or die( "No script kiddies please!" );

    use fkooman\OAuth\Client\Callback;
    use fkooman\OAuth\Client\Guzzle3Client;
    use wp_oauth_framework\classes\Admin_Menu;
    use wp_oauth_framework\classes\Oauth_Service;

    require_once __DIR__ . '/../vendor/autoload.php';

    /**
     * Class Login_Manager
     *
     * This class handles the login flow
     *
     * @package wp_oauth_framework
     */
    class Login_Manager
    {
        /**
         * Error number to represent a configuration error (either on WP or OAuth side)
         */
        const CONFIGURATION_ERROR = 1;

        /**
         * Error number to represent a missing email address during first time login/registration
         */
        const NO_EMAIL_ERROR = 2;

        /**
         * Name of the service that the current user is trying to use to log in
         * @var
         */
        protected $service_name;

        /**
         * Error code for the current user if any
         * @var
         */
        protected $error_code;

        /**
         * Return an instance of Login_Manager and hooks into the necessary WP actions
         */
        public function __construct()
        {
            add_action('login_form', array($this, 'display_login_buttons'));
            add_action('login_init', array($this, 'login_init'));
            add_action('wp_ajax_wpof_callback', array($this, 'oauth_callback'));
            add_action('wp_ajax_nopriv_wpof_callback', array($this, 'oauth_callback'));

            add_action( 'login_enqueue_scripts', array( $this, 'enqueue_scripts_and_styles') );

        }

        /**
         * Displays the login buttons for all registered services, allows overriding by theme or plugin extension
         */
        public static function display_login_buttons() {
            include Override_Handler::get_file_path_for_theme_override( 'template-social-logins.php' );
        }

        /**
         * Returns an array of Oauth_Service instances that have api credentials set in the options page for each service
         * Makes use of the filter wpof_registered_services
         *
         * @return array
         */
        public static function get_registered_services() {
            $registered_services = array();
            foreach (apply_filters('wpof_registered_services', array()) as $reqistered_service) {
                if( $reqistered_service->has_valid_api_credentials() ) {
                    $registered_services[] = $reqistered_service;
                }
            }
            return $registered_services;
        }

        /**
         * Runs on login_init and enables displaying of appropriate error.
         * Starts the OAuth login flow if Oauth service name provides in URL
         */
        public function login_init()
        {
            if( isset( $_GET['wpof_error'] ) ) {
                $this->error_code = $_GET['wpof_error'];

                if( isset( $_GET['service'] ) ) {
                    $this->service_name = $_GET['service'];
                } else {
                    $this->service_name = __( 'unknown' );
                }

                add_filter( 'login_message', array( $this, 'login_error_message') );
            } else {
                remove_filter( 'login_message', array( $this, 'login_error_message') );
            }

            if (isset($_GET['oauth'])) {
                $this->start_oauth_service($_GET['oauth']);
            }
        }

        /**
         * Starts the Oauth login flow for given OAuth service or displays error when no valid service name provided
         * @param $service_name
         */
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

        /**
         * Loads all scripts and styles for the login page
         */
        public function enqueue_scripts_and_styles() {
            wp_register_script( 'wpof-position-social-logins', plugins_url( 'js/position-social-logins.js', __DIR__  ), array( 'jquery'), '1.0', true );
            wp_enqueue_script( 'wpof-position-social-logins' );

            wp_register_style( 'wpof-social-logins', Override_Handler::get_style_url_for_theme_override( 'social-logins.css' ) );
            wp_enqueue_style( 'wpof-social-logins' );

            foreach( self::get_registered_services() as $registered_service ) {
                $registered_service->enqueue_style();
            }
        }

        /**
         * Callback function for OAuth provider, exposed using admin-ajax.php, displays error when no valid service
         * is passed through in the URL
         */
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
                    $this->redirect_to_login_url_with_error( self::CONFIGURATION_ERROR, $_GET['service'] );
                }
            } else {
                echo 'No script kiddies';
                die;
            }
        }

        /**
         * Returns error message dependant on whether or not there was already a error message set
         *
         * @param $message
         * @return string
         */
        public function login_error_message( $message ) {
            if ( empty($message) ){
                return static::get_error_message( $this->error_code, $this->service_name );
            } else {
                return $message;
            }
        }

        /**
         * Returns the html for an error message based on $error_code and $service_name
         *
         * @param $error_code
         * @param $service_name
         * @return string
         */
        private static function get_error_message( $error_code, $service_name ) {
            if( $error_code == static::CONFIGURATION_ERROR ) {
                return '<div id="login_error">	<strong>CONFIGURATION ERROR FOR '. $service_name . '</strong>:<br>' . __('Contact administrator') . '<br></div>';
            } elseif( $error_code == static::NO_EMAIL_ERROR ) {
                return '<div id="login_error">	<strong>' . $service_name . ' ' . __( 'did not return an email address' ) . '</strong>:<br>' . __('Contact administrator') . '<br></div>';
            } else {
                return '';
            }
        }

        /**
         * Displays error message based on passed arguments
         *
         * @param $get_vars
         */
        public static function display_error_message( $get_vars ) {
            if( isset( $get_vars['wpof_error'] ) && isset( $get_vars['service'] ) ) {
                echo static::get_error_message( $get_vars['wpof_error'], $get_vars['service'] );
            }
        }

        public static function redirect_to_login_url_with_no_email_error( $service ) {
            static::redirect_to_login_url_with_error( static::NO_EMAIL_ERROR, $service );
        }

        public static function redirect_to_login_url_with_config_error( $service ) {
            static::redirect_to_login_url_with_error( static::CONFIGURATION_ERROR, $service );
        }

        private static function redirect_to_login_url_with_error( $error_code, $service ) {
            $args = array(
                'wpof_error' => $error_code,
                'service' => $service,
            );
            $url = add_query_arg( $args, Admin_Menu::get_error_login_url() );
            header( 'Location: ' . $url );
            die;
        }
    }
}