<?php
/**
 * Includes the HTML needed to render the Bluesky comments.
 *
 * @link       https://www.neznam.hr
 * @since      1.6.0
 *
 * @package    Neznam_Atproto_Share
 * @subpackage Neznam_Atproto_Share/public/partials
 */

if ( ! is_single() || 'post' !== get_post_type() ) {
	return;
}
global $post;

$plugin_name = 'neznam-atproto-share';
$handle      = get_option( $plugin_name . '-handle' );
$bluesky_uri = get_post_meta( $post->ID, $plugin_name . '-uri', true );
if ( empty( $handle ) || empty( $bluesky_uri ) ) {
	return;
}

$direct_link = 'https://bsky.app/profile/' . esc_attr( $handle ) . '/post/' . esc_attr( substr( $bluesky_uri, strrpos( $bluesky_uri, '/' ) + 1 ) );
echo '<div id="neznam-atproto-share-comments" data-uri="' . esc_attr( $bluesky_uri ) . '">
<svg version="1.1" id="L1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 100 100" enable-background="new 0 0 100 100" xml:space="preserve" style="width:2rem;margin-bottom: -.5rem;">
	<circle fill="none" stroke="#fff" stroke-width="1" stroke-miterlimit="10" stroke-dasharray="10,10" cx="50" cy="50" r="39">
		<animateTransform 
			attributeName="transform" 
			attributeType="XML" 
			type="rotate"
			dur="5s" 
			from="0 50 50"
			to="-360 50 50" 
			repeatCount="indefinite" />
	</circle>
	</svg>';
	echo wp_kses(
		sprintf(
			/* translators: %s: URL of post */
			__( 'Loading comments from <a href="%s">Bluesky post</a>&hellip;', 'neznam-atproto-share' ),
			esc_url( $direct_link )
		),
		array(
			'a' => array(
				'href'   => array(),
				'target' => array(),
			),
		)
	);


	echo '</div>';
