<?php


namespace wp_oauth_framework\classes {

    defined( 'ABSPATH' ) or die( "No script kiddies please!" );

    use fkooman\OAuth\Client\AccessToken;
    use fkooman\OAuth\Client\Api;
    use fkooman\OAuth\Client\Context;
    use fkooman\OAuth\Client\SessionStorage;
    use fkooman\OAuth\Client\Guzzle3Client;
    use Guzzle\Http\Client;
    use Guzzle\Http\Exception\ClientErrorResponseException;
    use wp_oauth_framework\Login_Manager;
    use wp_oauth_framework\Override_Handler;
    use wp_oauth_framework\WPOF_Access_Token;

    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/../lib/wpof-client-config.php';

    /**
     * Class Oauth_Service
     *
     * This class provides all functionality for 'registered' OAuth providers.
     * Other plugins can add or 'register' additional OAuth providers through the provided filters
     *
     * @package wp_oauth_framework\classes
     */
    class Oauth_Service
    {

        /**
         * Display name of the OAuth service
         *
         * @var
         */
        protected $service_name;

        /**
         * Slug used to add the OAuth provider specific settings page to the menu.
         * Also used as an unique identifier for the OAuth provider
         *
         * @var
         */
        protected $submenu_slug;

        /**
         * The option group of the OAuth provider's settings
         * @var
         */
        protected $option_group;

        /**
         * Option name under which the OAuth provider's settings will be saved
         *
         * @var
         */
        protected $option_name;

        /**
         * Options page where the settings of the OAuth provider will be shown
         *
         * @var
         */
        protected $option_page;

        /**
         * Options section to which the settings fields of the OAuth provider will be added
         *
         * @var
         */
        protected $options_section;

        /**
         * Configuration parameters stored from the constructor's 2nd argument
         *
         * @var
         */
        protected $config_parameters;

        /**
         * @var \fkooman\OAuth\Client\SessionStorage
         */
        protected $token_storage;

        /**
         * @var \fkooman\OAuth\Client\Api;
         */
        protected $api;

        /**
         * @var \fkooman\OAuth\Client\Context
         */
        protected $context;

        /**
         * Array of field slugs/ids used for the settings page
         *
         * @var array
         */
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

        /**
         * Creates an instance of Oauth_Service and adds settings pages to the menu
         *
         * @param $service_name
         * @param $config
         */
        public function __construct($service_name, $config)
        {
            $this->service_name = $service_name;
            $this->config_parameters = $config;

            $this->init_settings();
            if( $this->has_valid_api_credentials() ) {
                $this->create_helper_objects();
            }

            add_action( 'wp_logout', array( $this, 'logout') );
        }

        /**
         * Sets the parameters to add the settings page
         */
        public function init_settings()
        {
            $this->submenu_slug = 'wpof-' . sanitize_title($this->service_name);
            $this->option_group = $this->submenu_slug . '-settings';
            $this->option_name = $this->submenu_slug . '-settings';
            $this->option_page = $this->submenu_slug . '-options';
            $this->options_section = $this->submenu_slug . '-settings-section';
        }

        /**
         * Returns true if there is a client id and a client secret set for the service
         * in the settings page, returns false if not
         *
         * @return bool
         */
        public function has_valid_api_credentials() {
            $options = get_option( $this->option_name );

            if( ! isset( $options['client_id'] ) ) {
                return false;
            } elseif( empty ($options['client_id'] ) ) {
                return false;
            } elseif( ! isset( $options['client_secret'] ) ) {
                return false;
            } elseif( empty ($options['client_secret'] ) ) {
                return false;
            }
            return true;
        }

        /**
         * Creates and stores the instances of classes used from fkooman's library
         */
        public function create_helper_objects() {
            $this->token_storage = new SessionStorage();
            $this->api = new Api($this->submenu_slug, $this->get_client_config(), $this->token_storage, new Guzzle3Client());
            if (! isset( $_SESSION[$this->submenu_slug] ) ) {
                $_SESSION[$this->submenu_slug] = array( 'uuid' => sha1( openssl_random_pseudo_bytes( 1024 ) ) );
            }
            $this->context = new Context( $_SESSION[$this->submenu_slug]['uuid'] , $this->get_client_config()->get_scope() );
        }

