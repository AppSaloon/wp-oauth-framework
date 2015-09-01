<?php
global $plugin_page;
?>
<div>
    <h2><?php echo $plugin_page; ?></h2>
    <form action="options.php" method="post">
        <?php settings_fields( $plugin_page . '-settings' ); ?>
        <?php do_settings_sections( $plugin_page . '-options' ); ?>

        <input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
    </form>
</div>