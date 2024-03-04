<?php
/**
 * Define the logic functionality.
 *
 * @package   Neznam_Atproto_Share
 * @subpackage Neznam_Atproto_Share/includes
 * @link      https://www.neznam.hr
 * @since      1.0.0
 */

/**
 * Define the logic functionality.
 *
 * @since      1.0.0
 * @package    Neznam_Atproto_Share
 * @subpackage Neznam_Atproto_Share/includes
 * @author     Marko Banušić <mbanusic@gmail.com>
 */
class Neznam_Atproto_Share_Logic {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private string $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private string $version;

	/**
	 * URL of the server.
	 *
	 * @var string $url URL of the server.
	 */
	private string $url = '';

	/**
	 * Handle of the user.
	 *
	 * @var string $handle Handle of the user.
	 */
	private string $handle = '';

	/**
	 * Password of the user.
	 *
	 * @var string $app_pass Password of the user.
	 */
	private string $app_pass = '';

	/**
	 * DID of the user.
	 *
	 * @var string $did DID of the user.
	 */
	private string $did = '';

	/**
	 * Access token.
	 *
	 * @var string $access_token Access token of the user.
	 */
	private string $access_token = '';

	/**
	 * Refresh token.
	 *
	 * @var string $refresh_token Refresh token of the user.
	 */
	private string $refresh_token = '';

	/**
	 * Constructor.
	 *
	 * @param string $plugin_name Name of the plugin.
	 * @param string $version Version of the plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name   = $plugin_name;
		$this->version       = $version;
		$this->access_token  = get_option( $this->plugin_name . '-access-token' );
		$this->refresh_token = get_option( $this->plugin_name . '-refresh-token' );
	}

	/**
	 * Internally set URL of the server.
	 *
	 * @param string $url URL of the server.
	 *
	 * @return void
	 */
	public function set_url( $url = null ) {
		if ( ! $url ) {
			$url = get_option( $this->plugin_name . '-url' );
		}
		$this->url = $url;
	}

	/**
	 * Internally set handle.
	 *
	 * @param string $handle Handle of the user.
	 *
	 * @return void
	 */
	public function set_handle( $handle = null ) {
		if ( ! $handle ) {
			$handle = get_option( $this->plugin_name . '-handle' );
		}
		$this->handle = $handle;
	}

	/**
	 * Make the DID request
	 *
	 * @return false|mixed
	 */
	public function did_request() {
		$body = wp_remote_get( trailingslashit( $this->url ) . 'xrpc/com.atproto.identity.resolveHandle?handle=' . $this->handle );
		if ( is_wp_error( $body ) ) {
			return false;
		}
		$body = json_decode( $body['body'], true );
		$did  = $body['did'];
		if ( $did ) {
			update_option( $this->plugin_name . '-did', $did );

			return $did;
		}
		return false;
	}

	/**
	 * Get the DID from handle
	 *
	 * @param string $handle Handle of the user.
	 *
	 * @return string
	 */
	private function get_did( $handle = '' ) {
		if ( ! $handle ) {
			$handle = get_option( $this->plugin_name . '-handle' );
		}
		$this->set_handle( $handle );
		$did = get_option( $this->plugin_name . '-did' );
		if ( ! $did ) {
			$did = $this->did_request();
		}
		return $did;
	}