        /**
         * Adds the settings page
         */
        public function add_settings()
        {
            register_setting(
                $this->option_group,
                $this->option_name,
                array($this, 'sanitize_settings')
            );

            add_settings_section(
                $this->options_section,
                $this->service_name,
                array($this, 'show_settings_section'),
                $this->option_page
            );

            add_settings_field(
                $this->submenu_slug . '-client_id',
                'Client ID',
                array($this, 'show_input_field'),
                $this->option_page,
                $this->options_section,
                array('client_id')
            );

            add_settings_field(
                $this->submenu_slug . '-client_secret',
                'Client Secret',
                array($this, 'show_input_field'),
                $this->option_page,
                $this->options_section,
                array('client_secret')
            );
        }

        /**
         * Adds the submenu page to the menu
         */
        public function add_to_menu()
        {
            add_submenu_page(
                Admin_Menu::MENU_SLUG,
                $this->service_name,
                $this->service_name,
                Admin_Menu::REQUIRED_CAPABILITY,
                $this->submenu_slug,
                array($this, 'show_settings_page')
            );
        }

        /**
         * Displays the settings page
         */
        public function show_settings_page()
        {
            include_once __DIR__ . '/../templates/settings-sub-page.php';
        }

        /**
         * Displays the settings section
         */
        public function show_settings_section()
        {
            echo '<p>Settings to enable ' . $this->service_name . ' login.</p>';
        }

        /**
         * Displays an input field
         *
         * @param $args
         */
        public function show_input_field($args)
        {
            $options = get_option($this->option_name);
            if (isset($options[$args[0]])) {
                $value = $options[$args[0]];
            } else {
                $value = '';
            }
            ?>
            <input id="<?php echo $this->submenu_slug . '-' . $args[0]; ?>"
                   name="<?php echo $this->option_name . '[' . $args[0] . ']' ?>" value="<?php echo $value; ?>">
        <?php
        }

        /**
         * Sanitizes the settings for the service
         *
         * @param $settings
         * @return mixed|void
         */
        public function sanitize_settings($settings)
        {
            $settings = apply_filters('wpof_sanitize_settings_' . $this->service_name, $settings);
            return $settings;
        }

        /**
         * Returns the name of the Oauth Service
         *
         * @return mixed
         */
        public function get_service_name()
        {
            return $this->service_name;
        }

        /**
         * Returns the client configuration
         *
         * @return WPOF_Client_Config
         */
        public function get_client_config()
        {
            $options = get_option( $this->option_name );
            $config_data = array_merge(
                $options,
                $this->config_parameters,
                array( 'redirect_uri' => admin_url( 'admin-ajax.php?action=wpof_callback&service=' . $this->service_name ) )
            );
            return new WPOF_Client_Config( $config_data );
        }

        /**
         * Returns the url of the login page as defined by WP core, avoiding any filters
         * that might be applied by other plugins or themes
         *
         * @return string
         */
        public function get_login_url() {
            $path = 'wp-login.php';
            $scheme = 'login';
            if ( empty( $blog_id ) || !is_multisite() ) {
                	                $url = get_option( 'siteurl' );
            } else {
                switch_to_blog( $blog_id );
                $url = get_option( 'siteurl' );
                restore_current_blog();
	        }

            $url = set_url_scheme( $url, $scheme );

            if ( $path && is_string( $path ) ) {
                $url .= '/' . ltrim( $path, '/' );
            }

            return $url . '?oauth=' . $this->service_name;
        }

        /**
         * Displays the login button, supports overriding by theme or plugins
         * First looks in the theme folder
         *
         */
        public function display_login_button() {
            include Override_Handler::get_template_path_for_theme_or_extension_override( 'template-login-button.php', $this->submenu_slug, $this->get_client_config()->get_plugin_folder() );
        }

