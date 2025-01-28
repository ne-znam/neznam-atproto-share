/* global jQuery, neznam_atproto_share_comments */
( function ( $ ) {
	/*
  MIT License

  Copyright (c) 2024 Nicholas Sideras

  Permission is hereby granted, free of charge, to any person obtaining a copy
  of this software and associated documentation files (the "Software"), to deal
  in the Software without restriction, including without limitation the rights
  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
  copies of the Software, and to permit persons to whom the Software is
  furnished to do so, subject to the following conditions:

  The above copyright notice and this permission notice shall be included in all
  copies or substantial portions of the Software.

  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
  SOFTWARE.
  */

	// Public domain image from https://commons.wikimedia.org/wiki/File:Default_pfp.svg
	const defaultAvatar = `<svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewbox="0 0 340 340">
  <path fill="#DDD" d="m169,.5a169,169 0 1,0 2,0zm0,86a76,76 0 1 1-2,0zM57,287q27-35 67-35h92q40,0 67,35a164,164 0 0,1-226,0"/></svg>`;

	const rootElement = document.querySelector(
		'#comments.neznam-atproto-share-comments'
	);
	if ( ! rootElement || ! rootElement.dataset.uri ) {
		return;
	}
	const atProto = rootElement.dataset.uri;
	const commentTemplate = document.querySelector(
		'#neznam-atproto-comment-template'
	);
	if ( ! commentTemplate ) {
		/* eslint-disable no-console */
		console.warn(
			'Unable to load comment template. Aborting comment rendering'
		);
		/* eslint-enable no-console */
		return;
	}
	const commentOverview = document.querySelector(
		'#neznam-atproto-comment-overview'
	);
	const pageLimit = 50;
	const shownComments = 0;
	const commentCutoff = shownComments + pageLimit;

	fetch(
		'https://public.api.bsky.app/xrpc/app.bsky.feed.getPostThread?uri=' +
			atProto
	)
		.then( ( response ) => {
			if ( ! response.ok ) {
				throw new Error( `HTTP error, status = ${ response.status }` );
			}
			return response.json();
		} )
		.then( ( data ) => {
			if (
				typeof data.thread.replies !== 'undefined' &&
				data.thread.replies.length > 0
			) {
				const overview = createElementFromHTML(
					renderOverview( data.thread.post )
				);
				rootElement.replaceChildren( overview );
				rootElement.style.position = 'relative';
				const list = renderComments(
					data.thread,
					'comment-list',
					1,
					1
				);
				rootElement.appendChild( list.ol );

				// Due to hidden and blocked comments, the post.replyCount does not include an accurate count. Display based on number rendered.
				$( list.ol )
					.find( '.neznam-atproto-show-more-comments' )
					.each( function () {
						const currentPoint = parseInt(
							$( this ).data( 'starting' ),
							10
						);
						if ( currentPoint + pageLimit < list.count ) {
							$( this ).text(
								neznam_atproto_share_comments.show_replies + // eslint-disable-line camelcase
									` ${ currentPoint } - ${
										currentPoint + pageLimit
									} (${ list.count - currentPoint } ${
										neznam_atproto_share_comments.remaining // eslint-disable-line camelcase
									})`
							);
						} else {
							$( this ).text(
								neznam_atproto_share_comments.show_replies + // eslint-disable-line camelcase
									` ${ currentPoint } - ${ list.count }`
							);
						}
					} );
				const someReplies = document.createElement( 'p' );
				someReplies.innerHTML =
					'<hr><a href="' +
					ToBskyUrl( rootElement.dataset.uri ) +
					'" rel="ugc external nofollow" target="_blank">' +
					neznam_atproto_share_comments.post_reply + // eslint-disable-line camelcase
					'</a>';
				rootElement.append( someReplies );
			} else {
				const noReplies = document.createElement( 'div' );
				noReplies.innerHTML =
					'<div class="comment-overview"><em>' +
					neznam_atproto_share_comments.no_replies + // eslint-disable-line camelcase
					'</em></div><a href="' +
					ToBskyUrl( rootElement.dataset.uri ) +
					'" rel="ugc external nofollow" target="_blank">' +
					neznam_atproto_share_comments.post_reply + // eslint-disable-line camelcase
					'</a>';
				rootElement.replaceChildren( noReplies );
			}
		} )
		.catch( ( error ) => {
			/* eslint-disable no-console */
			console.warn( error );
			/* eslint-enable no-console */
			const p = document.createElement( 'p' );
			p.appendChild(
				document.createTextNode( `Error: ${ error.message }` )
			);
			document.body.appendChild( p, rootElement );
		} );

	function ToBskyUrl( uri ) {
		const splitUri = uri.split( '/' );
		if ( splitUri[ 0 ] === 'at:' ) {
			return (
				'https://bsky.app/profile/' +
				splitUri[ 2 ] +
				'/post/' +
				splitUri[ 4 ]
			);
		}
		return uri;
	}

	function renderComments( thread, classname, depth, count ) {
		if ( thread.replies && thread.replies.length > 0 ) {
			const ol = document.createElement( 'ol' );
			ol.className = classname;
			for ( const comment of thread.replies ) {
				const renderedString = renderComment( comment );
				if ( ! renderedString ) {
					continue;
				}
				const htmlContent = createElementFromHTML( renderedString );
				const li = document.createElement( 'li' );
				const swap = count % 2 ? 'odd' : 'even';
				li.className = `comment depth-${ depth } ${ swap } thread-${ swap }`;
				li.id = `neznam-atproto-comment-${ count }`;
				if ( count > commentCutoff ) {
					li.style.display = 'none';
				}
				li.appendChild( htmlContent );
				if ( count % pageLimit === 0 ) {
					showMoreLink( li, count + 1 );
				}
				count++;

				if ( comment.replies && comment.replies.length > 0 ) {
					const comments = renderComments(
						comment,
						'children',
						depth + 1,
						count
					);
					if ( comments ) {
						li.appendChild( comments.ol );
						count = comments.count;
					}
				}
				ol.appendChild( li );
			}
			return {
				ol,
				count,
			};
		}
		return false;
	}

	function showMoreLink( li, count ) {
		const div = document.createElement( 'div' );
		div.style.height = '2rem';

		const p = document.createElement( 'p' );
		p.style.position = 'absolute';
		p.style.left = 0;
		p.innerHTML = `<a href="#" class="neznam-atproto-show-more-comments" data-starting="${ count }"></a>`;
		$( 'a', p ).on( 'click', function ( e ) {
			e.preventDefault();
			$( this ).parent().parent().hide();
			const starting = $( this ).data( 'starting' );
			for ( let i = starting; i < pageLimit + starting; i++ ) {
				const comment = document.getElementById(
					`neznam-atproto-comment-${ i }`
				);
				if ( comment ) {
					comment.style.display = 'block';
				} else {
					break;
				}
			}
		} );
		div.appendChild( p );
		li.appendChild( div );
	}

	// https://stackoverflow.com/a/494348
	function createElementFromHTML( htmlString ) {
		const div = document.createElement( 'div' );
		div.innerHTML = htmlString.trim();
		return div.firstChild;
	}

	function sanitizeAttr( text ) {
		return text.replaceAll( '"', '&quot;' );
	}

	function utf8Slice( str, start, end ) {
		const encoder = new TextEncoder();
		const encoded = encoder.encode( str );
		return new TextDecoder( 'utf-8' ).decode( encoded.slice( start, end ) );
	}

	function escapeHTML( str ) {
		return str.replace( /&/g, '&amp;' ).replace( /</g, '&lt;' );
	}

	function embedFacets( text, facets ) {
		if ( ! facets || facets.length === 0 ) {
			return escapeHTML( text );
		}
		const randomChar = ( Math.random() + 1 ).toString( 36 );

		// Embed facets by iterating from the end to avoid index conflicts
		for ( let i = facets.length - 1; i >= 0; i-- ) {
			const facet = facets[ i ];
			const { byteStart, byteEnd } = facet.index;
			let replacement = utf8Slice( text, byteStart, byteEnd );
			if (
				facet.features[ 0 ].$type === 'app.bsky.richtext.facet#link'
			) {
				replacement = `${ randomChar }a style="color:red" href="${ facet.features[ 0 ].uri }" rel="ugc external nofollow" target="_blank">${ replacement }${ randomChar }/a>`;
			} else if (
				facet.features[ 0 ].$type === 'app.bsky.richtext.facet#mention'
			) {
				replacement = `${ randomChar }a style="color:red" href="https://bsky.app/profile/${ facet.features[ 0 ].did }" rel="ugc external nofollow" target="_blank">${ replacement }${ randomChar }/a>`;
			} else if (
				facet.features[ 0 ].$type === 'app.bsky.richtext.facet#tag'
			) {
				replacement = `${ randomChar }a style="color:red" href="https://bsky.app/hashtag/${ facet.features[ 0 ].tag }" rel="ugc external nofollow" target="_blank">${ replacement }${ randomChar }/a>`;
			} else {
				/* eslint-disable no-console */
				console.log(
					`Unrecognized facet type: ${ facet.features[ 0 ].$type }`
				);
				/* eslint-enable no-console */
				continue;
			}

			text =
				utf8Slice( text, 0, byteStart ) +
				replacement +
				utf8Slice( text, byteEnd );
		}

		return escapeHTML( text ).replaceAll( randomChar, '<' );
	}

	function renderComment( comment ) {
		if (
			! comment.post.record ||
			! comment.post.record.text ||
			! comment.post.record.createdAt ||
			! comment.post.author ||
			! comment.post.author.handle ||
			! comment.post.uri
		) {
			return false;
		}

		const replyDate = new Date( comment.post.record.createdAt );
		const authorName = comment.post.author.displayName
			? comment.post.author.displayName
			: '@' + comment.post.author.handle;
		const replyCount = comment.post.replyCount ?? '0';
		const repostCount = comment.post.repostCount ?? '0';
		const likeCount = comment.post.likeCount ?? '0';
		let authorImage = defaultAvatar;
		if ( comment.post.author.avatar ) {
			authorImage = `<img src="${
				comment.post.author.avatar
			}" loading="lazy" alt="Profile picture of ${ sanitizeAttr(
				authorName
			) }">`;
		}

		let commentHTML = commentTemplate.innerHTML;
		const replacements = {
			'##AUTHOR_IMAGE##': authorImage,
			'##AUTHOR_PROFILE_URL##': `https://bsky.app/profile/${ comment.post.author.handle }`,
			'##AUTHOR_NAME##': escapeHTML( authorName ),
			'##POST_DATE_ISO##': replyDate.toISOString(),
			'##POST_DATA_HUMAN##': replyDate.toLocaleString(),
			'##POST_URL##': ToBskyUrl( comment.post.uri ),
			'##POST_TEXT##': embedFacets(
				comment.post.record.text,
				comment.post.record.facets
			),
			'##REPLY_COUNT##': replyCount,
			'##REPOST_COUNT##': repostCount,
			'##LIKE_COUNT##': likeCount,
		};
		for ( const key of Object.keys( replacements ) ) {
			commentHTML = commentHTML.replaceAll( key, replacements[ key ] );
		}
		return commentHTML;
	}

	function renderOverview( post ) {
		const replyCount = post.replyCount ?? '0';
		const repostCount = post.repostCount ?? '0';
		const likeCount = post.likeCount ?? '0';
		const quoteCount = post.quoteCount ?? '0';

		let commentHTML = commentOverview.innerHTML;
		const replacements = {
			'##POST_URL##': ToBskyUrl( post.uri ),
			'##TOTAL_LIKES##': likeCount,
			'##TOTAL_QUOTES##': quoteCount,
			'##TOTAL_REPLIES##': replyCount,
			'##TOTAL_REPOSTS##': repostCount,
		};
		for ( const key of Object.keys( replacements ) ) {
			commentHTML = commentHTML.replaceAll( key, replacements[ key ] );
		}
		return commentHTML;
	}
} )( jQuery );
