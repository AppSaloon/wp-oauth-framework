<?php
    defined( 'ABSPATH' ) or die( "No script kiddies please!" );

    if( ! current_user_can( \wp_oauth_framework\classes\Admin_Menu::REQUIRED_CAPABILITY ) ) {
        wp_die( 'Nice try but no sigar' );
    }
?>

<div>
    <h2>WordPress OAuth Framework</h2>
    This plugin serves as a base for other plugins that want to provide OAuth login services
</div>
<div>
    <p>Registered services:</p>
    <ul>
        <?php foreach( apply_filters( 'wpof_registered_services', array() ) as $service ): ?>
            <li><?php echo $service->get_service_name(); ?></li>
        <?php endforeach; ?>
    </ul>
</div>