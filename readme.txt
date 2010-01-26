=== Plugin Name ===
Contributors: swhitley
Tags: LinkedIn, OAuth, comments, login, single signon, avatar, claim
Requires at least: 2.7.0
Tested up to: 2.8.5
Stable tag: 0.1

Integrate LinkedIn and Wordpress.  Provides single-signon and avatars.

Changes in Version 0.1

- Changed login buttons







== Installation ==

1. Upload `linkedinconnect.php` and all included files to the `/wp-content/plugins/` directory.
1. Place `<?php if(function_exists('linkedin_connect')){linkedin_connect();} ?>` in your comment template or rely on the default `<?php do_action('comment_form', $post->ID); ?>` code.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Modify plugin options through the `Settings` menu.


== Change Log ==

0.1
28/12/2009 Tonći Galić

- Used Shannon Whitley's Twit Connect plugin to produce a simple plugin


