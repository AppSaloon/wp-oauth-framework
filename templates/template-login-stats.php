<?php defined( 'ABSPATH' ) or die( "No script kiddies please!" );?>
<div class="wrap">
    <h2><?php _e( 'Login statistics' ); ?></h2>
    <?php foreach( \wp_oauth_framework\Login_Manager::get_registered_services() as $registered_service ):?>
        <?php $service_name = $registered_service->get_service_name();?>
        <p><?php echo __( 'Number of logins using ' ) . $service_name . ': ' . \wp_oauth_framework\classes\Login_Statistics::get_wpof_login_count( $service_name ); ?></p>
    <?php endforeach; ?>
    <p><?php echo __( 'Number of normal WP logins' ) . ': ' . \wp_oauth_framework\classes\Login_Statistics::get_wp_login_count( $service_name ); ?></p>
</div>
