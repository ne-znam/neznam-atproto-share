<?php
/**
 * Admin Metabox class
 *
 * @package   Neznam_Atproto_Share
 * @subpackage Neznam_Atproto_Share/admin
 * @link      https://www.neznam.hr
 * @since      2.1.0
 */

/**
 * The metabox-related functionality of the admin area.
 *
 * @package    Neznam_Atproto_Share
 * @subpackage Neznam_Atproto_Share/admin
 * @author     Marko Banušić <mbanusic@gmail.com>
 */
class Neznam_Atproto_Share_Admin_Metabox {

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
	 * The Neznam_Atproto_Share_Admin object.
	 *
	 * @since    2.1.0
	 * @access   private
	 * @var      \Neznam_Atproto_Share_Admin $plugin_admin The object for admin functionality.
	 */
	private $plugin_admin;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 * @param object $plugin_share The Neznam_Atproto_Share_Logic object.
	 * @param object $plugin_admin The Neznam_Atproto_Share_Admin object.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $plugin_name, $version, $plugin_share, $plugin_admin ) {

		$this->plugin_name  = $plugin_name;
		$this->version      = $version;
		$this->plugin_share = $plugin_share;
		$this->plugin_admin = $plugin_admin;
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
	 * Injects the scripts needed by the meta box.
	 *
	 * @return null
	 * @since 2.1.0
	 */
	public function meta_box_scripts() {
		$screen = get_current_screen();
		if ( ! is_object( $screen ) || 'post' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_script(
			$this->plugin_name . '-meta-box-script',
			plugin_dir_url( __FILE__ ) . 'meta-boxes/js/admin.js',
			array( 'jquery' ),
			$this->version,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

		wp_localize_script(
			$this->plugin_name . '-meta-box-script',
			str_replace( '-', '_', $this->plugin_name ) . '_meta_box_object',
			$this->get_ajax_data()
		);
	}

	/**
	 * Render meta box
	 *
	 * @return void
	 */
	public function render_meta_box() {
		?>
		<p id="<?php echo esc_html( $this->plugin_name ); ?>-message" class="error-message style="display: none"></p>
		<div id="<?php echo esc_html( $this->plugin_name ); ?>-published-post" style="display: none">
			<p><?php esc_html_e( 'Published on Atproto', 'neznam-atproto-share' ); ?></p>
			<p><a href="#" class="ext-link" target="_blank" rel="noreferrer"><?php esc_html_e( 'View on Bluesky', 'neznam-atproto-share' ); ?></a></p>
			<hr>
			<p class="howto"><?php esc_html_e( 'Disassociate this entry with the published Atproto post.', 'neznam-atproto-share' ); ?></p>
			<button type="button" class="update">Disassociate Post</button>
			<span class="spinner"></span>
		</div>
		<div id="<?php echo esc_html( $this->plugin_name ); ?>-publish-post" style="display: none">
			<input type="hidden" name="<?php echo esc_html( $this->plugin_name ); ?>-default-nonce" value="<?php echo esc_attr( wp_create_nonce( 'default' ) ); ?>" />
			<input
				id="<?php echo esc_html( $this->plugin_name ); ?>-should-publish"
				name="<?php echo esc_html( $this->plugin_name ); ?>-should-publish"
				type="checkbox"
				value="1">
			<label
				for="<?php echo esc_html( $this->plugin_name ); ?>-should-publish"><?php esc_html_e( 'Publish on Atproto?', 'neznam-atproto-share' ); ?></label>
			<p class="howto"><?php esc_html_e( 'Publishes post to Atproto network.', 'neznam-atproto-share' ); ?></p>
			<label
				for="<?php echo esc_html( $this->plugin_name ); ?>-text-to-publish"><?php esc_html_e( 'Text to publish', 'neznam-atproto-share' ); ?></label>
			<input
				id="<?php echo esc_html( $this->plugin_name ); ?>-text-to-publish"
				name="<?php echo esc_html( $this->plugin_name ); ?>-text-to-publish"
				type="text"
				value=""/>
			<p class="howto">
				<?php esc_html_e( 'Text to add as status. If blank, will use: ', 'neznam-atproto-share' ); ?>
				<code>
				<?php
				$cur_format = get_option( $this->plugin_name . '-post-format' );
				if ( empty( $cur_format ) ) {
					$cur_format = 'post_title';
				}
				if ( 'post_title' === $cur_format ) {
					echo esc_html( strtolower( __( 'Post Title', 'neznam-atproto-share ' ) ) );
				}
				if ( 'post_excerpt' === $cur_format ) {
					echo esc_html( strtolower( __( 'Post Excerpt', 'neznam-atproto-share ' ) ) );
				}
				if ( 'post_title_and_excerpt' === $cur_format ) {
					echo esc_html( strtolower( __( 'Post Title: Post Excerpt', 'neznam-atproto-share ' ) ) );
				}
				?>
				</code>
			</p>
			<button type="button" class="update">Update</button>
			<span class="spinner"></span>
			<hr>
			<a href="#" id="<?php echo esc_html( $this->plugin_name ); ?>-switch-link-post" data-mode="link" style="display:none"><small><?php esc_html_e( 'Already posted on network? Link to the post.', 'neznam-atproto-share' ); ?></small></a>
		</div>
		<div id="<?php echo esc_html( $this->plugin_name ); ?>-link-post" style="display: none">
			<label
				for="<?php echo esc_html( $this->plugin_name ); ?>-published-rkey"><?php esc_html_e( 'Record key of published post', 'neznam-atproto-share' ); ?></label>
			<input
				id="<?php echo esc_html( $this->plugin_name ); ?>-published-rkey"
				name="<?php echo esc_html( $this->plugin_name ); ?>-published-rkey"
				type="text">
			<p class="howto"><?php esc_html_e( 'Record key of the post. Typically a 13-character string after post/ in the URI.', 'neznam-atproto-share' ); ?></p>
			<button type="button" class="update">Update</button>
			<span class="spinner"></span>
			<hr>
			<p><a href="#" id="<?php echo esc_html( $this->plugin_name ); ?>-switch-publish-post" data-mode="publish"><small><?php esc_html_e( 'Switch back to publish post.', 'neznam-atproto-share' ); ?></small></a></p>
		</div>
		<?php
	}

	/**
	 * Handles the various AJAX actions requested by the meta box
	 *
	 * @since 2.1.0
	 */
	public function ajax_handler() {
		if ( ! check_ajax_referer( $this->plugin_name . 'update-post-nonce', 'nonce', false ) ) {
			wp_send_json_error(
				array(
					'error' => esc_html__( 'Invalid security token sent.', 'neznam-atproto-share' ),
				)
			);
		}

		$post_id = isset( $_POST['post_id'] ) ? sanitize_text_field( wp_unslash( $_POST['post_id'] ) ) : '';

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error(
				array(
					'error' => esc_html__( 'Permission denied to edit post.', 'neznam-atproto-share' ),
				)
			);
		}

		$subaction = isset( $_POST['subaction'] ) ? sanitize_text_field( wp_unslash( $_POST['subaction'] ) ) : '';
		if ( 'disassociate' === $subaction ) {
			delete_post_meta( $post_id, $this->plugin_name . '-uri' );
			delete_post_meta( $post_id, $this->plugin_name . '-http-uri' );
			wp_send_json_success( $this->get_ajax_data( $post_id ) );
		} elseif ( 'publish' === $subaction ) {
			$skip_post       = ! empty( $_POST['skip_post'] ) ? 1 : 0;
			$should_publish  = ! empty( $_POST['publish'] ) ? 1 : 0;
			$text_to_publish = isset( $_POST['text'] ) ? sanitize_text_field( wp_unslash( $_POST['text'] ) ) : '';
			update_post_meta( $post_id, $this->plugin_name . '-should-publish', $should_publish );
			update_post_meta( $post_id, $this->plugin_name . '-text-to-publish', $text_to_publish );
			if ( $skip_post || ! $should_publish || 'publish' !== get_post_status( $post_id ) ) {
				wp_send_json_success( $this->get_ajax_data( $post_id ) );
			}
			$this->plugin_admin->publish_post( get_post( $post_id ) );
			$share_info = get_post_meta( $post_id, $this->plugin_name . '-uri', true );
			if ( ! empty( $share_info ) ) {
				wp_send_json_success( $this->get_ajax_data( $post_id ) );
			} else {
				wp_send_json_error(
					array(
						'error' => esc_html__( 'Failed to publish new post.', 'neznam-atproto-share' ),
					)
				);
			}
		} elseif ( 'link' === $subaction ) {
			$rkey = isset( $_POST['rkey'] ) ? sanitize_text_field( wp_unslash( $_POST['rkey'] ) ) : '';
			if ( $this->link_post( $post_id, $rkey ) ) {
				wp_send_json_success( $this->get_ajax_data( $post_id ) );
			} else {
				wp_send_json_error(
					array(
						'error' => esc_html__( 'Failed to link to existing post.', 'neznam-atproto-share' ),
					)
				);
			}
		} else {
			wp_send_json_error(
				array(
					'error' => esc_html__( 'Unrecognized AJAX action attempted.', 'neznam-atproto-share' ),
				)
			);
		}
	}

	/**
	 * Attempts to link the blog's post to the AtProto post
	 *
	 * @param string $post_id The post's ID.
	 * @param string $rkey Record key of the AtProto post.
	 *
	 * @return bool
	 * @since 2.1.0
	 */
	private function link_post( $post_id, $rkey ) {
		$record = $this->plugin_share->record_request( $rkey );
		if ( ! empty( $record['uri'] ) ) {
			update_post_meta( $post_id, $this->plugin_name . '-uri', $record['uri'] );
			$handle   = get_option( $this->plugin_name . '-handle' );
			$http_uri = 'https://bsky.app/profile/' . $handle . '/post/' . $rkey;
			update_post_meta( $post_id, $this->plugin_name . '-http-uri', $http_uri );
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Generates the data used to render the meta box.
	 *
	 * @param mixed $post_id The post's ID, defaults to result of `get_the_ID`.
	 *
	 * @return array
	 * @since 2.1.0
	 */
	private function get_ajax_data( $post_id = false ) {
		if ( false === $post_id ) {
			$post_id = get_the_ID();
		}
		$url = get_post_meta( $post_id, $this->plugin_name . '-http-uri', true );
		if ( ! $url ) {
			$uri = get_post_meta( $post_id, $this->plugin_name . '-uri', true );
			if ( $uri ) {
				$uri    = explode( '/', $uri );
				$id     = array_pop( $uri );
				$handle = get_option( $this->plugin_name . '-handle' );
				$url    = 'https://bsky.app/profile/' . $handle . '/post/' . $id;
				update_post_meta( $post_id, $this->plugin_name . '-http-uri', $url );
			}
		}

		$should_publish = get_post_meta( $post_id, $this->plugin_name . '-should-publish', true );

		if ( false === $should_publish || '' === $should_publish ) {
			$should_publish = get_option( $this->plugin_name . '-default' );
		}
		$published       = get_post_status( $post_id ) === 'publish';
		$text_to_publish = get_post_meta( $post_id, $this->plugin_name . '-text-to-publish', true );
		return array(
			'url'             => admin_url( 'admin-ajax.php' ),
			'nonce'           => wp_create_nonce( $this->plugin_name . 'update-post-nonce' ),
			'published'       => $published,
			'text_to_publish' => $text_to_publish,
			'should_publish'  => $should_publish,
			'atproto_url'     => $url,
		);
	}
}
