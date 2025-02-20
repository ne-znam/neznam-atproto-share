# Neznam Atproto Share

Automatically share to Authenticated Transfer Protocol networks like BlueSky

## Description

This plugin enables automatic posting of articles to ATProto networks like BlueSky. This plugin was written primarily for BlueSky, but it should work with other ATProto networks as well.

The plugin adds a new section in the Settings -> Writing page where you can enter the login information for your ATProto network.

A new meta box is added to the post editor where you can select whether to share the post to your ATProto network and what status to use.

If no status is selected, the plugin will use the title of the post as the status.

The plugin shares the post to your ATProto network when the post is published via the WordPress cron system. So make sure you have the cron system working on your WordPress installation.

We recommend hooking up the cron system to a cron job on your server. You can find more information about this in the WordPress documentation.

## Installation

1. Upload [`neznam-atproto-share.zip`](https://github.com/ne-znam/neznam-atproto-share/releases/download/1.1.1/neznam-atproto-share.zip) in Plugins admin page, or install directly from the plugin search
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Setup the login information in the Settings -> Writing page

## Frequently Asked Questions

### Does this work with the Classic editor and/or the Block editor?

### What are the recommended WordPress settings under 'Settings -> Discussion'?

### Do comments appear immediately on my site or at the next cron job?

### Can I use this with BlueSky?

Yes, you can use this with BlueSky. Just make sure you have the correct login information. BlueSky default URL is default in the plugin settings.

Please visit [App Passwords](https://bsky.app/settings/app-passwords) in your BlueSky account to generate a new password for this plugin.

### Can I use this with other ATProto networks?

Yes, you can use this with other ATProto networks. Just make sure you have the correct login information.

## Contributing

If you have any ideas or suggestions, please feel free to contribute to the plugin by submitting a Issue or Pull Request in the GitHub repository for the plugin.

## License

This plugin is released under the [GPLv2 or later](https://www.gnu.org/licenses/gpl-2.0.html) license.
