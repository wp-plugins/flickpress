<?php
/*
Plugin Name: flickpress
Plugin URI: http://familypress.net/flickpress/
Description: A multi-user flickr tool for WordPress. Creates a table to store flickr ids. Last tested and working with WordPress 2.7.1. Uses Dan Coulter's excellent phpFlickr class. Requires a Flickr API key.
Version: 0.6
Author: Isaac Wedin
Author URI: http://familypress.net/
*/

/* Copyright 2009  Isaac Wedin (email : isaac@familypress.net)
This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version. 

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

This program makes use of the phpflickr library, which is licensed under the Lesser GPL - see included version of the library for license and author details.
*/

require_once(ABSPATH . 'wp-content/plugins/flickpress/buttons.php');
require_once(ABSPATH . 'wp-content/plugins/flickpress/include.php');

// only users with "manage options" permission should see the Options page
function flickpress_add_options_page() {
	add_options_page('flickpress', 'flickpress', 'manage_options', basename(__FILE__), 'flickpress_options_subpanel');
}

// generates the flickpress Options subpanel
function flickpress_options_subpanel() {
	global $flickpress_options;
	if (isset($_POST['flickpress_options_update'])) { // update the options and refresh the options array with the updated options
		$flickpress_updated_options = array();
		$flickpress_updated_options = $_POST;
		add_option('flickpress_options');
		update_option('flickpress_options', $flickpress_updated_options);
		$flickpress_options = get_option('flickpress_options');
		echo '<div class="updated">' . __('flickpress options updated.','flickpress') . "</div>\n";
	}
	echo '
	<div class="wrap">
	<h2>' . __('flickpress options','flickpress') . '</h2>
	<form name="flickpress_options" method="post">
	<input type="hidden" name="flickpress_options_update" value="update" />
	<fieldset class="options">
	<table class="form-table">
		<tbody>
					 <tr>
								<th scope="row">' . __('flickr API key:','flickpress') . '</th>
								<td><input name="apikey" type="text" id="apikey" value="' . $flickpress_options['apikey'] . '" size="20"><br />
					 ' . __('Enter your <a href="http://flickr.com/services/api/keys/apply/">flickr API key</a> here. This is required for the plugin to work.','flickpress') . '</td>
					 </tr>
		<tr>
			<th scope="row">' . __('Capability required to use flickpress:','flickpress') . '</th>
			<td><input name="usecap" type="text" id="usecap" value="' . $flickpress_options['usecap'] . '" size="20"><br />
		' . __('You probably want to use <code>edit_posts</code>, but you could also use <code>upload_files</code>, <code>publish_posts</code> or any other capability you want.','flickpress') . '</td>
		</tr>
		</tbody>
	</table> 
	</fieldset>

	<p class="submit"><input type="submit" name="Submit" value="' . __('Update Options &raquo;','flickpress') . '" /></p>
</form> 
</div>
';
}

add_action('admin_menu', 'flickpress_add_options_page');

