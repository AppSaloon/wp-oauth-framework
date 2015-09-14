<?php
namespace wp_oauth_framework\classes;

use fkooman\OAuth\Client\ClientConfig;
use fkooman\OAuth\Client\ClientConfigInterface;

class WPOF_Client_Config extends ClientConfig implements ClientConfigInterface {

    protected $user_info_endpoint;
    protected $user_id_key;

    public function __construct(array $data) {
        foreach (array('client_id', 'client_secret') as $key) {
            if (!isset($data[$key])) {
                throw new ClientConfigException(sprintf("missing field '%s'", $key));
            }
        }
        $this->user_info_endpoint = $data['user_info_endpoint'];
        $this->user_id_key = $data['user_id_key'];
        parent::__construct( $data );
    }

    public function get_user_info_endpoint() {
        return $this->user_info_endpoint;
    }

    public function get_user_id_key() {
        return $this->user_id_key;
    }
}