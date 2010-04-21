<?php
/*
Plugin Name: flickpress
Plugin URI: http://familypress.net/flickpress/
Description: A multi-user Flickr tool plus widget. Creates database tables to store Flickr ids and cache data. Uses Dan Coulter's excellent phpFlickr class. Requires a Flickr API key.
Version: 1.9
Author: Isaac Wedin
Author URI: http://familypress.net/
*/

/* Copyright 2009, 2010 Isaac Wedin (email : isaac@familypress.net)
This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version. 
http://www.opensource.org/licenses/gpl-license.php

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

This program makes use of Dan Coulter's phpflickr library, which is licensed separately - see the included version of the library for details.
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
   tb_show("' . __('flickpress: insert Flickr photos','flickpress') . '","' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fpaction=users&amp;TB_iframe=true",false);
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
	$input['insclass'] = wp_filter_nohtml_kses($input['insclass']);
	$input['untitled'] = wp_filter_nohtml_kses($input['untitled']);
	$input['captions'] = wp_filter_nohtml_kses($input['captions']);
	$input['captype'] = wp_filter_nohtml_kses($input['captype']);
	$input['caporder'] = wp_filter_nohtml_kses($input['caporder']);
	$input['before'] = htmlentities(str_replace('"',"'",$input['before']),ENT_QUOTES);
	$input['between'] = htmlentities(str_replace('"',"'",$input['between']),ENT_QUOTES);
	$input['after'] = htmlentities(str_replace('"',"'",$input['after']),ENT_QUOTES);
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
	if (empty($flickpress_options['untitled']))
		$flickpress_options['untitled'] = '(untitled)';
	if (empty($flickpress_options['usecap']))
		$flickpress_options['usecap'] = 'edit_posts';
	if (!empty($flickpress_options['apikey'])) {
		if (!flickpress_check_key($flickpress_options['apikey'])) {
			echo "\n<div id='flickpress-warning' class='updated fade'><p><strong>Error:</strong> Your Flickr API key seems to be invalid, please verify it is correct. This can also mean the Flickr API itself has changed, so if your key is correct please check for a plugin update.</p></div>\n";
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
         <th scope="row">' . __('Class for captioned photos:','flickpress') . '</th>
         <td><input name="flickpress_options[insclass]" type="text" value="' . $flickpress_options['insclass'] . '" size="60"><br />
      ' . __('This class is applied to the container <code>div</code> for standard captioned images, or to the <code>img</code> tag for other types of inserted images. You should probably use either <code>alignnone</code> or <code>aligncenter</code>, but your theme may offer other options.','flickpress') . '</td>
      </tr>
		<tr>
			<th scope="row">' . __('Untitled photo text:','flickpress') . '</th>
         <td><input name="flickpress_options[untitled]" type="text" value="' . $flickpress_options['untitled'] . '" size="20"><br />
		' . __('Set the text used in caption links for untitled photos here.','flickpress') . '</td>
		</tr>
      <tr>
         <th scope="row">' . __('Captions for inserted photos:','flickpress') . "</th>\n<td>";
	if (empty($flickpress_options['captions']) || ($flickpress_options['captions'] == 'yes')) {
		echo '<label><input name="flickpress_options[captions]" type="radio" value="yes" size="5" checked="checked"> ' . __('On','flickpress') . '</label><br />';
		echo '<label><input name="flickpress_options[captions]" type="radio" value="no" size="5"> ' . __('Off','flickpress') . '</label><br />';
	} else {
		echo '<label><input name="flickpress_options[captions]" type="radio" value="yes" size="5"> ' . __('On','flickpress') . '</label><br />';
		echo '<label><input name="flickpress_options[captions]" type="radio" value="no" size="5" checked="checked"> ' . __('Off','flickpress') . '</label><br />';
	}
	echo __('This setting turns captions on or off. Note that the option to add a caption remains available, which is useful if you mostly use your own images but occasionally use images that require attribution.','flickpress') . '</td>
      </tr>
      <tr>
         <th scope="row">' . __('Caption type:','flickpress') . "</th>\n<td>";
	if (empty($flickpress_options['captype']) || ($flickpress_options['captype'] == 'default')) {
		echo '<label><input name="flickpress_options[captype]" type="radio" value="default" size="5" checked="checked"> ' . __('Default','flickpress') . '</label><br />';
		echo '<label><input name="flickpress_options[captype]" type="radio" value="simple" size="5"> ' . __('Simple','flickpress') . '</label><br />';
	} else {
		echo '<label><input name="flickpress_options[captype]" type="radio" value="default" size="5"> ' . __('Default','flickpress') . '</label><br />';
		echo '<label><input name="flickpress_options[captype]" type="radio" value="simple" size="5" checked="checked"> ' . __('Simple','flickpress') . '</label><br />';
	}
	echo __('<strong>Default</strong> produces normal WordPress captions. <strong>Simple</strong> places the caption text below the image without a surrounding div, useful if you wish to move the caption to the bottom of your post.','flickpress') . '</td>
      </tr>
      <tr>
         <th scope="row">' . __('Caption order:','flickpress') . "</th>\n<td>";
   if (empty($flickpress_options['caporder']) || ($flickpress_options['caporder'] == 'titleauthor')) {
      echo '<label><input name="flickpress_options[caporder]" type="radio" value="titleauthor" size="5" checked="checked"> ' . __('Title then author','flickpress') . '</label><br />';
      echo '<label><input name="flickpress_options[caporder]" type="radio" value="authortitle" size="5"> ' . __('Author then title','flickpress') . '</label><br />';
   } else {
      echo '<label><input name="flickpress_options[caporder]" type="radio" value="titleauthor" size="5"> ' . __('Title then author','flickpress') . '</label><br />';
      echo '<label><input name="flickpress_options[caporder]" type="radio" value="authortitle" size="5" checked="checked"> ' . __('Author then title','flickpress') . '</label><br />';
   }
   echo __('Configure the caption layout here.','flickpress') . '</td>
      </tr>
      <tr>
         <th scope="row">' . __('Before caption text:','flickpress') . '</th>
         <td><input name="flickpress_options[before]" type="text" value="' . $flickpress_options['before'] . '" size="20"><br />
      ' . __('Text placed before the caption.','flickpress') . '</td>
      </tr>
      <tr>
         <th scope="row">' . __('Between caption text:','flickpress') . '</th>
         <td><input name="flickpress_options[between]" type="text" value="' . $flickpress_options['between'] . '" size="20"><br />
      ' . __('Text placed between the caption parts.','flickpress') . '</td>
      </tr>
      <tr>
         <th scope="row">' . __('After caption text:','flickpress') . '</th>
         <td><input name="flickpress_options[after]" type="text" value="' . $flickpress_options['after'] . '" size="20"><br />
      ' . __('Text placed after the caption.','flickpress') . '</td>
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
		  <p>' . __('You can manually manage flickpress users here. It is possible to add users here but that is much easier to do from the popup tool because you can look up users by email address there.','flickpress') . '</p>
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

function flickpress_uninstall() {
	global $wpdb;
	$cache_table = $wpdb->prefix . 'flickpress_cache';
	$wpdb->query("DROP TABLE IF EXISTS $cache_table");
}

// activation and deactivation hooks
register_activation_hook( __FILE__, 'flickpress_table_install');
register_deactivation_hook( __FILE__, 'flickpress_uninstall');

// a simple template function to display photos in a sidebar or somesuch
function flickpress_photos($email,$numphotos=3,$before='',$after='<br />',$fpclass='centered') {
	$flickpress_options = get_option('flickpress_options');
	global $table_prefix;
	if (isset($flickpress_options['apikey'])) {
		$flick = new phpFlickpress($flickpress_options['apikey']);
		$fcon = "mysql://" . DB_USER . ":" . DB_PASSWORD . "@" . DB_HOST . "/" . DB_NAME;
		$flick->enableCache($type = 'db', $fcon , $cache_expire = 600, $table = $table_prefix.'flickpress_cache');
		$check = $flick->photos_getRecent(NULL,1,1);
		if ($check['page'] == 1) {
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

function widget_fpwidg_init() {
	// Check for the required plugin functions. This will prevent fatal
	// errors occurring when you deactivate the dynamic-sidebar plugin.
	if ( !function_exists('register_sidebar_widget') || !function_exists('flickpress_photos'))
		return;

	function widget_fpwidg($args) {
		// $args is an array of strings that help widgets to conform to
		// the active theme: before_widget, before_title, after_widget,
		// and after_title are the array keys. Default tags: li and h2.
		extract($args);
		// Each widget can store its own options. We keep strings here.
		$options = get_option('widget_fpwidg');
		$title = $options['title'];
		$fpclass = $options['style'];
		$after = stripslashes(html_entity_decode($options['after'],ENT_QUOTES));
		$before = stripslashes(html_entity_decode($options['before'],ENT_QUOTES));
		$email = $options['email'];
		$numphotos = (int)$options['number'];
		// These lines generate our output.
		// First echo out the required stuff.
		echo $before_widget . $before_title . $title . $after_title;
		// Now display the Flickr photo(s).
		flickpress_photos($email,$numphotos,$before,$after,$fpclass);
		// And echo the required after-widget bit.
		echo $after_widget;
	}

	// This is the function that outputs the form to let the users edit
	// the widget's title and number of images displayed.
	function widget_fpwidg_control() {

		// Get our options and see if we're handling a form submission.
		$options = get_option('widget_fpwidg');
		if ( !is_array($options) )
			$options = array('title'=>'', 'style'=>'', 'email'=>'', 'number'=>'1', 'widgets', 'before'=>'<p>', 'after'=>'</p>');
		if ( $_POST['fpwidg-submit'] ) {
			$options['title'] = strip_tags(stripslashes($_POST['fpwidg-title']));
			$options['email'] = sanitize_email($_POST['fpwidg-email']);
			$options['style'] = strip_tags(stripslashes($_POST['fpwidg-style']));
			$options['number'] = strip_tags(stripslashes($_POST['fpwidg-number']));
			// Swap double for single quotes.
			$options['before'] = htmlentities(str_replace('"',"'",$_POST['fpwidg-before']),ENT_QUOTES);
			$options['after'] = htmlentities(str_replace('"',"'",$_POST['fpwidg-after']),ENT_QUOTES);
			update_option('widget_fpwidg', $options);
		}
		$title = htmlspecialchars($options['title'], ENT_QUOTES);
		$style = htmlspecialchars($options['style'], ENT_QUOTES);
		$email = htmlspecialchars($options['email'], ENT_QUOTES);
		$before = stripslashes(html_entity_decode($options['before'], ENT_QUOTES));
		$after = stripslashes(html_entity_decode($options['after'], ENT_QUOTES));
		$number = (int)$options['number'];
		// Here is our little form segment. Notice that we don't need a
		// complete form. This will be embedded into the existing form.
		echo '<p style="text-align:right;"><label for="fpwidg-title">' . __('Title:') . ' <input style="width: 100px;" id="fpwidg-title" name="fpwidg-title" type="text" value="'.$title.'" /></label></p>';
		echo '<p style="text-align:right;"><label for="fpwidg-style">' . __('Style class:') . ' <input style="width: 100px;" id="fpwidg-style" name="fpwidg-style" type="text" value="'.$style.'" /></label></p>';
		echo '<p style="text-align:right;"><label for="fpwidg-before">' . __('Before each image:') . ' <input style="width: 100px;" id="fpwidg-before" name="fpwidg-before" type="text" value="'.$before.'" /></label></p>';
		echo '<p style="text-align:right;"><label for="fpwidg-after">' . __('After each image:') . ' <input style="width: 100px;" id="fpwidg-after" name="fpwidg-after" type="text" value="'.$after.'" /></label></p>';
		echo '<p style="text-align:right;"><label for="fpwidg-email">' . __('Flickr email:') . ' <input style="width: 100px;" id="fpwidg-email" name="fpwidg-email" type="text" value="'.$email.'" /></label></p>';
		echo '<p style="text-align:right;"><label for="fpwidg-number">' . __('Number of images:') . '<select id="fpwidg-number" name="fpwidg-number"' . ">\n";
		for ($i=1;$i<=10;$i++) {
			echo '<option value="' . $i . '"';
				if ($number == $i) {
					echo ' selected="selected"';
				}
			echo '>' . $i . "</option>\n";
      }
		echo "</select>\n" . '</label></p>';
		echo '<input type="hidden" id="fpwidg-submit" name="fpwidg-submit" value="1" />';
	}
	
	// This registers our widget so it appears with the other available
	// widgets and can be dragged and dropped into any active sidebars.
	register_sidebar_widget(array('flickpress', 'widgets'), 'widget_fpwidg');

	// This registers our optional widget control form. Because of this
	// our widget will have a button that reveals a 300x100 pixel form.
	register_widget_control(array('flickpress', 'widgets'), 'widget_fpwidg_control');
}

// Run our code later in case this loads prior to any required plugins.
add_action('widgets_init', 'widget_fpwidg_init');

?>
