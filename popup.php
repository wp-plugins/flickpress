<?php
require_once( dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php');
require_once( ABSPATH . '/wp-admin/admin.php'); // need this for media upload functions, but only for imports
require_once( ABSPATH . 'wp-content/plugins/flickpress/include.php');
if ($user_ID == '')
	die (__('Try logging in.','flickpress'));
$flickpress_options = get_option('flickpress_options');
if (!current_user_can($flickpress_options['usecap']))
	die (__('Ask the administrator to promote you, or verify that the capability entered at Settings:flickpress is correct.','flickpress'));
if (isset($_GET['fp_parent_ID']))
	$fp_parent_ID = $_GET['fp_parent_ID'];
if (isset($_POST['fp_parent_ID']))
	$fp_parent_ID = $_POST['fp_parent_ID'];
if (empty($flickpress_options['apikey']))
	die (__('No Flickr API key found, please enter one at Settings:flickpress.','flickpress'));
if (!flickpress_check_key($flickpress_options['apikey']))
	die (__('Your Flickr API key may be invalid, Flickr may be down, or the Flickr API may have changed. If Flickr is up and your key is correct check for a plugin update.','flickpress'));
$flickpress_per_page = 32;

// print the header stuff
flickpress_popup_header();

if (isset($_POST['fpuseradd'])) {
	flickpress_popup_main();
}

if (isset($_GET['fpaction'])) {

	// display the main page
	if (($_GET['fpaction'] == 'users') || ($_GET['fpaction'] == 'delcache')) {
		flickpress_popup_main();
	}

	// display the user options page
	if ($_GET['fpaction'] == 'options') {
		flickpress_popup_user_options();
	}

	// Display a user's recent photos
	if ($_GET['fpaction'] == 'recent') {
	flickpress_popup_recent();
	}

	// Display recent interesting photos
	if ($_GET['fpaction'] == 'interesting') {
		flickpress_popup_interesting();
	}

	// Display a user's favorite photos
	if ($_GET['fpaction'] == 'faves') {
		flickpress_popup_favorites();
	}

	// display a user's photosets or the photos in a set
	if ($_GET['fpaction'] == 'sets') {
		flickpress_popup_sets();
	}

	// display a user's tags or the photos for one of the tags
	if ($_GET['fpaction'] == 'tags') {
		flickpress_popup_tags();
	}

	// Display photo info and insert stuff
	if ($_GET['fpaction'] == 'showphoto') {
		flickpress_popup_showphoto();
	}

	// Import a photo into the Media Library
	if ($_GET['fpaction'] == 'import') {
		flickpress_popup_import();
	}

}

// Display the search results
if (isset($_POST['searchtext']) || isset($_GET['searchtext'])) {
	flickpress_popup_search();
}

?>
