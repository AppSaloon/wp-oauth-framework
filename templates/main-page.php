<?php
    if( ! current_user_can( \wp_oauth_framework\classes\Main_Page::REQUIRED_CAPABILITY ) ) {
        wp_die( 'Nice try but no sigar' );
    }
?>
<div>
    This plugin allows other plugins to provide login via OAuth services
</div>