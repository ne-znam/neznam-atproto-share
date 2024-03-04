<?php
/**
 * Admin class
 *
 * @package   Neznam_Atproto_Share
 * @subpackage Neznam_Atproto_Share/includes
 * @link      https://www.neznam.hr
 * @since      1.0.0
 */

/**
 * The admin-specific functionality of the plugin.
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

	/**
	 * Add settings links on plugins page
	 *
	 * @param mixed $links Plugin links.
	 *
	 * @return mixed
	 * @since 1.0.0
	 */
	public function settings_link( $links ) {
		// Build and escape the URL.
		$url = esc_url( get_admin_url() . 'options-writing.php#' . $this->plugin_name );
		// Create the link.
		$settings_link = "<a href='$url'>" . __( 'Settings', 'neznam-atproto-share' ) . '</a>';
		// Adds the link to the end of the array.
		array_unshift(
			$links,
			$settings_link
		);
		return $links;
	}

	/**
	 * Add all settings
	 *
	 * @since    1.0.0
	 */
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
				'sanitize_callback' => array( $this, 'check_handle' ),
			)
		);
		register_setting(
			'writing',
			$this->plugin_name . '-secret',
			array(
				'sanitize_callback' => array( $this, 'check_password' ),
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
		register_setting(
			'writing',
			$this->plugin_name . '-use-cron',
			array(
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '0',
			)
		);
		add_settings_section(
			$this->plugin_name . '-section',
			'Atproto Share settings',
			function () {
				echo '<p>' . esc_html__( 'Enter your server information to enable posting.', 'neznam-atproto-share' ) . '</p>';
				wp_nonce_field( $this->plugin_name . '-save-settings', $this->plugin_name . '-nonce' );
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
				<small><?php esc_html_e( 'Enter the URL of your provider or leave as is for BlueSky', 'neznam-atproto-share' ); ?></small>
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
				<small><?php echo esc_html__( 'Enter app password. If using BlueSky visit: <a href="https://bsky.app/settings/app-passwords" target="_blank">App passwords</a>', 'neznam-atproto-share' ); ?></small>
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

		add_settings_field(
			$this->plugin_name . '-default',
			'Use cron for sharing',
			function () {
				?>
				<input type="checkbox" name="<?php echo esc_html( $this->plugin_name ); ?>-use-cron" value="1" <?php checked( 1, get_option( $this->plugin_name . '-use-cron' ), true ); ?> />
				<small>Check this if you have trouble publishing posts. This will use cronjob to publish.</small>
				<?php
			},
			'writing',
			$this->plugin_name . '-section'
		);
	}

	/**
	 * Validate handle
	 *
	 * @param string $handle Handle to validate, without @.
	 *
	 * @return mixed
	 */
	public function check_handle( $handle ) {
		$nonce = isset( $_POST[ $this->plugin_name . '-nonce' ] ) ? sanitize_text_field( wp_unslash( $_POST[ $this->plugin_name . '-nonce' ] ) ) : '';
		if ( ! $nonce || ! wp_verify_nonce( $nonce, $this->plugin_name . '-save-settings' ) ) {
			add_settings_error( $this->plugin_name . '-handle', 'handle', __( 'Nonce is incorrect', 'neznam-atproto-share' ) );
			return $handle;
		}
		if ( ! isset( $_POST[ $this->plugin_name . '-url' ] ) || ! $handle ) {
			add_settings_error( $this->plugin_name . '-handle', 'handle', __( 'Required fields are empty', 'neznam-atproto-share' ) );
			return $handle;
		}
		$logic = new Neznam_Atproto_Share_Logic( $this->plugin_name, $this->version );
		$logic->set_url( sanitize_text_field( wp_unslash( $_POST[ $this->plugin_name . '-url' ] ) ) );
		$logic->set_handle( $handle );
		if ( ! $logic->did_request() ) {
			add_settings_error( $this->plugin_name . '-handle', 'handle', __( 'Handle is incorrect', 'neznam-atproto-share' ) );
		}
		return $handle;
	}

	/**
	 * Validate password
	 *
	 * @param string $password Password to validate.
	 *
	 * @return mixed
	 */
	public function check_password( $password ) {
		$nonce = isset( $_POST[ $this->plugin_name . '-nonce' ] ) ? sanitize_text_field( wp_unslash( $_POST[ $this->plugin_name . '-nonce' ] ) ) : '';
		if ( ! $nonce || ! wp_verify_nonce( $nonce, $this->plugin_name . '-save-settings' ) ) {
			add_settings_error( $this->plugin_name . '-secret', 'secret', __( 'Nonce is incorrect', 'neznam-atproto-share' ) );
			return $password;
		}
		if ( ! isset( $_POST[ $this->plugin_name . '-url' ] ) || ! isset( $_POST[ $this->plugin_name . '-handle' ] ) || ! $password ) {
			add_settings_error( $this->plugin_name . '-secret', 'secret', __( 'Required fields are empty', 'neznam-atproto-share' ) );
			return $password;
		}
		$logic = new Neznam_Atproto_Share_Logic( $this->plugin_name, $this->version );
		$logic->set_url( sanitize_text_field( wp_unslash( $_POST[ $this->plugin_name . '-url' ] ) ) );
		$logic->set_handle( get_option( sanitize_text_field( wp_unslash( $_POST[ $this->plugin_name . '-handle' ] ) ) ) );
		$did = $logic->did_request();
		if ( ! $logic->auth_request( $did, $password ) ) {
			add_settings_error( $this->plugin_name . '-secret', 'secret', __( 'Password is incorrect', 'neznam-atproto-share' ) );
		}
		return $password;
	}

	/**
	 * On save post save the information for reposting on atproto.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	public function edit_post( $post_id ) {
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

		if ( get_post_status( $post_id ) === 'publish' ) {
			$use_cron = get_option( $this->plugin_name . '-use-cron' );
			$share_info = get_post_meta($post_id, $this->plugin_name . '-uri', true);
			if ( ! $use_cron && ! $share_info ) {
				$logic = new Neznam_Atproto_Share_Logic( $this->plugin_name, $this->version );
				$logic->post_message( get_post( $post_id ) );
			}
		}
	}

	/**
	 * Add meta box to post
	 *
	 * @return void
	 */
	public function add_meta_box() {
		add_meta_box(
			$this->plugin_name . '-meta-box',
			__( 'Atproto Share', 'neznam-atproto-share' ),
			array(
				$this,
				'render_meta_box',
			),
			'post',
			'side',
			'high',
		);
	}

	/**
	 * Render meta box
	 *
	 * @return void
	 */
	public function render_meta_box() {
		$uri = get_post_meta( get_the_ID(), $this->plugin_name . '-uri', true );
		if ($uri) {
			$uri = explode('/', $uri);
			$id = array_pop($uri);
			$handle = get_option($this->plugin_name . '-handle');
			?>
			<p><?php esc_html_e( 'Published on Atproto', 'neznam-atproto-share' ); ?></p>
			<p><a href="<?php echo esc_url('https://bsky.app/profile/' . $handle . '/post/' . $id); ?>" target="_blank" rel="noreferrer"><?php esc_html_e('View on Bluesky', 'neznam-atproto-share'); ?></a></p>
			<?php
			return;
		}
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
			for="<?php echo esc_html( $this->plugin_name ); ?>-should-publish"><?php esc_html_e( 'Publish on Atproto?', 'neznam-atproto-share' ); ?></label>
		<p class="howto"><?php esc_html_e( 'Publishes post to Atproto network.', 'neznam-atproto-share' ); ?></p>
		<label
			for="<?php echo esc_html( $this->plugin_name ); ?>-text-to-publish"><?php esc_html_e( 'Text to publish', 'neznam-atproto-share' ); ?></label>
		<input
			id="<?php echo esc_html( $this->plugin_name ); ?>-text-to-publish"
			name="<?php echo esc_html( $this->plugin_name ); ?>-text-to-publish"
			type="text"
			value="<?php echo esc_html( $text_to_publish ); ?>"/>
		<p class="howto"><?php esc_html_e( 'Text to add as status', 'neznam-atproto-share' ); ?></p>
		<?php wp_nonce_field( 'save-should-publish', $this->plugin_name . 'should-publish-nonce' ); ?>
		<?php
	}

	/**
	 * Create cron schedule
	 *
	 * @param mixed $schedules Cron schedules.
	 *
	 * @return mixed
	 */
	public function cron_schedule( $schedules ) {
		$schedules[ $this->plugin_name . '-every-minute' ] = array(
			'interval' => 60,
			'display'  => __( 'Every minute', 'neznam-atproto-share' ),
		);

		return $schedules;
	}
}
