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
	 * Has a refresh been attempted.
	 *
	 * @var bool $refresh_attempted Track if an attempt has been made to refresh the token.
	 */
	private bool $refresh_attempted = false;

	/**
	 * Debug level that is worth writing to log.
	 *
	 * @var string $debug_level Level of debug worth logging.
	 */
	private string $debug_level = '';

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
		if ( empty( $url ) ) {
			$this->log( 'DEBUG', 'Option value of URL not present. Defaulting to https://bsky.social/' );
			$url = 'https://bsky.social/';
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
			$this->log( 'FATAL', $body );
			return false;
		}
		$response_body = $body['body'];
		$body          = json_decode( $body['body'], true );
		if ( $this->validate_did( $body['did'] ) ) {
			$this->log( 'DEBUG', 'Successfully validated DID.' );
			update_option( $this->plugin_name . '-did', $body['did'] );
			return $body['did'];
		}
		$this->log( 'ERROR', 'Received an invalid DID from resolve handle. Body of response: ' . esc_html( $response_body ) );
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
		$this->log( 'DEBUG', 'Running cron to auto-post recent published posts.' );
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
		$image_path = get_attached_file( get_post_thumbnail_id( $post->ID ) ); // TODO: Add option to select image and size.
		$blob       = null;
		if ( $image_path ) {
			$blob = $this->upload_blob( $image_path );
		}
		$text_to_publish = get_post_meta( get_the_ID(), $this->plugin_name . '-text-to-publish', true );
		if ( ! empty( $text_to_publish ) ) {
			$post_text = $text_to_publish;
		} else {
			$post_format = get_option( $this->plugin_name . '-post-format' );
			if ( ! empty( $post->post_excerpt ) && 'post_excerpt' === $post_format ) {
				$post_text = $post->post_excerpt;
			} elseif ( ! empty( $post->post_excerpt ) && 'post_title_and_excerpt' === $post_format ) {
				$post_text = $post->post_title . ': ' . $post->post_excerpt;
			} else {
				$post_text = $post->post_title;
			}
		}

		$locale = str_replace( '_', '-', get_locale() );

		$body = array(
			'collection' => 'app.bsky.feed.post',
			'repo'       => $this->did,
			'record'     => array(
				'text'      => $post_text,
				'createdAt' => gmdate( 'c' ),
				'embed'     => array(
					'$type'    => 'app.bsky.embed.external',
					'external' => array(
						'uri'         => get_the_permalink( $post ),
						'title'       => $post->post_title,
						'description' => $post->post_excerpt,
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
			$response = isset( $body['body'] ) ? json_decode( $body['body'], true ) : array();
			if ( ! $this->refresh_attempted && isset( $response['error'] ) && 'ExpiredToken' === $response['error'] ) {
				$this->log( 'INFO', 'When attempting to post, received response of expired token. Refreshing token.' );
				$this->refresh_token();
				$this->refresh_attempted = true;
				$this->post_message( $post );
				return;
			}
			$this->log( 'ERROR', 'Received a non-200 ( ' . esc_html( $body['response']['code'] ) . ')  response code from create record. Body of response: ' . esc_html( $body['body'] ) );
			return;
		}
		$response_body = $body['body'];
		$body          = json_decode( $response_body, true );
		if ( isset( $body['uri'] ) && $this->validate_at_uri( $body['uri'] ) ) {
			update_post_meta( $post->ID, $this->plugin_name . '-uri', $body['uri'] );
			$this->log( 'DEBUG', 'Record successfully created. URI is ' . esc_html( $body['uri'] ) );
		} else {
			$this->log( 'ERROR', 'Failed to create record. Response from server: ' . esc_html( $response_body ) );
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
		if ( is_wp_error( $body ) ) {
			$this->log( 'FATAL', $body );
			return false;
		}
		if ( 200 !== $body['response']['code'] ) {
			$this->log( 'ERROR', 'Received a non-200 ( ' . esc_html( $body['response']['code'] ) . ')  response code from create session. Body of response: ' . esc_html( $body['body'] ) );
			return false;
		}
		$response_body = $body['body'];
		$body          = json_decode( $response_body, true );
		if ( ! $this->validate_jwt( $body['accessJwt'] ) || ! $this->validate_jwt( $body['refreshJwt'] ) ) {
			$this->log( 'ERROR', 'Received invalid JWT tokens from create session. Body of response: ' . esc_html( $response_body ) );
			return false;
		}
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
		$this->log( 'DEBUG', 'JWT tokens successfully retrieved from auth request.' );
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
			$this->log( 'INFO', 'Received a non-200 ( ' . esc_html( $body['response']['code'] ) . ')  response code from refresh token. Attempting to reauthorize. Body of response: ' . esc_html( $body['body'] ) );
			$this->refresh_token = '';
			$this->authorize();
			return;
		}
		$body = json_decode( $body['body'], true );
		if ( ! $this->validate_jwt( $body['accessJwt'] ) || ! $this->validate_jwt( $body['refreshJwt'] ) ) {
			$this->log( 'WARN', 'Either the Access JWT or Refresh JWT are not valid. Attempting token refresh.' );
			$this->refresh_token = '';
			$this->authorize();
			return;
		}
		$this->log( 'DEBUG', 'JWT tokens successfully refreshed.' );
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
		$size = $wp_filesystem->size( $path );
		if ( $size > 1000000 ) {
			$this->log( 'WARN', 'File is too large to upload. Skipping.' );
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
			$this->log( 'INFO', 'Received a non-200 ( ' . esc_html( $body['response']['code'] ) . ')  response code from upload blob. Attempting token refresh. Body of response: ' . esc_html( $body['body'] ) );
			$this->access_token = '';
			$this->refresh_token();

			return $this->upload_blob( $path );
		}
		$body = json_decode( $body['body'], true );
		$this->log( 'DEBUG', 'Blob successfully uploaded.' );
		return $body['blob'];
	}

	/**
	 * Determine if the DID provided by the server is valid.
	 *
	 * @param string $did The did to be validated.
	 *
	 * @return bool If the $did matches the allowed spec.
	 */
	private function validate_did( $did ) {
		// Derived from DID Syntax published at https://atproto.com/specs/did.

		// Skip potential PHP regex DoS by confirming string length in under spec max (2kb).
		if ( strlen( $did ) > 2048 ) {
			$this->log( 'WARN', 'Received a DID of ' . strlen( $jwt ) . ', which should not happen.' );
			return false;
		}

		return ! ! preg_match( '/^did:[a-z]+:[a-zA-Z0-9._:%-]*[a-zA-Z0-9._-]$/', $did );
	}

	/**
	 * Determine if the at-proto URI provided by the server is valid.
	 *
	 * @param string $uri The URI to be validated.
	 *
	 * @return bool If the $uri matches the allowed spec.
	 */
	private function validate_at_uri( $uri ) {
		// Derived from URI Syntax published at https://atproto.com/specs/at-uri-scheme.

		// Skip potential PHP regex DoS by confirming string length in under spec max (8kb).
		if ( strlen( $uri ) > 8192 ) {
			$this->log( 'WARN', 'Received an AT URI of ' . strlen( $jwt ) . ', which should not happen.' );
			return false;
		}

		return ! ! preg_match( '@^at://(?:(did:[a-z]+:[a-zA-Z0-9._:%-]*[a-zA-Z0-9._-])|([a-zA-Z0-9.-]+\.[a-zA-Z]{2,}))(/([a-zA-Z0-9._-]+\.[a-zA-Z0-9._-]+)(/[a-zA-Z0-9._~-]+)?)?$@', $uri );
	}

	/**
	 * Determine if the JWT provided by the server is valid.
	 *
	 * @param string $jwt The JWT to be validated.
	 *
	 * @return bool If the $jwt is formatted properly.
	 */
	private function validate_jwt( $jwt ) {
		// Derived from URI Syntax published at https://atproto.com/specs/at-uri-scheme.

		// Assume any JWT longer than 16k is invalid because it won't be accepted in a HTTP header.
		if ( strlen( $jwt ) > 16384 ) {
			$this->log( 'WARN', 'Received a JWT of ' . strlen( $jwt ) . ', which should not happen.' );
			return false;
		}
		return ! ! preg_match( '/^[a-zA-Z0-9\-_]+?\.[a-zA-Z0-9\-_]+?\.([a-zA-Z0-9\-_]+)?$/', $jwt );
	}

	/**
	 * Debug logging for understanding upstraing interactions.
	 * NOTE: This requires WP_DEBUG to be enabled.
	 *
	 * @param string $log_level Must be either FATAL, ERROR, WARN, INFO or DEBUG.
	 * @param mixed  $message Output to be written to the error log.
	 */
	private function log( $log_level, $message ) {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		if ( '' === $this->debug_level ) {
			$this->debug_level = get_option( $this->plugin_name . '-debug-level' );
			if ( empty( $this->debug_level ) ) {
				$this->debug_level = 'ERROR';
			}
		}
		$message_level  = $this->level_mapping( $log_level );
		$required_level = $this->level_mapping( $this->debug_level );
		if ( $message_level > $required_level ) {
			return;
		}

		// phpcs:disable WordPress.PHP.DevelopmentFunctions
		$log_message = '[NeZnam ATProto Share] - ' . $log_level . ' - ';
		if ( is_string( $message ) ) {
			$log_message .= $message;
		} else {
			$log_message .= print_r( $message, true );
		}
		error_log( $log_message );
		// phpcs:enable
	}

	/**
	 * Helper function to cast log level to a numeric.
	 *
	 * @param string $log_level Must be either FATAL, ERROR, WARN, INFO or DEBUG.
	 *
	 * @return int Lower the number, higher the priority.
	 */
	private function level_mapping( $log_level ) {
		if ( 'FATAL' === $log_level ) {
			return 0;
		}
		if ( 'ERROR' === $log_level ) {
			return 1;
		}
		if ( 'WARN' === $log_level ) {
			return 2;
		}
		if ( 'INFO' === $log_level ) {
			return 3;
		}
		if ( 'DEBUG' === $log_level ) {
			return 4;
		}
		return 99;
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
