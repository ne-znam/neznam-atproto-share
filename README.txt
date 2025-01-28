=== Neznam Atproto Share ===
Contributors: mbanusic, ecaron
Donate link: https://nezn.am
Tags: atproto, share, bluesky
Requires at least: 6.0.0
Requires PHP: 8.0.0
Tested up to: 6.7.0
Stable tag: 2.1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Automatically share to Authenticated Transfer Protocol networks like BlueSky and display comments from that network below the post as comments.

== Description ==

This plugin enables automatic posting of articles to ATProto networks like BlueSky. Also, it enables the display of comments from the ATProto network below the post as comments.

The plugin adds a new section in the Settings -> Writing page where you can enter the login information for your ATProto network.

A new meta box is added to the post editor where you can select weather to share the post to your ATProto network and what status to use.

If no status is selected, the plugin will use the title of the post as the status.

The plugin shares the post to your ATProto network when the post is published via the WordPress cron system. So make sure you have the cron system working on your WordPress installation.

We recommend hooking up the cron system to a cron job on your server. You can find more information about this in the WordPress documentation.

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload `neznam-atproto-share.zip` in Plugins admin page, or install directly from the plugin search
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Set up the login information in the Settings -> Writing page

== Frequently Asked Questions ==

= Can I use this with BlueSky =

Yes, you can use this with BlueSky. Just make sure you have the correct login information. BlueSky default URL is default in the plugin settings.

= Can I use this with other ATProto networks =

Yes, you can use this with other ATProto networks. Just make sure you have the correct login information.

= How can I modify the comment template =

You can use the `neznam_atproto_comment_template` filter to modify the comment template. The filter receives the comment template as the first argument. See `/public/partials/comment.php` for the default template.

== Screenshots ==

1. This screen shows the meta box in the post editor
2. This screen shows the settings page

== Changelog ==

= 2.1.1 =
* Cleanup and bugfixes

= 2.1.0 =
* Disassociating Posts - within metabox
* Linking Posts - within metabox
* Comment Pagination
* Rich Comments
* Upload via Local Path

= 2.0.0 =
* Added option to display comments from ATProto networks as comments below the post

= 1.7.0 =
* Fixed share URL to preserve even after plugin deletion

= 1.6.0 =
* Added templates for the post status

= 1.5.0 =
* All inputs, both provided from /wp-admin/ as well as external sources, are sanitized and have their validity checked (thanks, White Fir)
* When the plugin is uninstalled, all options are removed
* The cron task is only setup to run when the option is enabled
* Separated the checkbox of "should this post" to the save_post action, and "post this, if checked" to the wp_after_insert_post action - this should fix the runtime issue people mentioned
* Added log output, of various levels, to help people debug
* Made the "Default to share" functionality visible
* Fixed the link showing as raw HTML near the admin "Secret" field
* Stopped redundant calls to the API when values, such as secret and handle, did not change

= 1.4.0 =
* Fixed token refresh
* Checks for variable unset

= 1.3.1 =
* Added support for WordPress 6.7.0

= 1.3.0 =
* Added support for WordPress 6.5.0
* Fix repeated posting
* Added info about already shared posts

= 1.2.0 =
* Added option to post on publish

= 1.1.0 =
* Fixed the locale of post thanks to @delirehberi
* Added login information verification on settings page
* Switched to using WP_Filesystem for reading images
* More documentation
* Cleaned up code

= 1.0 =
* Initial release
