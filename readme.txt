=== flickpress ===
Contributors: isaacwedin
Donate link: http://familypress.net/flickpress/
Tags: images, photos, flickr
Requires at least: 2.8
Tested up to: 2.9.1
Stable tag: 1.5

flickpress is a tool to find Flickr photos and insert them into your posts, plus a widget to display recent Flickr photos.

== Description ==

flickpress adds a button to the post editor to find and insert Flickr photos into WordPress posts. Add Flickr users by entering their usernames or email addresses. Previously-entered Flickr users are stored in a database table that can be managed at Tools:flickpress. Search for users' photos by keyword or browse tags, photosets, favorites, or recent photos. You can also search for Creative Commons, Flickr Commons, and government photos. Click through to a photo to insert a variety of sizes, adding a variety of caption information if desired.

== Installation ==

1. Extract the plugin archive in your `/wp-content/plugins/` directory, creating a 'flickpress' folder there.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Enter your Flickr API key at Settings:flickpress and configure other
options if desired.
4. If you wish to use the caption function, your theme should include some caption-related style stuff - see the default theme for an example.

== Template Function ==

There is a simple template function available for use in your sidebar or other spots you'd like to include a few recent flickr photos. The function, its options, and the defaults are:

`flickpress_photos($email,$numphotos=3,$before='',$after='<br />',$fpclass='centered');`

== Widget ==

The widget just packages the template function in convenient widget form. To use it, activate it through the 'Plugins' menu in WordPress and add it to your sidebar through the 'Widgets' menu. It requires some things in the main plugin, so you'll need to have both activated or it will not work.

Just like with the template function, you may specify a class for the images,
and text (such as HTML tags) to display before and after each image. Some tips:

* For a horizontal display, put nothing or just spaces before and after.
* For a vertical display, either use `<p>` before and `</p>` after or just a `<br />` after.
* Most themes include a class called "centered" that will center your images in the sidebar.

== Notes ==

This plugin relys heavily on Dan Coulter's nice phpFlickr library ( <http://phpflickr.com/> ). I've found that using more than one plugin based on the library can cause conflicts, so I renamed the class in the library included with flickpress. If you're considering creating a plugin based on flickpress I highly recommend getting the latest official version of phpFlickr instead of using this modified version.

== Changelog ==

= 1.5 =
* Renamed phpFlickr class and calls to eliminate plugin conflicts.
* Added favorites.
* Fixes error when inserting captions with funky characters.

= 1.4 =
* Added options to include photo description and EXIF info in photo captions.

= 1.3 =
* Added caption alignment class option.
* Updated phpFlickr to 2.3.1.

= 1.2 =
* Tested for WP 2.9.
* Moved things around on the photo insert page.
* Turned captions on by default when no option set.

= 1.1 =
* Changed popup.php to use wp-load.php instead of deprecated wp-config.php.

= 1.0 =
* Added widget options to display something (HTML tags) before and after each image.

= 0.9 =
* Added admin notice for missing Flickr API key.
* Set "edit_posts" as the default capability to use plugin.
* Added Flickr API key and capability checks to the settings page.
* Added option to turn captions on or off by default.
* Added jquery show/hide for license lists in the popup.
* Fixed "next page" bug for commons search.

== Upgrade Notice ==

= 1.5 =
* This version fixes a compatibility issue with other phpFlickr-based plugins, fixes a JavaScript error when inserting captions with funky characters, and adds favorites browsing.
