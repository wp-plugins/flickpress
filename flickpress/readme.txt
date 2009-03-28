=== flickpress ===
Contributors: isaacwedin
Tags: images, photos, flickr
Requires at least: 2.7
Tested up to 2.7.1
Stable tag: 0.6

flickpress is a tool to insert Flickr photos into your posts.

== Description ==

flickpress adds a button to the post editor to insert Flickr photos into WordPress posts. The button launches a popup tool where Flickr users can be added via their email addresses. Previously-entered Flickr users are stored in a database table. At Tools:flickpress users can be removed. Photos may be browsed by recentness, tag, and photoset. Click through to a photo to insert a variety of sizes, adding the image's Flickr title as a caption if desired.

== Installation ==

1. Extract the plugin archive in your `/wp-content/plugins/` directory, creating a 'flickpress' folder there.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Enter your Flickr API key at Settings:flickpress.
4. If you wish to use the caption function, your theme should include some caption-related CSS - see the default theme for an example.

== Template Function ==

There is a simple template function available for use in your sidebar or other spots you'd like to include a few recent flickr photos. The function, its options, and the defaults are:
`flickpress_photos($email,$numphotos=3,$before='',$after='<br />',$fpclass='centered'`

== Widget ==

The widget just packages the template function in convenient widget form. To
use it, activate it through the 'Plugins' menu in WordPress and add it to your
sidebar through the 'Widgets' menu. It requires a function in the main plugin,
so you'll need to have both activated to use it.
