=== flickpress ===
Contributors: isaacwedin
Donate link: http://familypress.net/flickpress/
Tags: images, photos, flickr
Requires at least: 3.3
Tested up to: 3.3
Stable tag: 1.9.3

flickpress is a tool to find Flickr photos and insert them into your posts, plus a widget to display recent Flickr photos.

== Description ==

flickpress adds a button to the post editor to find and insert Flickr photos into WordPress posts. Add Flickr users by entering their usernames or email addresses. Previously-entered Flickr users are stored in a database table that can be managed at Tools:flickpress. Search for users' photos by keyword or browse tags, photosets, favorites, or recent photos. You can also search for Creative Commons, Flickr Commons, and government photos. Click through to a photo to insert a variety of sizes, adding a variety of caption information if desired.

== Installation ==

1. Extract the plugin archive in your `/wp-content/plugins/` directory, creating a 'flickpress' folder there.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Enter your Flickr API key at Settings:flickpress and configure other options.
4. If you wish to use the caption function, your theme should include some caption-related style stuff - which it probably does - but see the WordPress default theme for an example if not.

== Widget ==

The widget just packages the template function in convenient widget form. To use it, activate it through the 'Plugins' menu in WordPress and add it to your sidebar through the 'Widgets' menu. It requires some things in the main plugin, so you'll need to have both activated.

Just like with the template function, you may specify a class for the images, and text (such as HTML tags) to display before and after each image. Some tips:

* For a horizontal display, put nothing or just spaces before and after.
* For a vertical display, either use `<p>` before and `</p>` after or just a `<br />` after.
* Many themes include a class called "aligncenter" that should center your images in the sidebar.

== Lightbox Support ==

There are a couple of different ways to enable lightbox support for images inserted with flickpress.

The easiest way is to just turn on ThickBox in the settings. This will add the necessary bits to your inserted images and add the necessary JavaScript to your theme for a simple lightbox.

Another fairly easy method is to turn on the Custom lightbox option in the settings and install a lightbox plugin that automatically recognizes lightboxable image links. The Lightbox Plus plugin has worked for me with this method, and has lots of customization options.

Finally, you could set it up completely manually: turn on the Custom lightbox option in the settings, enter a class or rel, then add your own lightbox JavaScript to your theme.

== Template Function ==

There is a simple template function available for use in your sidebar or other spots you'd like to include a few recent flickr photos. The function, its options, and the defaults are:

`if (function_exists('flickpress_photos')) flickpress_photos($email,$numphotos=3,$before='',$after='<br />',$fpclass='centered');`

== Notes ==

This plugin is really just a wrapper for Dan Coulter's excellent phpFlickr library ( <http://phpflickr.com/> ). Using more than one plugin based on the library can cause conflicts, so I renamed the class in the library included with flickpress. I also edited (maybe broke!) the database cache code, so if you're considering creating a plugin based on flickpress I highly recommend getting the latest official version of phpFlickr instead of using this modified version.

== Changelog ==
= 1.9.3 =
* Added various things to the popup tool.
* Added username lookup option to widget.
* Fixed editor buttons - HTML button is now only WP 3.3+ compatible.
* Removed mysterious "border=0" from widget display function.
* Updated phpFlickr to 3.1, fixed various issues.
* Check for email address before trying to get images in widget/shortcode function.
* Got rid of some WP 2.9 specific code.
* Better settings validation.
* Fixed some license stuff.
* Made the user manager suck a little less.

= 1.9.2 =
* Fixed activation issue with deprecated upgrade-functions.php.
* Added fsockopen test to reduce timeout duration when Flickr or network down.
* Only load plugin options when needed.
* Added option to import image into Media Library.

= 1.9.1 =
* Added generic lightbox support.
* Added title-only option for captions.
* Fixed before/between/after text to allow HTML tags.
* Various bug fixes to pass WP_DEBUG.
* Added license default setting.

= 1.9 =
* Added some caption customization options.
* Moved widget into main plugin.
* Added ThickBox support.
* Updated phpFlickr library.

= 1.8.1 =
* More conflict insulation in the popup tool.
* Tag list now starts with popular, with option to list all.

= 1.8 =
* Added deactivation function to delete cache table.
* Fixed popup menu bug.
* Using WP paginate_links function for popup navigation.
* Renamed more stuff to avoid conflicts.

= 1.7.1 =
* Fixed broken widget and template function.

= 1.7 =
* Fix for Flickr API change.
* Fixed extra Home link for commons search image page.
* Fixed popup style for WP 3.0.

= 1.6.1 =
* Fixed add user bug, added error handling.

= 1.6 =
* Added some error handling.
* Made navigation headings more consistent.
* Fixed warning for empty EXIF data, added reporting for empty EXIF and description.
* Added interesting photo browsing.
* Added older/newer links.
* Markup cleanup, should now pass validation.

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

= 1.9.2 =
* Fixed activation issue with deprecated stuff, added fsockopen tests, only load options when needed, option to import images.

= 1.9.1 =
This version adds custom lightbox support, allows HTML tags in before/between/after caption text, fixes bugs to pass WP_DEBUG, and adds an option to set default licenses.

= 1.9 =
* Added caption customization, ThickBox support, updated phpFlickr, moved widget into main plugin.

= 1.8.1 =
* More conflict insulation, tag list now starts with popular.

= 1.8 =
* Fixed small bugs, added some conflict insulation, popup navigation improvements.

= 1.7.1 =
* Fixed broken widget and template function.

= 1.7 =
* Fix for Flickr API change, fixed extra Home link for commons search image page, fixed popup style for WP 3.0.

= 1.6.1 =
* Fixes a bug in 1.6 where users couldn't be added.

= 1.6 =
* This version adds some error handling, makes the navigation headings more consistent, fixes empty EXIF warning, adds interesting photo browsing, adds a bunch of older/newer links, fixes markup validation issues.

= 1.5 =
* This version fixes a compatibility issue with other phpFlickr-based plugins, fixes a JavaScript error when inserting captions with funky characters, and adds favorites browsing.
