<?php
/*
Plugin Name: flickpress
Plugin URI: http://familypress.net/flickpress/
Description: A multi-user flickr tool for WordPress. Creates tables to store Flickr ids and cache data. Last tested and working with WordPress 2.8. Uses Dan Coulter's excellent phpFlickr class. Requires a Flickr API key.
Version: 0.9
Author: Isaac Wedin
Author URI: http://familypress.net/
*/

/* Copyright 2009  Isaac Wedin (email : isaac@familypress.net)
This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version. 

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

This program makes use of Dan Coulter's phpflickr library, which is licensed under the GNU Lesser GPL - see the included version of the library for details.
*/

require_once(ABSPATH . 'wp-content/plugins/flickpress/include.php');

$flickpress_url = get_bloginfo('wpurl') . '/wp-content/plugins/flickpress';

function flickpress_mce_buttons($buttons) {
   array_push($buttons, "flickpress");
   return $buttons;
}

function flickpress_mce_external_plugins($plugins) {
   global $flickpress_url;
   $plugins['flickpress'] = $flickpress_url . '/tinymce/v3/editor_plugin.js';
   return $plugins;
}

// Add a button to the HTML editor (borrowed from Kimili Flash Embed)
function fp_add_quicktags() {
   $buttonshtml = '<input type="button" class="ed_button" onclick="edflickpress(); return false;" title="' . __('Insert Flickr photos','flickpress') . '" value="' . __('Flickr','flickpress') . '" />';
?>
<script type="text/javascript" charset="utf-8">
// <![CDATA[
   (function(){
      if (typeof jQuery === 'undefined') {
         return;
      }
      jQuery(document).ready(function(){
         jQuery("#ed_toolbar").append('<?php echo $buttonshtml; ?>');
      });
   }());
// ]]>
</script>
<?php
}

// Load the javascript for the popup tool.
function flickpress_popup_javascript() {
   echo '
<script type="text/javascript">
//<![CDATA[
function edflickpress() {
   tb_show("' . __('flickpress: insert Flickr photos','flickpress') . '","' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=users&amp;TB_iframe=true",false);
}
//]]>
</script>
';
}

function flickpress_options_init() {
	register_setting('flickpressoptions_options','flickpress_options','flickpress_sanitize');
}

function flickpress_sanitize($input) {
	$input['apikey'] = wp_filter_nohtml_kses($input['apikey']);
	$input['usecap'] = wp_filter_nohtml_kses($input['usecap']);
	return $input;
}

// only users with "manage_options" permission should see the Options page
function flickpress_add_options_page() {
	add_options_page('flickpress', 'flickpress', 'manage_options', 'flickpress_options', 'flickpress_options_subpanel');
}

