<?php defined('ABSPATH') || die(); ?>
<div class="wrap">
    <h1><?=__('JWT Authentication Settings', 'ww-jwt-auth'); ?></h1>

    <form action="<?=esc_attr(admin_url('options.php'));?>" method="post">
    <?php
    settings_fields('ww-jwt-auth');
    do_settings_sections('ww-jwt-auth');
    submit_button();
    ?>
    </form>
</div>
