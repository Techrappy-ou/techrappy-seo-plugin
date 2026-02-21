<?php
/**
 * Admin settings page template.
 *
 * @package TechrappySEO\Admin
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap techrappy-seo-settings-wrap">

	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php settings_errors( 'techrappy_seo_settings' ); ?>

	<form method="post" action="options.php">

		<?php
		settings_fields( 'techrappy-seo-settings-group' );
		do_settings_sections( 'techrappy-seo-settings' );
		submit_button( __( 'Enregistrer les réglages', 'techrappy-seo' ) );
		?>

	</form>

</div>
