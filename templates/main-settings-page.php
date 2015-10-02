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
<div>
    <form action="options.php" method="post">
        <?php settings_fields( \wp_oauth_framework\classes\Admin_Menu::OPTION_GROUP ); ?>
        <?php do_settings_sections( \wp_oauth_framework\classes\Admin_Menu::MENU_SLUG ); ?>

        <input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
    </form>
</div>