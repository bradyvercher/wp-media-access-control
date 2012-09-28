=== Media Access Control ===
Contributors: blazersix, bradyvercher
Tags: media access, attachment access, access control, restrict, privacy, private
Requires at least: 3.4.2
Tested up to: 3.4.2
Stable tag: 0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Implement custom access rules for uploaded media files.

== Description ==

Media Access Control is meant to be extended by other plugins or developers in order to implement custom access rules for uploaded media files. On its own, it won't actually do anything useful.

The plugin adds a field to the standard Media settings screen that allows users to define a list of file extensions that should have custom access rules applied to them. Then when a visitor makes a request to a matching file, the file is passed through a filter that allows plugins to determine whether or not the visitor should be granted access.

== Installation ==

Installing Media Access Control is just like installing most other plugins. [Check out the codex](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins) if you have any questions.

#### Setup
After installation, go to the Media settings panel (the Media link under Settings) and enter the file extensions that should be routed through the custom filter.

That's it for setup. Custom business rules will need to be applied by a developer or another plugin by hooking into the `media_access_control_allow_file_access` filter.

== Frequently Asked Questions ==

= Why aren't all files automatically filtered? =

A custom rewrite rule is created that causes whitelisted files to be routed through WordPress instead of being served directly and bypassing WordPress altogether. Doing this for every uploaded file would be overkill, especially for images and other embedded files.

= So I can't use this for images? =

There are a few problems unique to images, which is why it's **strongly suggested** that you don't whitelist images using this plugin.

1. Images can be embedded in pages, meaning that WordPress will be *reloaded for every single image*.
2. Headers are sent telling browsers not to cache files, so unauthorized requests won't be cached.
3. Headers are also sent forcing the visitor to download the requested file instead of viewing it in their browser.

The basic idea could be duplicated to provide support for images, but it would most likely require naming images with a unique pattern instead of relying on the file extension to workaround some of these limitations. WordPress also generates multiple files for images, so support would need to be added to know when a requested image is an alternate version of the original.

= Can I use this for files that aren't in my upload directory? =

Not at this time.

= Will this work on servers not running Apache? =

Maybe, but it hasn't been tested. The external rewrite rule is registered via the core API, so it should be handled just like the built-in rules.

= I still don't get it. How is this useful? =

Create a meta box on a post, page, or CPT and let editors choose specific users that should have access. Then when a whitelisted file is requested, it'll be passed through the filter and a plugin can determine if the visitor should be granted access based on whether or not the user was selected in that meta box.

Or grant access to administrators only. Even if the URL is made public, the file can't be accessed without explicit permission.

Or perhaps you only want logged in users to download MP3s.

The possibilites are endless.

== Changelog ==

= 0.1 =
* Initial release.