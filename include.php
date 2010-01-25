<?php
/* flickpress includes for use by both popup and the main plugin file */

// get localized
$plugin_dir = basename(dirname(__FILE__));
load_plugin_textdomain( 'flickpress', 'wp-content/plugins/' . $plugin_dir, $plugin_dir );

// get phpflickr stuff
require_once( ABSPATH . 'wp-content/plugins/flickpress/phpflickr/phpFlickr.php');

function flickpress_check_key ($key) {
	global $table_prefix;
	$flick = new phpFlickpress($key);
	$fcon = "mysql://" . DB_USER . ":" . DB_PASSWORD . "@" . DB_HOST . "/" . DB_NAME;
	$flick->enableCache($type = 'db', $fcon , $cache_expire = 600, $table = $table_prefix.'flickpress_cache');
	$check = $flick->test_echo();
	if ($check['stat'] == 'ok') {
		return TRUE;
	} else {
		return FALSE;
	}
}

// Gets flickr user/group table as an associative array.
function flickpress_getlist() {
	global $table_prefix, $wpdb;
	$fp_table_name = $table_prefix . 'flickpress';
	if ($results = $wpdb->get_results("SELECT flickrid, flickrname FROM $fp_table_name ORDER BY flickrname",ARRAY_A)) {
                return $results;
        } else {
                return FALSE;
        }
}

// update the table
function flickpress_update($update_array) {
	global $wpdb, $flickpress_options, $table_prefix;
	$table_name = $table_prefix . "flickpress";
	foreach ($update_array as $key=>$val) {
		$update_array[$key] = $wpdb->escape($val);
        }
        if ($wpdb->get_var("SELECT flickrid FROM $table_name WHERE binary flickrid = '" . $update_array['flickrid'] . "'")) {
                return $wpdb->query("UPDATE $table_name SET 
                        flickrid = '" . $update_array['flickrid'] . "', 
                        flickrname = '" . $update_array['flickrname'] . "',
                        WHERE binary flickrid = '" . $update_array['flickrid'] . "'");
        } else {
                return $wpdb->query("INSERT INTO $table_name SET 
                        flickrid = '" . $update_array['flickrid'] . "',
                        flickrname = '" . $update_array['flickrname'] . "'");
        }
}

?>
