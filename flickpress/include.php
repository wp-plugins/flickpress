<?php
/* flickpress includes for use by both popup and the main plugin file */

// get localized
load_plugin_textdomain('flickpress');

// get phpflickr stuff
require_once( ABSPATH . 'wp-content/plugins/flickpress/phpflickr/phpFlickr.php');

// fill the options array with the stored data if it's there
$filez_options = array();
if (get_option('flickpress_options')) {
	$flickpress_options = get_option('flickpress_options');
}

// default capability is edit_posts
if (empty($flickpress_options['capability'])) {
	$flickpress_options['capability'] = 'edit_posts';
}

function flickpress_check_key ($key) {
	$f = new phpFlickr($key);
	$check = $f->test_echo();
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
