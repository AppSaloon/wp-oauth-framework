<?php defined( 'ABSPATH' ) or die( "No script kiddies please!" );?>

<div class="wpof-social-logins-container">
    <h3 class="wpof-social-logins-title"><?php _e( 'Login using:');?></h3>
    <?php foreach( $this->get_registered_services() as $registered_service ): ?>
        <?php $registered_service->display_login_button();?>
    <?php endforeach; ?>
</div>