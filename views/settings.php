<?php defined( 'ABSPATH' ) || die(); ?>
<div class="wrap">
	<h1><?php echo esc_html__( 'JWT Authentication Settings', 'ww-jwt-auth' ); ?></h1>

	<form action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" method="post">
	<?php
	settings_fields( 'ww-jwt-auth' );
	do_settings_sections( 'ww-jwt-auth' );
	submit_button();
	?>
	</form>
</div>
