<?php
defined( 'ABSPATH' ) or die( "No script kiddies please!" );

/**
 * WordPress OAuth Frame work
 *
 * @package   WP_OAuth_Framework
 * @author    Koen Gabriëls
 * @link      http://www.appsaloon.be
 * @copyright 2015 AppSaloon BVBA
 *
 * @wordpress-plugin
 * Plugin Name:       WordPress OAuth Framework
 * Description:       WordPress OAuth Framework to enable login with popular services
 * Version:           1.0.0
 * Author:            Koen Gabriëls
 * Author URI:        http://www.appsaloon.be
 */

require_once __DIR__ . '/classes/login-manager.php';
require_once __DIR__ . '/classes/admin-menu.php';
require_once __DIR__ . '/classes/oauth-service.php';

new \wp_oauth_framework\classes\Admin_Menu();
new \wp_oauth_framework\Login_Manager();