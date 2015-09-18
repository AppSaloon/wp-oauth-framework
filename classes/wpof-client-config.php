<?php
namespace wp_oauth_framework\classes;

defined( 'ABSPATH' ) or die( "No script kiddies please!" );

use fkooman\OAuth\Client\ClientConfig;
use fkooman\OAuth\Client\ClientConfigInterface;

class WPOF_Client_Config extends ClientConfig implements ClientConfigInterface {

    protected $user_info_endpoint;
    protected $user_info_endpoint_method;
    protected $user_id_key;
    protected $user_name_key;
    protected $user_email_key;
    protected $plugin_folder;
    protected $plugin_file;
    protected $style_url;
    protected $scope;

    public function __construct(array $data) {
        foreach (array('client_id', 'client_secret') as $key) {
            if (!isset($data[$key])) {
                throw new ClientConfigException(sprintf("missing field '%s'", $key));
            }
        }
        $this->user_info_endpoint = $data['user_info_endpoint'];
        $this->user_info_endpoint_method = strtolower( $data['user_info_endpoint_method'] );
        $this->set_user_info_keys_for_response( $data );

        $this->plugin_folder = $data['plugin_folder'];
        $this->plugin_file = $data['plugin_file'];

        $this->style_url = isset( $data['style_url'] ) ? $data['style_url'] : false;

        $this->scope = $data['scope'];
        parent::__construct( $data );
    }

    public function set_user_info_keys_for_response( $data ) {
        $this->user_id_key = isset( $data['user_id_key'] ) ? $data['user_id_key'] : 'id';
        $this->user_name_key = isset( $data['user_name_key'] ) ? $data['user_name_key'] : 'name';
        $this->user_email_key = isset( $data['user_email_key'] ) ? $data['user_email_key'] : 'email';
    }

    public function get_user_info_endpoint() {
        return $this->user_info_endpoint;
    }

    public function get_user_id_key() {
        return $this->user_id_key;
    }

    public function get_user_name_key() {
        return $this->user_name_key;
    }

    public function get_user_email_key() {
        return $this->user_email_key;
    }

    public function get_plugin_folder() {
        return $this->plugin_folder;
    }

    public function get_plugin_file() {
        return $this->plugin_file;
    }

    public function get_style_url() {
        return $this->style_url;
    }

    public function get_scope() {
        return $this->scope;
    }

    public function get_user_info_endpoint_method() {
        return $this->user_info_endpoint_method;
    }
}