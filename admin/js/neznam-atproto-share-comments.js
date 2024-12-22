( function( blocks, element, __ ) {
    console.log(__)
    var el = element.createElement;

    blocks.registerBlockType( 'neznam-atproto-share/bluesky-comment', {
        title: 'Bluesky Comment',
        icon: 'format-aside',
        category: 'common',

        edit: function () {
            var placeholder =  el( 'p', {
                style: { display: 'block' },
            }, 'Comment threads go here.' );

            return el( 'div', {}, [ placeholder ] );
        },

        save: function () {
            return null
        }

    } );
} )(
	window.wp.blocks,
	window.wp.element,
	window.wp.i18n
);
