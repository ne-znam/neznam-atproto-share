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

$comment_template = '<article class="comment comment-body">
      <header class="comment-meta">
        <div class="comment-avatar">
		  ##AUTHOR_IMAGE##
		</div>
        <div class="comment-metadata">
          <div class="comment-author">
            <a href="##AUTHOR_PROFILE_URL##" rel="external nofollow ugc" target="_blank"><b>##AUTHOR_NAME##</b></a>
          </div>
		  <div class="comment-time">
			<a href="##POST_URL##" rel="external nofollow ugc" class="url" target="_blank">
				<time datetime="##POST_DATE_ISO##">##POST_DATA_HUMAN##</time>
			</a>
		  </div>
        </div>
      </header>
      <section class="comment-content comment">
        ##POST_TEXT##
      </section>
      <div class="reply">
		<a class="comment-reply-link" href="##POST_URL##" rel="ugc external nofollow" target="_blank">
			##REPLY_COUNT##
		</a>
		<span>&nbsp;&nbsp;</span>

		<a class="comment-repost-link" href="##POST_URL##" rel="ugc external nofollow" target="_blank">
			##REPOST_COUNT##
		</a>
		<span>&nbsp;&nbsp;</span>

		<a class="comment-like-link" href="##POST_URL##" rel="ugc external nofollow" target="_blank">
			##LIKE_COUNT##
		</a>
	  </div>
      </article>';
$comment_template = apply_filters( 'neznam_atproto_comment_template', $comment_template );

$allowed_html         = wp_kses_allowed_html( 'post' );
$allowed_html['time'] = array( 'datetime' => true );

echo '<script type="text/html" id="neznam-atproto-comment-template">';
echo wp_kses( $comment_template, $allowed_html );
echo '</script>';
$direct_link = 'https://bsky.app/profile/' . esc_attr( $handle ) . '/post/' . esc_attr( substr( $bluesky_uri, strrpos( $bluesky_uri, '/' ) + 1 ) );
echo '<div id="comments" class="comments-area neznam-atproto-share-comments" data-uri="' . esc_attr( $bluesky_uri ) . '">
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