	/**
	 * Run the cronjob to post the messages.
	 *
	 * @return void
	 */
	public function cron() {
		$use_cron = get_option( $this->plugin_name . '-use-cron' );
		if ( ! $use_cron ) {
			return;
		}
		$q = new WP_Query(
			array(
				'post_type'      => 'any',
				'post_status'    => 'publish',
				'posts_per_page' => - 1,
				'meta_query'     => array(
					array(
						'key'   => $this->plugin_name . '-should-publish',
						'value' => 1,
					),
					array(
						'key'     => $this->plugin_name . '-uri',
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);
		while ( $q->have_posts() ) {
			$q->the_post();
			$this->post_message( $q->post );
		}
	}

	/**
	 * Post the message to ATproto server.
	 *
	 * @param WP_Post $post Post to publish.
	 *
	 * @return void
	 */
	public function post_message( WP_Post $post ): void {
		$this->set_url();
		$this->did = $this->get_did();
		if ( ! $this->access_token ) {
			$this->authorize();
		}
		$image_path = get_attached_file( get_post_thumbnail_id( $post->ID ) );
		$blob       = null;
		if ( $image_path ) {
			$blob = $this->upload_blob( $image_path );
		}
		$text_to_publish = get_post_meta( get_the_ID(), $this->plugin_name . '-text-to-publish', true );

		$locale = str_replace( '_', '-', get_locale() );

		$body = array(
			'collection' => 'app.bsky.feed.post',
			'repo'       => $this->did,
			'record'     => array(
				'text'      => $text_to_publish ?? $post->post_title,
				'createdAt' => gmdate( 'c' ),
				'embed'     => array(
					'$type'    => 'app.bsky.embed.external',
					'external' => array(
						'uri'         => get_the_permalink( $post ),
						'title'       => $post->post_title,
						'description' => get_post_meta( $post->ID, 'subtitle', true ),
					),
				),
				'langs'     => array( $locale ),
			),
		);
		if ( $blob ) {
			$body['record']['embed']['external']['thumb'] = $blob;
		}
		$body = wp_remote_post(
			trailingslashit( $this->url ) . 'xrpc/com.atproto.repo.createRecord',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->access_token,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
			)
		);
		if ( 200 !== $body['response']['code'] ) {
			// TODO: Log error.
			return;
		}
		$body = json_decode( $body['body'], true );
		if ( isset( $body['uri'] ) ) {
			$uri = $body['uri'];
			update_post_meta( $post->ID, $this->plugin_name . '-uri', $uri );
		}
	}

	/**
	 * Make the auth request with data.
	 *
	 * @param string $did DID of the user.
	 * @param string $password Password of the user.
	 *
	 * @return array|false
	 */
	public function auth_request( $did, $password ) {
		$body = wp_remote_post(
			trailingslashit( $this->url ) . 'xrpc/com.atproto.server.createSession',
			array(
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'identifier' => $did,
						'password'   => $password,
					)
				),
			)
		);
		if ( is_wp_error( $body ) || 200 !== $body['response']['code'] ) {
			return false;
		}
		$body = json_decode( $body['body'], true );
		return $body;
	}

	/**
	 * Get token from handle and password.
	 *
	 * @return void
	 */
	private function authorize() {
		$this->handle   = get_option( $this->plugin_name . '-handle' );
		$this->app_pass = get_option( $this->plugin_name . '-secret' );
		if ( $this->refresh_token ) {
			$this->refresh_token();
			return;
		}
		if ( ! $this->did ) {
			$this->did = $this->get_did( $this->handle );
		}
		$body = $this->auth_request( $this->did, $this->app_pass );
		if ( ! $body ) {
			return;
		}
		$this->access_token  = $body['accessJwt'];
		$this->refresh_token = $body['refreshJwt'];
		update_option( $this->plugin_name . '-access-token', $this->access_token );
		update_option( $this->plugin_name . '-refresh-token', $this->refresh_token );
	}

	/**
	 * Get new token from refresh token.
	 *
	 * @return void
	 */
	private function refresh_token() {
		$body = wp_remote_post(
			trailingslashit( $this->url ) . 'xrpc/com.atproto.server.refreshSession',
			array(
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'refreshJwt' => $this->refresh_token,
					)
				),
			)
		);
		if ( 200 !== $body['response']['code'] ) {
			$this->refresh_token = '';
			$this->authorize();
			return;
		}
		$body                = json_decode( $body['body'], true );
		$this->access_token  = $body['accessJwt'];
		$this->refresh_token = $body['refreshJwt'];
		update_option( $this->plugin_name . '-access-token', $this->access_token );
		update_option( $this->plugin_name . '-refresh-token', $this->refresh_token );
	}

	/**
	 * Upload the image to the server.
	 *
	 * @param string $path Path of the file.
	 *
	 * @return array|mixed
	 */
	private function upload_blob( $path ) {
		if ( ! $path ) {
			return array();
		}
		global $wp_filesystem;
		require_once ABSPATH . '/wp-admin/includes/file.php';
		WP_Filesystem();
		if ( ! $wp_filesystem->exists( $path ) ) {
			return array();
		}

		$file = $wp_filesystem->get_contents( $path );
		$body = wp_remote_post(
			trailingslashit( $this->url ) . 'xrpc/com.atproto.repo.uploadBlob',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->access_token,
					'Content-Type'  => 'image/*',
				),
				'body'    => $file,
			)
		);
		if ( 200 !== $body['response']['code'] ) {
			$this->access_token = '';
			$this->refresh_token();

			return $this->upload_blob( $path );
		}
		$body = json_decode( $body['body'], true );

		return $body['blob'];
	}

	/**
	 * Registers CLI command to enable shareing through CLI.
	 *
	 * @return void
	 */
	public function cli() {
		WP_CLI::add_command( $this->plugin_name, $this );
	}
}