// generates the flickpress Options subpanel
function flickpress_options_subpanel() {
	echo '
	<div class="wrap">
	<h2>' . __('flickpress options','flickpress') . '</h2>
	<form method="post" action="options.php">
';
	settings_fields('flickpressoptions_options');
	$flickpress_options = get_option('flickpress_options');
	if (empty($flickpress_options['usecap'])) {
		$flickpress_options['usecap'] = 'edit_posts';
	}
	if (!empty($flickpress_options['apikey'])) {
		if (!flickpress_check_key($flickpress_options['apikey'])) {
			echo "\n<div id='flickpress-warning' class='updated fade'><p><strong>Error:</strong> Your Flickr API key seems to be invalid, please verify it is correct.</p></div>\n";
		}
	}
	if (!current_user_can($flickpress_options['usecap'])) { // they're an admin, so the capability *must* be wrong...
		echo "\n<div id='flickpress-warning' class='updated fade'><p><strong>Error:</strong> The capability you have entered below is incorrect.</p></div>\n";
	}
	echo '
	<fieldset class="options">
	<table class="form-table">
		<tbody>
					 <tr>
								<th scope="row">' . __('flickr API key:','flickpress') . '</th>
								<td><input name="flickpress_options[apikey]" type="text" value="' . $flickpress_options['apikey'] . '" size="30"><br />
					 ' . __('Enter your <a href="http://flickr.com/services/api/keys/">flickr API key</a> here. This is required for the plugin to work.','flickpress') . '</td>
					 </tr>
		<tr>
			<th scope="row">' . __('Capability required to use flickpress:','flickpress') . '</th>
			<td><input name="flickpress_options[usecap]" type="text" value="' . $flickpress_options['usecap'] . '" size="20"><br />
		' . __('You should probably use <code>edit_posts</code> but you may use <code>upload_files</code> <code>publish_posts</code> or <a href="http://codex.wordpress.org/Roles_and_Capabilities#Capabilities">any other capability</a>.','flickpress') . '</td>
		</tr>
      <tr>
         <th scope="row">' . __('Captions for inserted photos:','flickpress') . "</th>\n<td>";
	if (empty($flickpress_options['captions']) || ($flickpress_options['captions'] == 'yes')) {
		echo '<label><input name="flickpress_options[captions]" type="radio" value="yes" size="5" checked="checked"> Yes</label><br />';
		echo '<label><input name="flickpress_options[captions]" type="radio" value="no" size="5"> No</label><br />';
	} else {
		echo '<label><input name="flickpress_options[captions]" type="radio" value="yes" size="5"> Yes</label><br />';
		echo '<label><input name="flickpress_options[captions]" type="radio" value="no" size="5" checked="checked"> No</label><br />';
	}
	echo __('This turns captions on or off by default. You can still turn them on or off when inserting photos. If you like captions or mostly use photos that require attribution, turn them on by default. If you dislike captions and mostly use photos that do not require attribution (such as your own), then turn them off.','flickpress') . '</td>
      </tr>
		</tbody>
	</table> 
	</fieldset>

	<p class="submit"><input type="submit" name="Submit" value="' . __('Update Options &raquo;','flickpress') . '" /></p>
</form> 
</div>
';
}

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
					 <th scope="col">' . __('Flickr ID','flickpress') . '</th>
					 <th scope="col">' . __('Flickr name','flickpress') . '</th>
					 <th scope="col">' . __('delete','flickpress') . '</th>
		  </tr>
		  <tr>
					 <td><input type="text" name="row1[flickrid]" value="" size="15" /></td>
					 <td><input type="text" name="row1[flickrname]" value="" size="15" /></td>
		<td></td>
		  </tr>
		  ';
		  if ($flickrs = flickpress_getlist()) {
					 $i = 2;
					 foreach ((array)$flickrs as $flick) {
								echo '<tr>
		<td><input type="hidden" name="row' . $i . '[flickrid]" value="' . $flick['flickrid'] . '" /><input type="text" name="row' . $i . '[flickrid]" value="' . $flick['flickrid'] . '" size="15" /></td>
					 <td><input type="text" name="row' . $i . '[flickrname]" value="' . $flick['flickrname'] . '" size="15" /></td>
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
	$flickpress_options = get_option('flickpress_options');
	global $table_prefix;
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

add_action('edit_form_advanced','fp_add_quicktags');
add_action('edit_page_form','fp_add_quicktags');
add_action('admin_print_scripts','flickpress_popup_javascript');
add_filter('mce_external_plugins','flickpress_mce_external_plugins');
add_filter('mce_buttons','flickpress_mce_buttons');
add_action('admin_init','flickpress_options_init');
add_action('admin_menu', 'flickpress_add_options_page');

$flickpress_options = get_option('flickpress_options');

if ( ( !$flickpress_options || empty($flickpress_options['apikey'])) && !isset($_POST['submit']) ) {
   function flickpress_warning() {
      echo "\n<div id='flickpress-warning' class='updated fade'><p>".sprintf(__('You must <a href="%1$s">enter your Flickr API key</a> for flickpress to work.'), "options-general.php?page=flickpress_options")."</p></div>\n";
   }
   add_action('admin_notices', 'flickpress_warning');
   return;
}

?>
