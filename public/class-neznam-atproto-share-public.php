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
	 * The fallback comment behavior, either "default" or "hidden".
	 *
	 * @since    1.6.0
	 * @access   private
	 * @var      string    $fallback_method    The fallback comment behavior.
	 */
	private $fallback_method = 'default';

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
	 * Determines if and when the comments RSS feed should be hidden.
	 *
	 * @since    1.6.0
	 */
	public function manage_feeds() {
		if ( ! empty( get_option( $this->plugin_name . '-comment-disable' ) ) ) {
			$this->disable_feeds();
			return;
		}
		if ( is_single() ) {
			global $post;
			$this->bluesky_uri = get_post_meta( $post->ID, $this->plugin_name . '-uri', true );
			if ( ! empty( $this->bluesky_uri ) ) {
				$this->disable_feeds();
				return;
			}
		}
	}

	/**
	 * Consolidates the methods to disable comment RSS feeds.
	 *
	 * @since    1.6.0
	 */
	private function disable_feeds() {
		add_filter( 'feed_links_show_comments_feed', '__return_false' );
		add_filter( 'feed_links_extra_show_post_comments_feed', '__return_false' );
	}

	/**
	 * Manage how the post's comments are adjusted to include Bluesky replies.
	 *
	 * @since    1.6.0
	 */
	public function comment_controls() {
		if ( ! empty( get_option( $this->plugin_name . '-comment-disable' ) ) ) {
			$this->fallback_method = 'hidden';
		}

		add_filter( 'comments_number', array( $this, 'comments_number' ), 10, 2 );

		if ( is_singular( 'post' ) ) {
			global $post;
			$this->bluesky_uri = get_post_meta( $post->ID, $this->plugin_name . '-uri', true );
			if ( empty( $this->bluesky_uri ) ) {
				if ( 'hidden' === $this->fallback_method ) {
					add_filter( 'comments_template', array( $this, 'no_comments' ) );
					add_theme_support( 'automatic-feed-links' );
					add_filter( 'feed_links_show_comments_feed', '__return_false' );
				}
				return;
			}

			add_filter( 'comments_template', array( $this, 'comments_template' ) );
			wp_deregister_script( 'comment-reply' );
			wp_enqueue_script( $this->plugin_name . '-comments' );
			wp_enqueue_style( $this->plugin_name . '-comments' );

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
	 * Adjusts if the comments number should be hidden for when comments come from Bluesky.
	 *
	 * @param   string $comments_number_text Text expected to be displayed.
	 * @return  string
	 * @since    1.6.0
	 */
	public function comments_number( $comments_number_text ) {
		global $post;
		$bluesky_uri = get_post_meta( $post->ID, $this->plugin_name . '-uri', true );
		if ( ! empty( $bluesky_uri ) || 'hidden' === $this->fallback_method ) {
			return '';
		}
		return $comments_number_text;
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
	 * Returns the comments template
	 *
	 * @since     1.6.0
	 * @return    string    The new comment text.
	 */
	public function no_comments() {
		return plugin_dir_path( __FILE__ ) . '/partials/blank.php';
	}

	/**
	 * Returns the comments template
	 *
	 * @since     1.6.0
	 * @return    string    The new comment text.
	 */
	public function comments_template() {
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
