<?php
/*
Plugin Name: flickpress
Plugin URI: http://familypress.net/flickpress/
Description: A multi-user Flickr tool plus widget. Creates database tables to store Flickr ids and cache data. Uses Dan Coulter's excellent phpFlickr class. Requires a Flickr API key.
Version: 1.9.3
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

function fp_add_quicktags() {
?>
<script type="text/javascript" charset="utf-8">
	QTags.addButton( id='flickpress', display='Flickr', arg1=edflickpress, arg2='', access_key='f', title='Browse and Insert Flickr Photos', priority='');
</script>
<?php
}

// Load the javascript for the popup tool.
function flickpress_popup_javascript() {
	global $post_ID, $temp_ID;
	$parent_ID = (int) (0 == $post_ID ? $temp_ID : $post_ID);
   echo '
<script type="text/javascript">
//<![CDATA[
function edflickpress() {
   tb_show("' . __('Browse and Insert Flickr Photos','flickpress') . '","' . WP_PLUGIN_URL . '/flickpress/popup.php?fpaction=users&fp_parent_ID=' . $parent_ID . '&TB_iframe=true",false);
}
//]]>
</script>
';
}

function flickpress_options_init() {
	register_setting('flickpressoptions_options','flickpress_options','flickpress_sanitize');
}

function flickpress_sanitize($input) {
	$output = array();
	$output['apikey'] = wp_filter_nohtml_kses($input['apikey']);
	$output['usecap'] = wp_filter_nohtml_kses($input['usecap']);
	$output['insclass'] = wp_filter_nohtml_kses($input['insclass']);
	$output['untitled'] = wp_filter_nohtml_kses($input['untitled']);
	$output['captions'] = wp_filter_nohtml_kses($input['captions']);
	$output['captype'] = wp_filter_nohtml_kses($input['captype']);
	$output['caporder'] = wp_filter_nohtml_kses($input['caporder']);
	$output['before'] = wp_filter_post_kses($input['before']);
	$output['between'] = wp_filter_post_kses($input['between']);
	$output['after'] = wp_filter_post_kses($input['after']);
	$output['thickbox'] = wp_filter_nohtml_kses($input['thickbox']);
	$output['tbcode'] = wp_filter_nohtml_kses($input['tbcode']);
	$output['license'] = array();
	if (isset($input['license'])) {
		foreach ($input['license'] as $license) {
			$output['license'][] = (int)$license;
		}
	}
	return $output;
}

// only users with "manage_options" permission should see the Options page
function flickpress_add_options_page() {
	add_options_page('flickpress', 'flickpress', 'manage_options', 'flickpress_options', 'flickpress_options_subpanel');
}

// generates the flickpress Options subpanel
function flickpress_options_subpanel() {
	global $table_prefix;
	echo '
	<div class="wrap">
	<h2>' . __('flickpress options','flickpress') . '</h2>
	<form method="post" action="options.php">
';
	settings_fields('flickpressoptions_options');
	$flickpress_options = get_option('flickpress_options');
	// set some defaults
	if (empty($flickpress_options['caporder'])) {
		// set defaults for between and untitled text here
		// so users can set them to be empty if they really want to
		$flickpress_options['between'] = ' by ';
		$flickpress_options['untitled'] = '(untitled)';
		$flickpress_options['caporder'] = 'titleauthor';
	}
	if (empty($flickpress_options['usecap']))
		$flickpress_options['usecap'] = 'edit_posts';
	if (empty($flickpress_options['thickbox']))
		$flickpress_options['thickbox'] = 'no';
	if (empty($flickpress_options['tbcode']))
		$flickpress_options['tbcode'] = '';
	if (!empty($flickpress_options['apikey'])) {
		if (!flickpress_check_key($flickpress_options['apikey'])) {
			echo "\n<div class='updated fade'><p><strong>Error:</strong> Flickr may be down or very slow, your internet connection may be down or very slow, your Flickr API key may be invalid, or the Flickr API may have changed. If Flickr is up, your connection is fine, and your key is correct please check for a plugin update.</p></div>\n";
			$key_works = false;
		} else {
			$key_works = true;
		}
	} else {
		$key_works = false;
	}
	if (!current_user_can($flickpress_options['usecap'])) {
		// they're an admin, so the capability must be wrong...
		echo "\n<div class='updated fade'><p><strong>Error:</strong> The capability you have entered below is incorrect.</p></div>\n";
	}
	echo '
	<fieldset class="options">
	<table class="form-table">
		<tbody>
					 <tr>
								<th scope="row">' . __('Flickr API key:','flickpress') . '</th>
								<td><input name="flickpress_options[apikey]" type="text" value="' . $flickpress_options['apikey'] . '" size="30"><br />
					 ' . __('Enter your <a href="http://flickr.com/services/api/keys/">Flickr API key</a> here. This is required for the plugin to work.','flickpress') . '</td>
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
         <th scope="row">' . __('Caption layout:','flickpress') . "</th>\n<td>";
   if (empty($flickpress_options['caporder']) || ($flickpress_options['caporder'] == 'titleauthor')) {
      echo '<label><input name="flickpress_options[caporder]" type="radio" value="titleauthor" size="5" checked="checked"> ' . __('Title then author','flickpress') . '</label><br />';
      echo '<label><input name="flickpress_options[caporder]" type="radio" value="authortitle" size="5"> ' . __('Author then title','flickpress') . '</label><br />';
      echo '<label><input name="flickpress_options[caporder]" type="radio" value="titleonly" size="5"> ' . __('Title only','flickpress') . '</label><br />';
   } elseif ($flickpress_options['caporder'] == 'titleonly') {
      echo '<label><input name="flickpress_options[caporder]" type="radio" value="titleauthor" size="5"> ' . __('Title then author','flickpress') . '</label><br />';
      echo '<label><input name="flickpress_options[caporder]" type="radio" value="authortitle" size="5"> ' . __('Author then title','flickpress') . '</label><br />';
      echo '<label><input name="flickpress_options[caporder]" type="radio" value="titleonly" size="5" checked="checked"> ' . __('Title only','flickpress') . '</label><br />';
   } else {
      echo '<label><input name="flickpress_options[caporder]" type="radio" value="titleauthor" size="5"> ' . __('Title then author','flickpress') . '</label><br />';
      echo '<label><input name="flickpress_options[caporder]" type="radio" value="authortitle" size="5" checked="checked"> ' . __('Author then title','flickpress') . '</label><br />';
      echo '<label><input name="flickpress_options[caporder]" type="radio" value="titleonly" size="5"> ' . __('Title only','flickpress') . '</label><br />';
   }
   echo __('Configure the caption layout here.','flickpress') . '</td>
      </tr>
      <tr>
         <th scope="row">' . __('Before caption text:','flickpress') . '</th>
         <td><input name="flickpress_options[before]" type="text" value="' . htmlentities(wp_kses_stripslashes($flickpress_options['before'])) . '" size="50"><br />
      ' . __('Text placed before the caption.','flickpress') . '</td>
      </tr>
      <tr>
         <th scope="row">' . __('Between caption text:','flickpress') . '</th>
         <td><input name="flickpress_options[between]" type="text" value="' . htmlentities(wp_kses_stripslashes($flickpress_options['between'])) . '" size="50"><br />
      ' . __('Text placed between the caption parts.','flickpress') . '</td>
      </tr>
      <tr>
         <th scope="row">' . __('After caption text:','flickpress') . '</th>
         <td><input name="flickpress_options[after]" type="text" value="' . htmlentities(wp_kses_stripslashes($flickpress_options['after'])) . '" size="50"><br />
      ' . __('Text placed after the caption.','flickpress') . '</td>
      </tr>
      <tr>
         <th scope="row">' . __('Lightbox support:','flickpress') . "</th>\n<td>";
   if ($flickpress_options['thickbox'] == 'yes') {
      echo '<label><input name="flickpress_options[thickbox]" type="radio" value="yes" size="5" checked="checked"> ' . __('ThickBox','flickpress') . '</label><br />';
      echo '<label><input name="flickpress_options[thickbox]" type="radio" value="custom" size="5"> ' . __('Custom','flickpress') . '</label><br />';
      echo '<label><input name="flickpress_options[thickbox]" type="radio" value="no" size="5"> ' . __('Off','flickpress') . '</label><br />';
   } elseif ($flickpress_options['thickbox'] == 'custom') {
      echo '<label><input name="flickpress_options[thickbox]" type="radio" value="yes" size="5"> ' . __('ThickBox','flickpress') . '</label><br />';
      echo '<label><input name="flickpress_options[thickbox]" type="radio" value="custom" size="5" checked="checked"> ' . __('Custom','flickpress') . '</label><br />';
      echo '<label><input name="flickpress_options[thickbox]" type="radio" value="no" size="5"> ' . __('Off','flickpress') . '</label><br />';
   } else {
      echo '<label><input name="flickpress_options[thickbox]" type="radio" value="yes" size="5"> ' . __('ThickBox','flickpress') . '</label><br />';
      echo '<label><input name="flickpress_options[thickbox]" type="radio" value="custom" size="5"> ' . __('Custom','flickpress') . '</label><br />';
      echo '<label><input name="flickpress_options[thickbox]" type="radio" value="no" size="5" checked="checked"> ' . __('Off','flickpress') . '</label><br />';
   }
   echo __('Add lightbox support for inserted images. Enabling <strong>ThickBox</strong> will also add the necessary JavaScript for ThickBox to work. Enable <strong>Custom</strong> if you would like to use a different lightbox system or if you want to use ThickBox but do not want the JavaScript added for you.','flickpress') . '</td>
      </tr>
      <tr>
         <th scope="row">' . __('Custom lightbox code:','flickpress') . '</th>
         <td><input name="flickpress_options[tbcode]" type="text" value="' . htmlentities(wp_kses_stripslashes($flickpress_options['tbcode'])) . '" size="20"><br />
      ' . __('Enter a custom HTML attribute or class here if your lightbox method requires it (most lightbox plugins do not). Format the code like <code>class="thickbox"</code> or <code>rel="lightbox"</code>.','flickpress') . '</td>
      </tr>';
	if ($key_works) {
		echo '
      <tr>
         <th scope="row">' . __('Default licenses for CC photo searches:','flickpress') . '</th>
         <td>';
		$phpflickpress = new phpFlickpress($flickpress_options['apikey']);
		$fcon = "mysql://" . DB_USER . ":" . DB_PASSWORD . "@" . DB_HOST . "/" . DB_NAME;
		$phpflickpress->enableCache($type = 'db', $fcon , $cache_expire = 600, $table = $table_prefix.'flickpress_cache');
		$fplicenses = $phpflickpress->photos_licenses_getInfo();
		foreach ($fplicenses as $fplicense) {
			if ($fplicense['id'] !== '0') {
				if (!isset($flickpress_options['license'])) {
					$checked = 'checked="checked" ';
				} else {
					if (in_array($fplicense['id'],$flickpress_options['license'])) {
						$checked = 'checked="checked" ';
					} else {
						$checked = '';
					}
				}
				echo '<label><input name="flickpress_options[license][]" type="checkbox" value="' . $fplicense['id'] . '" ' . $checked . '"/> ' . $fplicense['name'] . ' <a href="' . $fplicense['url'] . '">' . __('(about)','flickpress') . '</a></label><br />';
			}
		}
	echo '
			</td>
      </tr>';
	}
	echo '
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
	global $wpdb;
	$flickpress_options = get_option('flickpress_options');
	$table_name = $wpdb->prefix . "flickpress";
	if (isset($_POST['flickpress_update'])) {
		check_admin_referer();
		$phpflickpress = new phpFlickpress($flickpress_options['apikey']);
		$fcon = "mysql://" . DB_USER . ":" . DB_PASSWORD . "@" . DB_HOST . "/" . DB_NAME;
		$phpflickpress->enableCache($type = 'db', $fcon , $cache_expire = 600, $table = $wpdb->prefix.'flickpress_cache');
		if (isset($_POST['flickrmail'])) {
		if (!empty($_POST['flickrmail'])) {
			if (strpos($_POST['flickrmail'],'@') === FALSE) {
				$fpuseradd_info = $phpflickpress->people_findByUsername($_POST['flickrmail']);
			} else {
				$fpuseradd_info = $phpflickpress->people_findByEmail($_POST['flickrmail']);
			}
			if ($fpuseradd_info) {
				if (flickpress_is_user($fpuseradd_info['id'])) {
					$fpmessage = $fpuseradd_info['username'] . __(' has already been added.','flickpress');
				} else {
					$fpupdate_array = array('flickrid'=>$fpuseradd_info['id'],'flickrname'=>$fpuseradd_info['username']);
					if (flickpress_update($fpupdate_array)) {
						$fpmessage = $fpuseradd_info['username'] . __(' has been added.','flickpress');
					} else {
						$fpmessage =  __('Failed to add user for some reason.','flickpress');
					}
				}
			} else {
				$fpmessage =  __('No Flickr user found!','flickpress');
			}
		}
		}
		$dels = 0;
		foreach ((array)$_POST as $val) {
			if (isset($val['delete'])) {
				if ($val['delete'] == '1') {
					if (flickpress_delete($val['flickrid']))
						$dels++;
				}
			}
		}
		if (($dels > 0) || (isset($fpmessage)))
			echo "<div class='updated'>";
		if ($dels == 1) {
			echo __('One user removed. ','flickpress');
		} elseif ($dels > 1) {
			echo $dels . __(' users removed. ','flickpress');
		}
		if (isset($fpmessage))
			echo $fpmessage;
		if (($dels > 0) || (isset($fpmessage)))
			echo "</div>\n";
	}
	echo '
		  <div class="wrap">
		  <h2>' . __('flickpress manager','flickpress') . '</h2>
		  <p>' . __('You can manually manage flickpress users here. It is possible to add users here but that is much easier to do from the popup tool because you can look up users by email address there.','flickpress') . '</p>
		  <form name="flickpress" method="post">
' . wp_nonce_field('flickpress-manager') . '
		  <p class="submit"><input type="submit" name="Submit" value="' . __('Update','flickpress') . ' &raquo;" /></p>
		  <input type="hidden" name="flickpress_update" value="update" />
			<p><strong>' . __('Add by email or Flickr username:','flickpress') . '</strong> <input type="text" name="flickrmail" value="" size="30" /></p>
		  <table>
		  <tr>
					 <th scope="col">' . __('Flickr ID','flickpress') . '</th>
					 <th scope="col">' . __('Flickr name','flickpress') . '</th>
					 <th scope="col">' . __('delete','flickpress') . '</th>
		  </tr>
		  ';
		  if ($flickrs = flickpress_getlist()) {
					 $i = 1;
					 foreach ((array)$flickrs as $flick) {
								echo '<tr>
		<td><input type="hidden" name="row' . $i . '[flickrid]" value="' . $flick['flickrid'] . '" /><input type="text" disabled="disabled" name="row' . $i . '[flickrid]" value="' . $flick['flickrid'] . '" size="15" /></td>
					 <td><a href="http://flickr.com/people/' . $flick['flickrid'] . '/">' . $flick['flickrname'] . '</a></td>
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
	add_management_page('flickpress', 'flickpress', 'manage_options', basename(__FILE__), 'flickpress_management');
}

add_action('admin_menu', 'flickpress_add_management_page');

// install or update a table in the db for data
function flickpress_table_install() {
	global $wpdb;
	$table_name = $wpdb->prefix . "flickpress";
	$cache_table_name = $wpdb->prefix . "flickpress_cache";
	$sql = "CREATE TABLE ".$table_name." (
			flickrid varchar(20) NULL,
			flickrname varchar(30) NULL
		);
		CREATE TABLE ".$cache_table_name." (
			request CHAR( 35 ) NOT NULL,
			response MEDIUMTEXT NOT NULL,
			expiration DATETIME NOT NULL,
			INDEX ( request )
		) DEFAULT CHARACTER SET UTF8;";
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
}

function flickpress_delete($id) {
	global $wpdb;
	$table_name = $wpdb->prefix . "flickpress";
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
// echoes if it works, fails quietly
function flickpress_photos($email,$numphotos=3,$before='',$after='<br />',$fpclass='centered') {
	global $table_prefix;
	$flickpress_options = get_option('flickpress_options');
	if (!flickpress_check_key($flickpress_options['apikey'])) {
		return;
	} else {
		$flick = new phpFlickpress($flickpress_options['apikey']);
		$fcon = "mysql://" . DB_USER . ":" . DB_PASSWORD . "@" . DB_HOST . "/" . DB_NAME;
		$flick->enableCache($type = 'db', $fcon , $cache_expire = 600, $table = $table_prefix.'flickpress_cache');
		if (isset($email)) {
			$isemail = strpos('@',$email);
			 if (strpos($email,'@') === FALSE) {
				$user_id = $flick->people_findByUsername($email);
			} else {
				$user_id = $flick->people_findByEmail($email);
			}
			$user_info = $flick->people_getInfo($user_id['id']);
			$photos_url = $user_info['photosurl'];
			$photos = $flick->people_getPublicPhotos($user_info['id'],NULL,NULL,$numphotos,1);
		} else {
			return;
		}
		if ($photos['photos']['total'] < 1) {
			return;
		} else {
			$imgcode = '';
			foreach ((array)$photos['photos']['photo'] as $photo) {
				$photourl = $flick->buildPhotoURL($photo, "Square");
				$imgcode .= $before . '<a href="' . $photos_url . $photo['id'] . '"><img alt="' . $photo['title'] . '" title="' . $photo['title'] . '" src="' . $photourl . '" class="' . $fpclass . '" width="75" height="75" /></a>' . $after;
			}
			return $imgcode;
		}
	}
}
add_action( 'admin_print_footer_scripts', 'fp_add_quicktags', 100 );
add_action('admin_print_scripts','flickpress_popup_javascript');
add_filter('mce_external_plugins','flickpress_mce_external_plugins');
add_filter('mce_buttons','flickpress_mce_buttons');
add_action('admin_init','flickpress_options_init');
add_action('admin_menu', 'flickpress_add_options_page');

// flickpressWidget Class 
class flickpressWidget extends WP_Widget {
	// constructor
	function flickpressWidget() {
		$widget_ops = array('description' => __( "Display recent photos from a Flickr account" ) );
		$this->WP_Widget('flickpress', __('Flickr Photos'), $widget_ops);
	}

	// @see WP_Widget::widget  - display the widget
	function widget($args, $instance) {
		extract( $args );
		$title = apply_filters('widget_title', $instance['title']);
		$style = $instance['style'];
		$email = $instance['email'];
		$number = (int)$instance['number'];
		$after = stripslashes(html_entity_decode($instance['after'],ENT_QUOTES));
		$before = stripslashes(html_entity_decode($instance['before'],ENT_QUOTES));
		?>
			<?php echo $before_widget; ?>
				<?php if ( $title )
					echo $before_title . $title . $after_title; ?>
				<?php if ( function_exists('flickpress_photos') ) {
					if ($images = flickpress_photos($email,$number,$before,$after,$style))
						echo flickpress_photos($email,$number,$before,$after,$style);
				} ?>
			<?php echo $after_widget; ?>
		<?php
	} // function widget

	// @see WP_Widget::update  - process the options
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['email'] = strip_tags($new_instance['email']);
		$instance['style'] = strip_tags(stripslashes($new_instance['style']));
		$instance['number'] = strip_tags(stripslashes($new_instance['number']));
		if ( current_user_can('unfiltered_html') ) {
			$instance['before'] =  $new_instance['before'];
			$instance['after'] =  $new_instance['after'];
		} else {
			$instance['before'] = stripslashes( wp_filter_post_kses( addslashes($new_instance['before']) ) );
			$instance['after'] = stripslashes( wp_filter_post_kses( addslashes($new_instance['after']) ) );
		}
		return $instance;
	} // function update

	// @see WP_Widget::form  - output the options form
	function form($instance) {
		$instance = wp_parse_args( (array) $instance,  array('title'=>'', 'style'=>'', 'email'=>'', 'number'=>'1', 'widgets', 'before'=>'<p>', 'after'=>'</p>') );
		$title = htmlspecialchars($instance['title'], ENT_QUOTES);
		$style = htmlspecialchars($instance['style'], ENT_QUOTES);
		$email = htmlspecialchars($instance['email'], ENT_QUOTES);
		$before = format_to_edit($instance['before']);
		$after = format_to_edit($instance['after']);
		$number = (int)$instance['number'];
		?>
			<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>

			<p><label for="<?php echo $this->get_field_id('style'); ?>"><?php _e('Style class:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('style'); ?>" name="<?php echo $this->get_field_name('style'); ?>" type="text" value="<?php echo $style; ?>" /></label></p>

			<p><label for="<?php echo $this->get_field_id('before'); ?>"><?php _e('Before each image:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('before'); ?>" name="<?php echo $this->get_field_name('before'); ?>" type="text" value="<?php echo $before; ?>" /></label></p>

			<p><label for="<?php echo $this->get_field_id('after'); ?>"><?php _e('After each image:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('after'); ?>" name="<?php echo $this->get_field_name('after'); ?>" type="text" value="<?php echo $after; ?>" /></label></p>

			<p><label for="<?php echo $this->get_field_id('email'); ?>"><?php _e('Flickr email or username:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('email'); ?>" name="<?php echo $this->get_field_name('email'); ?>" type="text" value="<?php echo $email; ?>" /></label></p>

		
			<p><label for="<?php echo $this->get_field_id('number'); ?>"><?php _e('Number of images:'); ?><select id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>">
			<?php
			for ($i=1;$i<=10;$i++) {
				echo '<option value="' . $i . '"';
				if ($number == $i) {
					echo ' selected="selected"';
				}
				echo '>' . $i . "</option>\n";
			}
			?>
			</select></label></p>
		<?php
	} // function form
} // class flickpressWidget

// register flickpressWidget widget
add_action('widgets_init', create_function('', 'return register_widget("flickpressWidget");'));

// Add the thickbox stuff if the option is set
function flickpress_scripts() {
	$flickpress_options = get_option('flickpress_options');
	if ($flickpress_options['thickbox'] == 'yes')
		wp_enqueue_script('thickbox');
}

add_action('wp_print_scripts','flickpress_scripts');

function flickpress_load_tb_fix() {
	$flickpress_options = get_option('flickpress_options');
	if ($flickpress_options['thickbox'] == 'yes')
		echo "\n" . '<script type="text/javascript">tb_pathToImage = "' . get_option('siteurl') . '/wp-includes/js/thickbox/loadingAnimation.gif";tb_closeImage = "' . get_option('siteurl') . '/wp-includes/js/thickbox/tb-close.png";</script>'. "\n";
}

add_action('wp_footer', 'flickpress_load_tb_fix');

function flickpress_shortcode_tag ($atts) {
	$atts_array = shortcode_atts(array(
		'number' => '3',
		'before' => '<p>',
		'after' => '</p>',
		'style' => 'none',
		'email' => '',
	), $atts);
	extract($atts_array);
	$before = stripslashes(html_entity_decode($before,ENT_QUOTES));
	$after = stripslashes(html_entity_decode($after,ENT_QUOTES));
	if ($images = flickpress_photos($email,$number,$before,$after,$style)) {
		return $images;
	} else {
		return;
	}
}

add_shortcode('flickpress','flickpress_shortcode_tag');

?>