        /**
         * Displays the logo of the service
         */
        public function display_logo() {
            ?>
            <img src="<?php echo $this->get_image_url( 'logo.png' );?>" >
        <?php
        }

        /**
         * Returns the url for the image with the given name
         * First checks the theme folder ({theme}/wp-oauth-framework/{service_submenu_slug}/images,
         * if no image with the given name is found, checks the plugin folder of the service.
         * This plugin folder is not the folder of the framework but of the extension using the framework.
         *
         * @param $file_name
         * @return string
         */
        public function get_image_url( $file_name ) {
            $theme_folder = get_template_directory() . '/wp-oauth-framework/' . $this->submenu_slug . '/images';
            $plugin_folder = $this->get_client_config()->get_plugin_folder() . '/images';

            if( file_exists( $theme_folder . '/' . $file_name ) ) {
                return get_template_directory_uri() . '/' . $file_name;
            }elseif( file_exists( $plugin_folder . '/' . $file_name ) ) {
                return plugin_dir_url( $this->get_client_config()->get_plugin_file() ) . 'images/' . $file_name;
            }else {
                return plugin_dir_url( __FILE__ ) . '../images/' . $file_name;
            }
        }

        /**
         * Returns a Oauth_Service object for the given service name
         * Returns false if no service was registered with the given service name
         *
         * @param $service_name
         * @return Oauth_Service
         */
        public static function get_service_by_name($service_name)
        {
            foreach (apply_filters('wpof_registered_services', array()) as $reqistered_service) {
                if ($service_name == $reqistered_service->get_service_name()) {
                    return $reqistered_service;
                }
            }
            return false;
        }

        /**
         * Returns the SessionStorage object used for this service
         *
         * @return \fkooman\OAuth\Client\SessionStorage
         */
        public function get_token_storage() {
            return $this->token_storage;
        }

        /**
         * Returns the submenu_slug for this service
         *
         * @return string
         */
        public function get_submenu_slug() {
            return $this->submenu_slug;
        }

        /**
         * Starts the login flow for this service
         */
        public function start()
        {
            if( $this->has_valid_api_credentials() ) {
//                error_reporting(-1);
//                ini_set('display_errors', 'On');

                try {
                    /* OAuth client configuration */
                    // already done in $this->create_helper_objects()

                    /* get the access token */
                    $access_token = $this->api->getAccessToken($this->context);
                    if (false === $access_token) {
                        /* no valid access token available just yet, go to authorization server */
                        header( 'HTTP/1.1 302 Found' );
                        header( 'Location: ' . $this->api->getAuthorizeUri( $this->context ) );
                        exit;
                    }

                    /* we have an access token */
//                    var_dump( $access_token ); die;
                    $this->handle_access_token( $access_token );

                } catch (Exception $e) {
                    Login_Manager::redirect_to_login_url_with_config_error( $this->get_service_name() );
                }
            }
        }

        /**
         * Handles the login flow using the given access_token
         *
         * @param WPOF_Access_Token $access_token
         */
        public function handle_access_token( WPOF_Access_Token $access_token) {
            $user_info_endpoint = apply_filters(
                'wpof_user_info_endpoint_' . $this->service_name,
                $this->get_client_config()->get_user_info_endpoint(),
                $access_token
            );

            try {
                $client = new Client();
                if( $this->get_client_config()->get_user_info_endpoint_method() == 'post' ) {
                    $request = $client->post( $user_info_endpoint );
                } else {
                    $request = $client->get( $user_info_endpoint );
                }

                $request->addHeader('Authorization', sprintf('Bearer %s', $access_token->getAccessToken()));

                $response = $request->send();

                $user_info = apply_filters( 'wpof_user_info_data_' . $this->service_name, json_decode( (string) $response->getBody(), true ) );

                $user_id_for_service = $user_info['user_id'];

                $user_query = new \WP_User_Query(
                    array(
                        'meta_key' => $this->submenu_slug . '_id',
                        'meta_value' => $user_id_for_service,
                    )
                );

                $users = $user_query->get_results();

                if( sizeof( $users )  == 0 ) {
                    $user_id = $this->merge_with_existing_wp_user( $user_info );

                    if( ! $user_id ) {
                        $this->create_new_wp_user( $user_info );
                    } else {
                        $this->login_wp_user( $user_id );
                    }
                } elseif( sizeof( $users ) == 1 ) {
                    $this->login_wp_user( $users[0]->ID );
                } else {
                    // multiple users, what's going on...
                    echo "weird stuff happening, contact administrator";
                    die;
                }

            } catch (ClientErrorResponseException $e) {
                if (401 === $e->getResponse()->getStatusCode()) {
                    /* no valid access token available just yet, go to authorization server */
                    $this->api->deleteAccessToken($this->context);
                    header('HTTP/1.1 302 Found');
                    header('Location: ' . $this->api->getAuthorizeUri($this->context));
                    exit;
                }
                Login_Manager::redirect_to_login_url_with_config_error( $this->get_service_name() );
            }
        }

