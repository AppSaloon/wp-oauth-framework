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
 * Version:           1.0.1
 * Author:            Koen Gabriëls
 * Author URI:        http://www.appsaloon.be
 */

require_once __DIR__ . '/classes/login-manager.php';
require_once __DIR__ . '/classes/admin-menu.php';
require_once __DIR__ . '/classes/oauth-service.php';
require_once __DIR__ . '/classes/login-statistics.php';
require_once __DIR__ . '/classes/override-handler.php';

require_once __DIR__ . '/lib/wpof-callback.php';
require_once __DIR__ . '/lib/wpof-token-request.php';
require_once __DIR__ . '/lib/wpof-token-response.php';
require_once __DIR__ . '/lib/wpof-access-token.php';

new \wp_oauth_framework\classes\Admin_Menu();
new \wp_oauth_framework\Login_Manager();
new \wp_oauth_framework\classes\Login_Statistics();

add_filter( 'arpu_github_plugins', 'github_check_for_new_updates' );

function github_check_for_new_updates( $github_plugins ) {
    $github_plugins[] = array(
        'plugin_file' => __FILE__,
        'github_owner' => 'AppSaloon',
        'github_project_name' => 'wp-oauth-framework',
        'access_token' => 'e2061c14cdc7b2d6eef0eeca6cd66bd27475d055'
    );

    return $github_plugins;
}
