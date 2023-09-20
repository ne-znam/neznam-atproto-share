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

	private string $url;

	private string $handle;

	private string $app_pass;

	private string $did;

	private string $access_token;

	private string $refresh_token;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		$this->url           = get_option( $this->plugin_name . '-url' );
		$this->handle        = get_option( $this->plugin_name . '-handle' );
		$this->app_pass      = get_option( $this->plugin_name . '-secret' );
		$this->access_token  = get_option( $this->plugin_name . '-access-token' );
		$this->refresh_token = get_option( $this->plugin_name . '-refresh-token' );
		$this->did           = $this->get_did( $this->handle );
	}

	private function get_did( string $handle ): string {
		$did = get_option( $this->plugin_name . '-did' );
		if ( ! $did ) {
			$body = wp_remote_get( trailingslashit( $this->url ) . 'xrpc/com.atproto.identity.resolveHandle?handle=' . $handle );
			$body = json_decode( $body['body'], true );
			$did  = $body['did'];
			update_option( $this->plugin_name . '-did', $did );
		}

		return $did;
	}

	public function cron() {
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

	public function post_message( WP_Post $post ): void {
		if ( ! $this->access_token ) {
			$this->authorize();
		}
		$image_path      = get_attached_file( get_post_thumbnail_id( $post->ID ) );
		$blob            = $this->upload_blob( $image_path );
		$text_to_publish = get_post_meta( get_the_ID(), $this->plugin_name . '-text-to-publish', true );
		$body            = wp_json_encode(
			array(
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
							'thumb'       => $blob,
						),
					),
					'langs'     => array( 'hr' ),
				),

			)
		);
		$body = wp_remote_post(
			trailingslashit( $this->url ) . 'xrpc/com.atproto.repo.createRecord',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->access_token,
					'Content-Type'  => 'application/json',
				),
				'body'    => $body,
			)
		);
		$body = json_decode( $body['body'], true );
		$uri  = $body['uri'];
		update_post_meta( $post->ID, $this->plugin_name . '-uri', $uri );
	}

	private function authorize(): void {
		if ( $this->refresh_token ) {
			$this->refresh_token();

			return;
		}
		$body                = wp_remote_post(
			trailingslashit( $this->url ) . 'xrpc/com.atproto.server.createSession',
			array(
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'identifier' => $this->did,
						'password'   => $this->app_pass,
					)
				),
			)
		);
		$body                = json_decode( $body['body'], true );
		$this->access_token  = $body['accessJwt'];
		$this->refresh_token = $body['refreshJwt'];
		update_option( $this->plugin_name . '-access-token', $this->access_token );
		update_option( $this->plugin_name . '-refresh-token', $this->refresh_token );
	}

	private function refresh_token(): void {
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

	private function upload_blob( string $path ): array {
		if ( ! $path ) {
			return array();
		}
		$body = wp_remote_post(
			trailingslashit( $this->url ) . 'xrpc/com.atproto.repo.uploadBlob',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->access_token,
					'Content-Type'  => 'image/*',
				),
				'body'    => file_get_contents( $path ),
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
}