        /**
         * Checks if given the user info contains an email address of an existing profile
         * and merges the given user info with data for the user of the matching profile
         *
         * @param $user_info
         * @return bool|int
         */
        public function merge_with_existing_wp_user( $user_info ) {
            if( ! empty( $user_info['email'] ) ) {
                $user = get_user_by( 'email', $user_info['email'] );

                if( $user ) {
                    update_user_meta( $user->ID, $this->submenu_slug . '_id', $user_info['user_id'] );
                    return $user->ID;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }

        /**
         * Creates a new WordPress user with the given user info
         *
         * @param $user_info
         */
        public function create_new_wp_user( $user_info ) {
            if( empty( $user_info['email'] ) ) {
                Login_Manager::redirect_to_login_url_with_no_email_error( $this->get_service_name() );
            } else {
                $user_name = $this->get_new_user_name( $user_info['name'] );
                $password = sha1( openssl_random_pseudo_bytes( 64 ) );
                $user_id = wp_create_user( $user_name, $password , $user_info['email'] );
                update_user_meta( $user_id, $this->submenu_slug . '_id', $user_info['user_id'] );

                update_user_meta( $user_id, 'first_name', $user_info['first_name'] );
                update_user_meta( $user_id, 'last_name', $user_info['last_name'] );

                foreach( apply_filters( 'wpof_extra_user_meta_fields_' . $this->service_name, array() ) as $meta_field_name ) {
                    update_user_meta( $user_id, $meta_field_name, $user_info[$meta_field_name] );
                }

                $this->login_wp_user( $user_id );
            }
        }

        /**
         * Logs in the user with given user id
         *
         * @param $user_id
         */
        public function login_wp_user( $user_id ) {
            wp_set_auth_cookie( $user_id, 0, 0);
            do_action( 'wpof_oauth_login', $user_id, $this->submenu_slug );
            header( 'Location:' . Admin_Menu::get_success_login_url() );
        }

        /**
         * Returns a unique user name by adding a numeric suffix to the given name
         *
         * @param $given_name
         * @param int $suffix
         * @return string
         */
        public function get_new_user_name( $given_name, $suffix = 0 ) {
            if( $suffix > 0 ) {
                $name_to_check = $given_name . $suffix;
            } else {
                $name_to_check = $given_name;
            }

            if( get_user_by( 'login', sanitize_title( $name_to_check ) ) ) {
                return $this->get_new_user_name( $given_name , $suffix + 1 );
            } else {
                return sanitize_title( $name_to_check );
            }

        }

        /**
         * Logs out the current user
         */
        public function logout() {
            if( isset( $_SESSION[$this->submenu_slug] ) ) {
                unset( $_SESSION[$this->submenu_slug] );
            }
        }

        /**
         * Loads the css provided by the extension plugin(s)
         */
        public function enqueue_style() {
            $style_url = $this->get_client_config()->get_style_url();
            if( $style_url ) {
                wp_register_style( 'wpof-social-login-' . $this->submenu_slug, $style_url );
                wp_enqueue_style( 'wpof-social-login-' . $this->submenu_slug );
            }
        }
    }
}