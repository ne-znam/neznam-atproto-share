<?php
/**
 * Admin class
 *
 * @package   Neznam_Atproto_Share
 * @subpackage Neznam_Atproto_Share/admin
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
	 * The Neznam_Atproto_Share_Logic object.
	 *
	 * @since    2.1.0
	 * @access   private
	 * @var      \Neznam_Atproto_Share_Logic $plugin_share The object for shared logic.
	 */
	private $plugin_share;

	/**
	 * The Neznam_Atproto_Share_Admin_Metabox object.
	 *
	 * @since    2.1.0
	 * @access   private
	 * @var      \Neznam_Atproto_Share_Admin_Metabox $plugin_share The object for metabox-related functions.
	 */
	private $plugin_metabox;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 * @param object $plugin_share The Neznam_Atproto_Share_Logic object.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $plugin_name, $version, $plugin_share ) {

		$this->plugin_name    = $plugin_name;
		$this->version        = $version;
		$this->plugin_share   = $plugin_share;
		$this->plugin_metabox = new Neznam_Atproto_Share_Admin_Metabox( $this->plugin_name, $this->version, $this->plugin_share, $this );
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
		$url = esc_url( add_query_arg( 'page', $this->plugin_name, get_admin_url( null, 'options-general.php' ) ) );
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
	 * Injects the "ATProto Share" link into the options submenu.
	 *
	 * @since 2.0.0
	 */
	public function add_page() {
		add_options_page( 'ATProto Share', 'ATProto Share', 'manage_options', 'neznam-atproto-share', array( $this, 'create_admin_page' ) );
	}

	/**
	 * Generates the options submenu page.
	 *
	 * @since 2.0.0
	 */
	public function create_admin_page() {
		?>
		<div class="wrap">
			<h2>ATProto Share Settings</h2>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'neznam-atproto-share' );
				do_settings_sections( 'neznam-atproto-share' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Add all settings and attach metabox management.
	 *
	 * @since    1.0.0
	 */
	public function admin_init() {
		if ( get_option( $this->plugin_name . '-did' ) ) {
			add_action( 'admin_enqueue_scripts', array( $this->plugin_metabox, 'meta_box_scripts' ) );
			add_action( 'add_meta_boxes', array( $this->plugin_metabox, 'add_meta_box' ) );
			add_action( 'wp_ajax_' . $this->plugin_name, array( $this->plugin_metabox, 'ajax_handler' ) );
		}

		register_setting(
			'neznam-atproto-share',
			$this->plugin_name . '-url',
			array(
				'sanitize_callback' => 'esc_url',
				'default'           => 'https://bsky.social/',
			)
		);
		register_setting(
			'neznam-atproto-share',
			$this->plugin_name . '-handle',
			array(
				'sanitize_callback' => array( $this, 'check_handle' ),
			)
		);
		register_setting(
			'neznam-atproto-share',
			$this->plugin_name . '-secret',
			array(
				'sanitize_callback' => array( $this, 'check_password' ),
			)
		);
		register_setting(
			'neznam-atproto-share',
			$this->plugin_name . '-default',
			array(
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '1',
			)
		);
		register_setting(
			'neznam-atproto-share',
			$this->plugin_name . '-use-cron',
			array(
				'sanitize_callback' => array( $this, 'adjust_cron' ),
				'default'           => '0',
			)
		);
		register_setting(
			'neznam-atproto-share',
			$this->plugin_name . '-post-format',
			array(
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'post_title',
			)
		);
		register_setting(
			'neznam-atproto-share',
			$this->plugin_name . '-comment-override',
			array(
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);
		register_setting(
			'neznam-atproto-share',
			$this->plugin_name . '-comment-disable',
			array(
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);
		register_setting(
			'neznam-atproto-share',
			$this->plugin_name . '-debug-level',
			array(
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => 'ERROR',
			)
		);

		add_settings_section(
			$this->plugin_name . '-section',
			'Share settings',
			function () {
				echo '<p>' .
				esc_html__( 'Enter your server information to enable posting.', 'neznam-atproto-share' ) .
				' (' .
				wp_kses(
					__( 'Enjoying this plugin? Please <a href="https://wordpress.org/support/plugin/neznam-atproto-share/reviews/#new-post" target="_blank">leave a review</a> to support its development.', 'neznam-atproto-share' ),
					array(
						'a' => array(
							'href'   => array(),
							'target' => array(),
						),
					)
				) .
				')</p>';
				wp_nonce_field( $this->plugin_name . '-save-settings', $this->plugin_name . '-nonce' );
			},
			'neznam-atproto-share',
			array(
				'before_section' => '<hr id="' . esc_html( $this->plugin_name ) . '"/>',
			)
		);

		add_settings_field(
			$this->plugin_name . '-url',
			'ATProto URL',
			function () {
				?>
				<input type="text" name="<?php echo esc_html( $this->plugin_name ); ?>-url" value="<?php echo esc_html( get_option( $this->plugin_name . '-url' ) ); ?>" /><br>
				<small><?php esc_html_e( 'Enter the URL of your provider or leave as is for BlueSky', 'neznam-atproto-share' ); ?></small>
				<?php
			},
			'neznam-atproto-share',
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
			'neznam-atproto-share',
			$this->plugin_name . '-section'
		);

		add_settings_field(
			$this->plugin_name . '-secret',
			'Secret',
			function () {
				?>
				<input type="password" name="<?php echo esc_html( $this->plugin_name ); ?>-secret" value="<?php echo esc_html( get_option( $this->plugin_name . '-secret' ) ); ?>" /><br>
				<small>
					<?php
					echo wp_kses(
						__( 'Enter app password. If using BlueSky visit: <a href="https://bsky.app/settings/app-passwords" target="_blank">App passwords</a>', 'neznam-atproto-share' ),
						array(
							'a' => array(
								'href'   => array(),
								'target' => array(),
							),
						)
					);
					?>
				</small>
				<?php
			},
			'neznam-atproto-share',
			$this->plugin_name . '-section'
		);

		add_settings_field(
			$this->plugin_name . '-default',
			'Default to share',
			function () {
				?>
				<input type="checkbox" name="<?php echo esc_html( $this->plugin_name ); ?>-default" value="1" <?php checked( 1, get_option( $this->plugin_name . '-default' ), true ); ?> />
				<small><?php esc_html_e( 'Enable this option to always push posts and publish.', 'neznam-atproto-share' ); ?></small>
				<?php
			},
			'neznam-atproto-share',
			$this->plugin_name . '-section'
		);

		add_settings_field(
			$this->plugin_name . '-use-cron',
			'Use cron for sharing',
			function () {
				?>
				<input type="checkbox" name="<?php echo esc_html( $this->plugin_name ); ?>-use-cron" value="1" <?php checked( 1, get_option( $this->plugin_name . '-use-cron' ), true ); ?> />
				<small><?php esc_html_e( 'Check this if you have trouble publishing posts. This will use cronjob to publish.', 'neznam-atproto-share' ); ?></small>
				<?php
			},
			'neznam-atproto-share',
			$this->plugin_name . '-section'
		);

		add_settings_field(
			$this->plugin_name . '-post-format',
			'Post Format',
			function () {
				$cur_format = get_option( $this->plugin_name . '-post-format' );
				if ( empty( $cur_format ) ) {
					$cur_format = 'post_title';
				}
				$formats = array(
					'post_title'             => __( 'Post Title', 'neznam-atproto-share ' ),
					'post_excerpt'           => __( 'Post Excerpt', 'neznam-atproto-share ' ),
					'post_title_and_excerpt' => __( 'Post Title: Post Excerpt', 'neznam-atproto-share ' ),
				);
				?>
				<select name="<?php echo esc_html( $this->plugin_name ); ?>-post-format">
				<?php foreach ( $formats as $key => $value ) { ?>
					<option value="<?php echo esc_html( $key ); ?>"
												<?php
												if ( $cur_format === $key ) {
													echo 'selected="selected"';
												}
												?>
					><?php echo esc_html( $value ); ?></option>
				<?php } ?>
				</select>
				<small>
				<?php
				esc_html_e( 'If the "Text to publish" field is blank, this information will populate the post.', 'neznam-atproto-share' );
				?>
				</small>
				<?php
			},
			'neznam-atproto-share',
			$this->plugin_name . '-section'
		);

		add_settings_field(
			$this->plugin_name . '-debug-level',
			'Debug Level',
			function () {
				$cur_level = get_option( $this->plugin_name . '-debug-level' );
				if ( empty( $cur_level ) ) {
					$cur_level = 'ERROR';
				}
				$levels = array( 'FATAL', 'ERROR', 'WARN', 'INFO', 'DEBUG' );
				?>
				<select name="<?php echo esc_html( $this->plugin_name ); ?>-debug-level">
				<?php foreach ( $levels as $level ) { ?>
					<option value="<?php echo esc_html( $level ); ?>"
												<?php
												if ( $cur_level === $level ) {
													echo 'selected="selected"';}
												?>
					><?php echo esc_html( ucfirst( strtolower( $level ) ) ); ?></option>
				<?php } ?>
				</select>
				<small>
				<?php
				echo wp_kses(
					__( "Adjusts the amount of details written to <a href='https://developer.wordpress.org/advanced-administration/debug/debug-wordpress' target='_blank'>WordPress' Debug system</a>.", 'neznam-atproto-share' ),
					array(
						'a' => array(
							'href'   => array(),
							'target' => array(),
						),
					)
				);
				?>
						</small>
				<?php
			},
			'neznam-atproto-share',
			$this->plugin_name . '-section'
		);

		add_settings_section(
			$this->plugin_name . '-comments-section',
			'Comments settings',
			function () {
				echo '<p>' .
					esc_html__( 'Enable comments.', 'neznam-atproto-share' ) .
					'</p>';
			},
			'neznam-atproto-share',
		);

		add_settings_field(
			$this->plugin_name . '-comment-override',
			'Use Bluesky Replies as Comments',
			function () {
				?>
				<input type="checkbox" name="<?php echo esc_html( $this->plugin_name ); ?>-comment-override" value="1" <?php checked( '1', get_option( $this->plugin_name . '-comment-override' ), true ); ?> />
				<small><?php esc_html_e( 'For posts published to Bluesky, replace WordPress comments with Bluesky reply threads.', 'neznam-atproto-share' ); ?></small>
				<?php
			},
			'neznam-atproto-share',
			$this->plugin_name . '-comments-section'
		);

		add_settings_field(
			$this->plugin_name . '-comment-disable',
			'WordPress Comment Disable',
			function () {
				?>
				<input type="checkbox" name="<?php echo esc_html( $this->plugin_name ); ?>-comment-disable" value="1" <?php checked( '1', get_option( $this->plugin_name . '-comment-disable' ), true ); ?> />
				<small><?php esc_html_e( 'If "Use Bluesky Replies as Comments" is enabled but post is not published to Bluesky, enabling this will hide default WordPress comments.', 'neznam-atproto-share' ); ?></small>
				<?php
			},
			'neznam-atproto-share',
			$this->plugin_name . '-comments-section'
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
		$prev_handle = get_option( $this->plugin_name . '-handle' );
		if ( $handle === $prev_handle ) {
			return $prev_handle;
		}
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
		$logic->set_url( sanitize_url( wp_unslash( $_POST[ $this->plugin_name . '-url' ] ) ) );
		$logic->set_handle( $handle );
		if ( ! $logic->did_request() ) {
			add_settings_error( $this->plugin_name . '-handle', 'handle', __( 'Handle is incorrect - ', 'neznam-atproto-share' ) . esc_html( $handle ) );
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
		$prev_password = get_option( $this->plugin_name . '-secret' );
		if ( $password === $prev_password ) {
			return $prev_password;
		}
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
		$logic->set_url( sanitize_url( wp_unslash( $_POST[ $this->plugin_name . '-url' ] ) ) );
		$logic->set_handle( get_option( sanitize_text_field( wp_unslash( $_POST[ $this->plugin_name . '-handle' ] ) ) ) );
		$did = $logic->did_request();
		if ( ! $logic->auth_request( $did, $password ) ) {
			add_settings_error( $this->plugin_name . '-secret', 'secret', __( 'Password is incorrect', 'neznam-atproto-share' ) );
		}
		return $password;
	}

	/**
	 * Determine if the cron should be enabled or disabled
	 *
	 * @param string $cron_setting Checkbox for cron being enabled.
	 *
	 * @return mixed
	 */
	public function adjust_cron( $cron_setting ) {
		$cron_setting = isset( $_POST[ $this->plugin_name . '-use-cron' ] ) ? sanitize_text_field( wp_unslash( $_POST[ $this->plugin_name . '-use-cron' ] ) ) : false;
		$nonce        = isset( $_POST[ $this->plugin_name . '-nonce' ] ) ? sanitize_text_field( wp_unslash( $_POST[ $this->plugin_name . '-nonce' ] ) ) : '';
		if ( ! $nonce || ! wp_verify_nonce( $nonce, $this->plugin_name . '-save-settings' ) ) {
			add_settings_error( $this->plugin_name . '-secret', 'secret', __( 'Nonce is incorrect', 'neznam-atproto-share' ) );
			return $cron_setting;
		}
		$timestamp = wp_next_scheduled( 'neznam-atproto-share_cron' );
		if ( empty( $cron_setting ) ) {
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, 'neznam-atproto-share_cron' );
			}
		} elseif ( ! $timestamp ) {
				wp_schedule_event( time(), 'neznam-atproto-share-every-minute', 'neznam-atproto-share_cron' );
		}
		return $cron_setting;
	}

	/**
	 * On save post save the information for reposting on atproto.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post The Post itself.
	 *
	 * @return void
	 */
	public function save_post( $post_id, $post ) {
		if ( 'publish' === get_post_status( $post_id ) ) {
			if ( isset( $_POST[ $this->plugin_name . '-should-publish' ] ) ) {
				if ( wp_verify_nonce( $_POST[ $this->plugin_name . '-default-nonce' ], 'default' ) ) {
					$should_publish  = ! empty( $_POST[ $this->plugin_name . '-should-publish' ] ) ? 1 : 0;
					$text_to_publish = isset( $_POST[ $this->plugin_name . '-text-to-publish' ] ) ? sanitize_text_field( wp_unslash( $_POST[ $this->plugin_name . '-text-to-publish' ] ) ) : '';
					update_post_meta( $post_id, $this->plugin_name . '-should-publish', $should_publish );
					update_post_meta( $post_id, $this->plugin_name . '-text-to-publish', $text_to_publish );
				}
			}
			$this->publish_post( $post );
		}
	}

	/**
	 * On save post save the information for reposting on atproto.
	 *
	 * @param WP_Post $post The Post itself.
	 *
	 * @return void
	 */
	public function publish_post( $post ) {
		if ( empty( $post->ID ) ) {
			return;
		}

		if ( get_post_status( $post->ID ) !== 'publish' ) {
			return;
		}

		$use_cron = get_option( $this->plugin_name . '-use-cron' );
		if ( $use_cron ) {
			return;
		}

		$share_info = get_post_meta( $post->ID, $this->plugin_name . '-uri', true );
		if ( $share_info ) {
			return;
		}

		$should_publish = get_post_meta( $post->ID, $this->plugin_name . '-should-publish', true );

		if ( $should_publish && '0' !== $should_publish ) {
			$logic = new Neznam_Atproto_Share_Logic( $this->plugin_name, $this->version );
			$logic->post_message( $post );
		}
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
