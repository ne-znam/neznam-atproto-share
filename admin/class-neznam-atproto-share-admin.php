<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Neznam_Atproto_Share
 * @subpackage Neznam_Atproto_Share/admin
 * @author     Marko Banušić <mbanusic@gmail.com>
 */
class Neznam_Atproto_Share_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	public function settings_link( $links ) {
		// Build and escape the URL.
		$url = esc_url( get_admin_url() . 'options-writing.php#' . $this->plugin_name );
		// Create the link.
		$settings_link = "<a href='$url'>" . __( 'Settings', $this->plugin_name ) . '</a>';
		// Adds the link to the end of the array.
		array_unshift(
			$links,
			$settings_link
		);
		return $links;
	}

	public function add_settings() {
		register_setting(
			'writing',
			$this->plugin_name . '-url',
			array(
				'sanitize_callback' => 'esc_url',
				'default'           => 'https://bsky.social/',
			)
		);
		register_setting(
			'writing',
			$this->plugin_name . '-handle',
			array(
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		register_setting(
			'writing',
			$this->plugin_name . '-secret',
			array(
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		register_setting(
			'writing',
			$this->plugin_name . '-default',
			array(
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '1',
			)
		);
		add_settings_section(
			$this->plugin_name . '-section',
			'Atproto Share settings',
			function () {
				echo '<p>Enter yout server information to enable posting.</p>';
			},
			'writing',
			array(
				'before_section' => '<hr id="' . esc_html( $this->plugin_name ) . '"/>',
			)
		);
		add_settings_field(
			$this->plugin_name . '-url',
			'Atproto URL',
			function () {
				?>
				<input type="text" name="<?php echo esc_html( $this->plugin_name ); ?>-url" value="<?php echo esc_html( get_option( $this->plugin_name . '-url' ) ); ?>" /><br>
				<small><?php esc_html_e( 'Enter the URL of your provider or leave as is for BlueSky', $this->plugin_name ); ?></small>
				<?php
			},
			'writing',
			$this->plugin_name . '-section'
		);
		add_settings_field(
			$this->plugin_name . '-handle',
			'Handle/username',
			function () {
				?>
				<input type="text" name="<?php echo esc_html( $this->plugin_name ); ?>-handle" value="<?php echo esc_html( get_option( $this->plugin_name . '-handle' ) ); ?>" />
				<?php
			},
			'writing',
			$this->plugin_name . '-section'
		);
		add_settings_field(
			$this->plugin_name . '-secret',
			'Secret',
			function () {
				?>
				<input type="password" name="<?php echo esc_html( $this->plugin_name ); ?>-secret" value="<?php echo esc_html( get_option( $this->plugin_name . '-secret' ) ); ?>" /><br>
				<small><?php echo __( 'Enter app password. If using BlueSky visit: <a href="https://bsky.app/settings/app-passwords" target="_blank">App passwords</a>', $this->plugin_name ); ?></small>
				<?php
			},
			'writing',
			$this->plugin_name . '-section'
		);
		add_settings_field(
			$this->plugin_name . '-default',
			'Default to share',
			function () {
				?>
				<input type="checkbox" name="<?php echo esc_html( $this->plugin_name ); ?>-default" value="1" <?php checked( 1, get_option( $this->plugin_name . '-default' ), true ); ?> />
				<?php
			},
			'writing',
			$this->plugin_name . '-section'
		);
	}

	public function edit_post( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! isset( $_POST[ $this->plugin_name . 'should-publish-nonce' ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $this->plugin_name . 'should-publish-nonce' ] ) ), 'save-should-publish' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$should_publish  = isset( $_POST[ $this->plugin_name . '-should-publish' ] ) ? 1 : 0;
		$text_to_publish = isset( $_POST[ $this->plugin_name . '-text-to-publish' ] ) ? sanitize_text_field( wp_unslash( $_POST[ $this->plugin_name . '-text-to-publish' ] ) ) : '';

		update_post_meta( $post_id, $this->plugin_name . '-should-publish', $should_publish );
		update_post_meta( $post_id, $this->plugin_name . '-text-to-publish', $text_to_publish );
	}

	public function add_meta_box( $post_type ) {
		add_meta_box(
			$this->plugin_name . '-meta-box',
			'Atproto Share',
			array(
				$this,
				'render_meta_box',
			),
			'post',
			'side',
			'high',
		);
	}

	public function render_meta_box() {
		$should_publish = get_post_meta( get_the_ID(), $this->plugin_name . '-should-publish', true );
		if ( false === $should_publish ) {
			$should_publish = get_option( $this->plugin_name . '-default' );
		}
		$text_to_publish = get_post_meta( get_the_ID(), $this->plugin_name . '-text-to-publish', true );
		?>
		<input
			id="<?php echo esc_html( $this->plugin_name ); ?>-should-publish"
			name="<?php echo esc_html( $this->plugin_name ); ?>-should-publish"
			type="checkbox"
			value="1" <?php checked( $should_publish ); ?>>
		<label
			for="<?php echo esc_html( $this->plugin_name ); ?>-should-publish"><?php esc_html_e( 'Publish on Atproto?', $this->plugin_name ); ?></label>
		<p class="howto"><?php esc_html_e( 'Publishes post to Atproto network.', $this->plugin_name ); ?></p>
		<label
			for="<?php echo esc_html( $this->plugin_name ); ?>-text-to-publish"><?php esc_html_e( 'Text to publish', $this->plugin_name ); ?></label>
		<input
			id="<?php echo esc_html( $this->plugin_name ); ?>-text-to-publish"
			name="<?php echo esc_html( $this->plugin_name ); ?>-text-to-publish"
			type="text"
			value="<?php echo esc_html( $text_to_publish ); ?>"/>
		<p class="howto"><?php esc_html_e( 'Text to add as status', $this->plugin_name ); ?></p>
		<?php wp_nonce_field( 'save-should-publish', $this->plugin_name . 'should-publish-nonce' ); ?>
		<?php
	}

	public function cron_schedule( $schedules ) {
		$schedules[ $this->plugin_name . '-every-minute' ] = array(
			'interval' => 60,
			'display'  => __( 'Every minute', $this->plugin_name ),
		);

		return $schedules;
	}
}
