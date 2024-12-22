<?php
/**
 * Public class
 *
 * @package    Neznam_Atproto_Share
 * @subpackage Neznam_Atproto_Share/public
 * @link       https://www.neznam.hr
 * @since      1.6.0
 */

/**
 * The public-facing functionality of the plugin.
 *
 * @package    Neznam_Atproto_Share
 * @subpackage Neznam_Atproto_Share/public
 * @author     Eric Caron <eric.caron@gmail.com>
 */
class Neznam_Atproto_Share_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.6.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.6.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * The Bluesky post URI.
	 *
	 * @since    1.6.0
	 * @access   private
	 * @var      string    $bluesky_uri    Bluesky URI of the post.
	 */
	private $bluesky_uri;

	/**
	 * The current comment behavior, either "bluesky" or "hidden".
	 *
	 * @since    1.6.0
	 * @access   private
	 * @var      string    $comment_method    The current comment behavior.
	 */
	private $comment_method;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.6.0
	 * @param      string $plugin_name       The name of the plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Manage how the post's comments are adjusted to include Bluesky replies.
	 *
	 * @since    1.6.0
	 */
	public function comment_controls() {
		if ( is_singular( 'post' ) && in_the_loop() ) {
			global $post;
			$this->bluesky_uri = get_post_meta( $post->ID, $this->plugin_name . '-uri', true );
			if ( ! empty( $this->bluesky_uri ) ) {
				$this->comment_method = 'bluesky';
				wp_enqueue_script( $this->plugin_name . '-comments' );
			} elseif ( ! empty( get_option( $this->plugin_name . '-comment-disable' ) ) ) {
				$this->comment_method = 'hidden';
			} else {
				return;
			}

			wp_enqueue_style( $this->plugin_name . '-comments' );
			add_filter( 'comments_open', '__return_false' );
			add_filter( 'comments_template', array( $this, 'comments_template' ) );
			add_action( 'show_user_profile', '__return_false' );
			add_filter( 'comments_number', '__return_false' );

			unregister_block_type( 'core/comments' );
			register_block_type(
				'core/comments',
				array(
					'editor_script'   => $this->plugin_name . '-comments',
					'render_callback' => array( $this, 'pre_get_comments' ),
				)
			);
		}
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.6.0
	 */
	public function register_styles() {
		wp_register_style( $this->plugin_name . '-comments', plugin_dir_url( __FILE__ ) . 'css/neznam-atproto-share-comments.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.6.0
	 */
	public function register_scripts() {
		wp_register_script(
			$this->plugin_name . '-comments',
			plugin_dir_url( __FILE__ ) . 'js/neznam-atproto-share-comments.js',
			array( 'jquery' ),
			$this->version,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);
	}

	/**
	 * Hide the comments number, because it is only known client-side.
	 *
	 * @since     1.6.0
	 * @return    string                  A blank string.
	 */
	public function comments_number() {
		return '';
	}

	/**
	 * Returns the comments template
	 *
	 * @since     1.6.0
	 * @return    string    The new comment text.
	 */
	public function comments_template() {
		if ( 'hidden' === $this->comment_method ) {
			return '';
		}
		return plugin_dir_path( __FILE__ ) . '/partials/comments.php';
	}

	/**
	 * Renders the HTML for the Bluesky-powered comments.
	 *
	 * @since     1.6.0
	 * @return    string    The new comment text.
	 */
	public function pre_get_comments() {
		ob_start();
		include plugin_dir_path( __FILE__ ) . '/partials/comments.php';
		$comments = ob_get_clean();
		return $comments;
	}
}