// makes the flickpress Management page
function flickpress_management() {
	global $flickpress_options, $wpdb, $table_prefix;
	$table_name = $table_prefix . "flickpress";
	if (isset($_POST['flickpress_update'])) {
		$dels = 0;
		$updates = 0;
		foreach ((array)$_POST as $key=>$val) {
			if (is_array($val)) {
				if (($val['delete'] == '1')) {
					if (flickpress_delete($val['flickrid'])) {
						$dels++;
					}
				} elseif (!empty($val['flickrid'])) {
													 $update_array = array();
													 $update_array['flickrid'] = $val['flickrid'];
													 $update_array['flickrname'] = $val['flickrname'];
													 if (flickpress_update($update_array)) {
																$updates++;
													 }
										  }
								}
					 }
					 echo "<div class='updated'>";
					 if ($dels > 0) {
								echo $dels . __(' records deleted. ','flickpress');
					 }
					 if ($updates > 0) {
								echo $updates . __(' records updated.','flickpress');
					 }
  echo "</div>\n";
		  }
		  echo '
		  <div class="wrap">
		  <h2>' . __('flickpress manager','flickpress') . '</h2>
		  <p>' . __('You can manually manage flickpress users here. You should probably only delete users...you can also add users but that is much easier to do from the popup tool because you can look up users by email address there.','flickpress') . '</p>
		  <form name="flickpress" method="post">
		  <p class="submit"><input type="submit" name="Submit" value="' . __('Update','flickpress') . ' &raquo;" /></p>
		  <input type="hidden" name="flickpress_update" value="update" />
		  <table>
		  <tr>
					 <th scope="col">' . __('flickrid','flickpress') . '</th>
					 <th scope="col">' . __('flickrname','flickpress') . '</th>
					 <th scope="col">' . __('delete','flickpress') . '</th>
		  </tr>
		  <tr>
					 <td><input type="text" name="row1[flickrid]" value="" size="5" /></td>
					 <td><input type="text" name="row1[flickrname]" value="" size="5" /></td>
		<td></td>
		  </tr>
		  ';
		  if ($flickrs = flickpress_getlist()) {
					 $i = 2;
					 foreach ((array)$flickrs as $flick) {
								echo '<tr>
		<td><input type="hidden" name="row' . $i . '[flickrid]" value="' . $flick['flickrid'] . '" /><input type="text" name="row' . $i . '[flickrid]" value="' . $flick['flickrid'] . '" size="5" /></td>
					 <td><input type="text" name="row' . $i . '[flickrname]" value="' . $flick['flickrname'] . '" size="5" /></td>
					 <td><input type="checkbox" name="row' . $i . '[delete]" value="1" /></td>
		  </tr>';
								$i++;
		}
	}
	echo '
					 </table>
					 <p class="submit"><input type="submit" name="Submit" value="' . __('Update','flickpress') . ' &raquo;" /></p>
					 </form>
		  </div>
';
}

function flickpress_add_management_page() {
	global $flickpress_options;
	add_management_page('flickpress', 'flickpress', 'manage_options', basename(__FILE__), 'flickpress_management');
}

add_action('admin_menu', 'flickpress_add_management_page');

// install or update a table in the db for data
function flickpress_table_install() {
		  global $table_prefix, $wpdb;
		  $table_name = $table_prefix . "flickpress";
		  $cache_table_name = $table_prefix . "flickpress_cache";
		  $sql = "CREATE TABLE ".$table_name." (
					 flickrid varchar(20) NULL,
					 flickrname varchar(30) NULL
		  );
	CREATE TABLE ".$cache_table_name." (
		request CHAR( 35 ) NOT NULL ,
		response MEDIUMTEXT NOT NULL ,
		expiration DATETIME NOT NULL ,
		INDEX ( request )
	);";
		  require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
		  dbDelta($sql);
}

function flickpress_delete($id) {
		  global $wpdb, $table_prefix;
		  $table_name = $table_prefix . "flickpress";
		  $id = $wpdb->escape($id);
		  if ($wpdb->query("DELETE FROM $table_name WHERE binary flickrid = '$id'")) {
					 return TRUE;
		  } else {
					 return FALSE;
		  }
}

// run the table installer when the plugin is activated
if (isset($_GET['activate']) && $_GET['activate'] == 'true') {
		  add_action('init', 'flickpress_table_install');
}

// a simple template function to display photos in a sidebar or somesuch
function flickpress_photos($email,$numphotos=3,$before='',$after='<br />',$fpclass='centered') {
	global $flickpress_options, $table_prefix;
	if (isset($flickpress_options['apikey'])) {
		$flick = new phpFlickr($flickpress_options['apikey']);
		$fcon = "mysql://" . DB_USER . ":" . DB_PASSWORD . "@" . DB_HOST . "/" . DB_NAME;
		$flick->enableCache($type = 'db', $fcon , $cache_expire = 600, $table = $table_prefix.'flickpress_cache');
		$check = $flick->test_echo();
		if ($check['stat'] == 'ok') {
			$user_id = $flick->people_findByEmail($email);
			$user_info = $flick->people_getInfo($user_id['id']);
			$photos_url = $user_info['photosurl'];
			$photos = $flick->people_getPublicPhotos($user_info['id'],NULL,NULL,$numphotos,1);
			foreach ((array)$photos['photos']['photo'] as $photo) {
				$photourl = $flick->buildPhotoURL($photo, "Square");
				$imgcode .= $before . '<a href="' . $photos_url . $photo['id'] . '"><img border="0" alt="' . $photo['title'] . '" title="' . $photo['title'] . '" src="' . $photourl . '" class="' . $fpclass . '" width="75" height="75" /></a>' . $after;
			}
			echo $imgcode;
		}
	}
	return;
}

?>
