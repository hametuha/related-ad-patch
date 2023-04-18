<?php

namespace Hametuha\RelatedAdPatch;


use Hametuha\RelatedAdPatch\Pattern\SingletonPattern;

/**
 * Setting screen.
 */
class Setting extends SingletonPattern {

	/**
	 * {@inheritdoc}
	 */
	protected function init() {
		add_action( 'admin_menu', [ $this, 'add_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	/**
	 * Register submenu.
	 *
	 * @return void
	 */
	public function add_menu() {
		add_options_page( __( 'Related Posts Setting', 'rap' ), __( 'Related Posts', 'rap' ), 'manage_options', 'related-ad-patch', [ $this, 'render_menu' ] );
	}

	/**
	 * Register setting.
	 *
	 * @return void
	 */
	public function register_settings() {
		// Register section.
		add_settings_section( 'rap-general', __( 'Related Posts Rule', 'rap' ), function() {

		}, 'related-ad-patch' );
		// Register fields.
		add_settings_field( 'rap-logic', __( 'Relation Factor', 'rap' ), function() {
			$now     = get_option( 'rap-logic', '' );
			$options = [
				''     => __( 'Matching Term Amount', 'rap' ),
				'date' => __( 'Post Date', 'rap' ),
			];
			?>
			<select name="rap-logic">
				<?php foreach ( $options as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>"<?php selected( $now, $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php
		}, 'related-ad-patch', 'rap-general' );
		register_setting( 'related-ad-patch', 'rap-logic' );
	}

	/**
	 * Render menu screen.
	 *
	 * @return void
	 */
	public function render_menu() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Related Posts Setting', 'rap' ); ?></h1>
			<form action="<?php echo admin_url( 'options.php' ); ?>" method="post">
				<?php
				settings_fields( 'related-ad-patch' );
				do_settings_sections( 'related-ad-patch' );
				submit_button();
				?>
			</form>
		<?php
	}
}
