<?php
/* flickpress includes for use by both popup and the main plugin file */

// get localized
$flickpress_plugin_dir = basename(dirname(__FILE__));
load_plugin_textdomain( 'flickpress', 'wp-content/plugins/' . $flickpress_plugin_dir, $flickpress_plugin_dir );

// get phpflickr stuff
require_once( ABSPATH . 'wp-content/plugins/flickpress/phpflickr/phpFlickr.php');

function flickpress_check_key ($key) {
	$concheck = FALSE;
	$concheck = @fsockopen("flickr.com", 80, $errno, $errstr, 15);
	if (!$concheck) {
		return FALSE;
	} else {
		fclose($concheck);
		global $table_prefix;
		$flick = new phpFlickpress($key);
		$fcon = "mysql://" . DB_USER . ":" . DB_PASSWORD . "@" . DB_HOST . "/" . DB_NAME;
		$flick->enableCache($type = 'db', $fcon , $cache_expire = 100, $table = $table_prefix.'flickpress_cache');
		$check = $flick->photos_getRecent(NULL,NULL,1,1);
		if (isset($check['photos']['page'])) {
			if ($check['photos']['page'] == 1) {
				return TRUE;
			} else {
				return FALSE;
			}
		} else {
			return FALSE;
		}
	}
}

// Gets flickr user/group table as an associative array.
function flickpress_getlist() {
	global $wpdb;
	$fp_table_name = $wpdb->prefix . 'flickpress';
	if ($results = $wpdb->get_results("SELECT flickrid, flickrname FROM $fp_table_name ORDER BY flickrname",ARRAY_A)) {
		return $results;
	} else {
		return FALSE;
	}
}

function flickpress_is_user($flickrid) {
	global $wpdb;
	$flickpress_options = get_option('flickpress_options');
	$table_name = $wpdb->prefix . "flickpress";
	if ($wpdb->get_var("SELECT flickrid FROM $table_name WHERE binary flickrid = '" . $flickrid . "'")) {
		return TRUE;
	} else {
		return FALSE;
	}
}

// update the table
function flickpress_update($update_array) {
	global $wpdb;
	$flickpress_options = get_option('flickpress_options');
	$table_name = $wpdb->prefix . "flickpress";
	foreach ($update_array as $key=>$val) {
		$update_array[$key] = $wpdb->escape($val);
	}
	if ($wpdb->get_var("SELECT flickrid FROM $table_name WHERE binary flickrid = '" . $update_array['flickrid'] . "'")) {
		return $wpdb->query("UPDATE $table_name SET 
			flickrid = '" . $update_array['flickrid'] . "', 
			flickrname = '" . $update_array['flickrname'] . "'
			WHERE binary flickrid = '" . $update_array['flickrid'] . "'");
	} else {
		return $wpdb->query("INSERT INTO $table_name SET 
			flickrid = '" . $update_array['flickrid'] . "',
			flickrname = '" . $update_array['flickrname'] . "'");
	}
}

function flickpress_popup_header() {
	global $flickpress_options, $fp_parent_ID;
	if (empty($flickpress_options['insclass']))
		$flickpress_options['insclass'] = 'alignnone';
	if (empty($flickpress_options['captions']))
		$flickpress_options['captions'] = 'yes';
	if ($flickpress_options['thickbox'] == 'yes') {
		$imagelinkvar = 'var imglink = "<a href=\"" + unescape(tbimg) + "\" title=\"" + imgtitleenc + "\" class=\"thickbox\">";';
	} elseif ($flickpress_options['thickbox'] == 'custom') {
		if (!empty($flickpress_options['tbcode']))
			$tbcode = ' ' . trim($flickpress_options['tbcode']);
		$imagelinkvar = 'var imglink = "<a href=\"" + unescape(tbimg) + "\" title=\"" + imgtitleenc + "\"' . $tbcode . '>";';
	} else {
		$imagelinkvar = 'var imglink = "<a href=\"" + unescape(imgurl) + "\">";';
	}
	echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>' . __('flickpress: insert Flickr photos','flickpress') . '</title>
<link rel="stylesheet" href="' . get_bloginfo('wpurl') . '/wp-admin/css/global.css" type="text/css" />
<link rel="stylesheet" href="' . get_bloginfo('wpurl') . '/wp-admin/css/wp-admin.css" type="text/css" />
<link rel="stylesheet" href="' . get_bloginfo('wpurl') . '/wp-admin/css/media.css" type="text/css" />
<link rel="stylesheet" href="' . get_bloginfo('wpurl') . '/wp-admin/css/colors-fresh.css" type="text/css" />
<link rel="stylesheet" href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.css" type="text/css" />
<meta http-equiv="Content-Type" content="text/html; charset=' . get_option('blog_charset') . '" />
<script type="text/javascript" src="' . get_bloginfo('wpurl') . '/wp-includes/js/jquery/jquery.js"></script>
<script type="text/javascript">
//<![CDATA[
window.focus();
var winder = window.top;
function insertcode(imgsrc,docaption,dodesc,doexif,divwidth,imgwidth,imgheight) {
	var captype = "' . $flickpress_options['captype'] . '";
	' . $imagelinkvar . '
	if (docaption == "1") {
		if (captype == "default") {
			var linkcode = "<div class=\"wp-caption ' . $flickpress_options['insclass'] . '\" style=\"width: " + divwidth + "px\;\">\n\n" + imglink + "<img src=\"" + unescape(imgsrc) + "\" title=\"" + imgtitleenc + "\" alt=\"" + imgtitleenc + "\" width=\"" + imgwidth + "\" height=\"" + imgheight + "\" \></a>\n<p class=\"wp-caption-text\">" + unescape(imgcaption) + "</p>";
		} else {
			var linkcode = imglink + "<img src=\"" + unescape(imgsrc) + "\" title=\"" + imgtitleenc + "\" alt=\"" + imgtitleenc + "\" width=\"" + imgwidth + "\" height=\"" + imgheight + "\" \></a>\n<p class=\"wp-caption-text\">" + unescape(imgcaption) + "</p>";
		}
		if (dodesc == "1") {
			var linkcode = linkcode + "\n<p class=\"wp-caption flickr-desc\">" + unescape(imgdescription) + "</p>";
		}
		if (doexif == "1") {
			var linkcode = linkcode + "\n\n" + unescape(imgexif);
		}
		if (captype == "default") {
			var linkcode = linkcode + "\n\n</div>\n";
		} else {
			var linkcode = linkcode + "\n";
		}
	} else {
		var linkcode = imglink + "<img src=\"" + unescape(imgsrc) + "\" title=\"" + imgtitleenc + "\" alt=\"" + imgtitleenc + "\" width=\"" + imgwidth + "\" height=\"" + imgheight + "\" \></a>\n";
	}
   if ( typeof winder.tinyMCE !== "undefined" && ( winder.ed = winder.tinyMCE.activeEditor ) && !winder.ed.isHidden() ) {
      winder.ed.focus();
      if (winder.tinymce.isIE)
         winder.ed.selection.moveToBookmark(winder.tinymce.EditorManager.activeEditor.windowManager.bookmark);
      winder.ed.execCommand("mceInsertContent", false, linkcode);
   } else {
      winder.edInsertContent(winder.edCanvas, linkcode);
   }
   return;
}
jQuery(document).ready(function() {
	jQuery("span.fpinserted").hide();
	jQuery("a.fpinserting").click(function(){
		jQuery("span.fpinserted").show();
	});
   jQuery("div.fpshowhide").hide();
   jQuery("a.fptoggle").click(function(){
      jQuery("div.fpshowhide").toggle();
   });
});
//]]>
</script>
</head>
<body>
<div class="wrap">';
}

function flickpress_popup_main() {
	global $wpdb, $fp_parent_ID, $flickpress_options;
	if (isset($_GET['fpaction'])) {
		if ($_GET['fpaction'] == 'delcache') {
			$wpdb->query('TRUNCATE TABLE ' . $wpdb->prefix . 'flickpress_cache;');
			_e("<p class='fpupdated'>Cleared the cache.</p>\n","flickpress");
		}
	}
	$phpflickpress = new phpFlickpress($flickpress_options['apikey']);
	$fcon = "mysql://" . DB_USER . ":" . DB_PASSWORD . "@" . DB_HOST . "/" . DB_NAME;
	$phpflickpress->enableCache($type = 'db', $fcon , $cache_expire = 600, $table = $wpdb->prefix.'flickpress_cache');
	if (isset($_POST['fpuseradd'])) {
	if (!empty($_POST['email'])) {
		if (strpos($_POST['email'],'@') === FALSE) {
			$fpuseradd_info = $phpflickpress->people_findByUsername($_POST['email']);
		} else {
			$fpuseradd_info = $phpflickpress->people_findByEmail($_POST['email']);
		}
		if ($fpuseradd_info) {
			if (flickpress_is_user($fpuseradd_info['id'])) {
				printf(__("<p class='fpupdated'>%s has already been added.</p>\n","flickpress"),$fpuseradd_info['username']);
			} else {
				$fpupdate_array = array('flickrid'=>$fpuseradd_info['id'],'flickrname'=>$fpuseradd_info['username']);
				if (flickpress_update($fpupdate_array)) {
					printf(__("<p class='fpupdated'>Added %s, you may now browse their photos.</p>\n","flickpress"),$fpuseradd_info['username']);
				} else {
					printf(__("<p class='fperror'>Failed to add %s, possibly due to a database error.</p>\n","flickpress"),$fpuseradd_info['username']);
				}
			}
		} else {
					printf(__("<p class='fperror'>No Flickr user found linked to %s, check the email or username and try again.</p>\n","flickpress"),$_POST['email']);
		}
	}
	}
	echo '<h3>' . __('Insert photos from a Flickr account','flickpress') . '</h3>
';
	if ($flickpress_stored = flickpress_getlist()) {
		echo '<p>' . __('Click one of the usernames to browse their photos:','flickpress') . "</p>\n<table class='usertable'><tbody>\n";
		$fpcol = 1;
		foreach ((array)$flickpress_stored as $flickr_user) {
			if ($fpcol == 1) {
				echo '<tr>';
			}
			echo '<td><a class="usernames page-numbers" href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=options&amp;fptype=user&amp;fpid=' . $flickr_user['flickrid'] . '&amp;fpuname=' . urlencode($flickr_user['flickrname']) . '">' . $flickr_user['flickrname'] . "</a></td>";
			if ($fpcol == 3) {
				echo '</tr>';
				$fpcol = 1;
			} else {
				$fpcol++;
			}
		}
		if ($fpcol == 2) {
			echo "<td></td><td></td></tr>";
		} elseif ($fpcol == 3) {
			echo "<td></td></tr>";
		}
		echo "</tbody></table>\n";
	}
	$fplicenses = $phpflickpress->photos_licenses_getInfo();
	echo '<form name="flickpress_adduser" method="post" action="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php">
        <input type="hidden" name="fpuseradd" value="update" />
        <input type="hidden" name="fp_parent_ID" value="' . $fp_parent_ID . '" />
        ' . __('Enter a Flickr username or email to add:','flickpress') . '<br /><input type="text" name="email" value="" size="15" class="regular-text" /> <input type="submit" name="Submit" value="' . __('Look up Flickr user','flickpress') . '" class="button" /></form>
	<h3>' . __('Search for CC-licensed, government, and Flickr Commons photos','flickpress') . '</h3>
	<form name="flickpress_search" method="post" action="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php">
	<input type="hidden" name="fp_parent_ID" value="' . $fp_parent_ID . '" />
	<p><a class="fptoggle" style="cursor:pointer">' . __('Choose licenses &raquo;','flickpress') . '</a></p>
	<div class="fpshowhide">
	<p>' . __('Please be sure that your use is compatible with the photo license.','flickpress') . '</p>
	<ul>';
	foreach ($fplicenses as $fplicense) {
		if (!isset($flickpress_options['license'])) {
			$checked = 'checked="checked" ';
		} else {
			if (in_array($fplicense['id'],$flickpress_options['license'])) {
				$checked = 'checked="checked" ';
			} else {
				$checked = '';
			}
		}
		if ($fplicense['id'] !== '0') {
			echo '<li><label><input name="licensetype[]" value="' . $fplicense['id'] . '" type="checkbox" ' . $checked . '/> ' . $fplicense['name'] . ' <a href="' . $fplicense['url'] . '">' . __('(about)','flickpress') . "</a></label></li>\n";
		}
	}
	echo '</ul>
	</div>
	<p><input type="text" name="searchtext" value="" size="15" class="regular-text" /> <input type="submit" name="Submit" value="' . __('Find photos','flickpress') . '" class="button" /></p></form>
	<h3><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=interesting">' . __('Browse interesting photos','flickpress') . '</a></h3>
	<h3><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=delcache">' . __('Clear cache','flickpress') . '</a></h3>
	</div>
</body>
</html>';
	die();
}

function flickpress_popup_user_options() {
	global $flickpress_options, $fp_parent_ID;
	$userlink = '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=options&amp;fptype=user&amp;fpid=' . $_GET['fpid'] . '&amp;fpuname=' . urlencode($_GET['fpuname']) . '">' . $_GET['fpuname'] . '</a>';
	echo '<h3><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=users" class="current">' . __('Home','flickpress') . '</a> : ' . $userlink . '</h3>
	<p><strong>Browse:</strong></p>
	<ul>
	<li><strong><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=sets&amp;fptype=user&amp;fpid=' . $_GET['fpid'] . '&amp;fpuname=' . $_GET['fpuname'] . '">' . __('photosets','flickpress') . ' &raquo;</a></strong></li>
	<li><strong><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=tags&amp;fptype=user&amp;fpid=' . $_GET['fpid'] . '&amp;fpuname=' . $_GET['fpuname'] . '&amp;fpshowtags=popular">' . __('tags','flickpress') . ' &raquo;</a></strong></li>
	<li><strong><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=recent&amp;fptype=user&amp;fpid=' . $_GET['fpid'] . '&amp;fpuname=' . $_GET['fpuname'] . '">' . __('recent','flickpress') . ' &raquo;</a></strong></li>
	<li><strong><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=faves&amp;fptype=user&amp;fpid=' . $_GET['fpid'] . '&amp;fpuname=' . $_GET['fpuname'] . '">' . __('favorites','flickpress') . ' &raquo;</a></strong></li>
	</ul>
	<p><strong>Search:</strong></p>
	<form name="flickpress_search" method="post" action="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php">
	<input type="hidden" name="fp_parent_ID" value="' . $fp_parent_ID . '" />
	<input type="hidden" name="fpid" value="' . $_GET['fpid'] . '" />
	<input type="hidden" name="fpuname" value="' . $_GET['fpuname'] . '" />
   <input type="text" name="searchtext" value="" size="15" class="regular-text" /> <input type="submit" name="Submit" value="' . __('Find photos','flickpress') . '" class="button" /></form>
        </div>
</body>
</html>';
	die();
}

function flickpress_popup_recent() {
	global $flickpress_options, $fp_parent_ID, $flickpress_per_page, $table_prefix;
	$phpflickpress = new phpFlickpress($flickpress_options['apikey']);
	$fcon = "mysql://" . DB_USER . ":" . DB_PASSWORD . "@" . DB_HOST . "/" . DB_NAME;
	$phpflickpress->enableCache($type = 'db', $fcon , $cache_expire = 600, $table = $table_prefix.'flickpress_cache');
	$userlink = '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=options&amp;fptype=user&amp;fpid=' . $_GET['fpid'] . '&amp;fpuname=' . urlencode($_GET['fpuname']) . '">' . $_GET['fpuname'] . '</a>';
	if (isset($_GET['fp_page'])) {
		if ($_GET['fp_page'] > 1) {
			$page = $_GET['fp_page'];
		} else {
			$page = 1;
		}
	} else {
		$page = 1;
	}
	if ($user_info = $phpflickpress->people_getInfo($_GET['fpid'])) {
		$photos_url = $user_info['photosurl'];
	echo '<h3><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=users" class="current">' . __('Home','flickpress') . '</a> : ' . $userlink . ' : ' . '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=recent&amp;fptype=user&amp;fpid=' . $_GET['fpid'] . '&amp;fpuname=' . $_GET['fpuname'] . '">' . __('recent','flickpress') . "</a></h3>\n";
		echo "\n<p>\n";
		if ($photos = $phpflickpress->people_getPublicPhotos($_GET['fpid'],NULL,NULL,$flickpress_per_page,$page)) {
			$num_photos = $photos['photos']['total'];
			if ($num_photos > 0) {
				$pages = ceil($num_photos/$flickpress_per_page);
				foreach ((array)$photos['photos']['photo'] as $photo) {
					$photourl = $phpflickpress->buildPhotoURL($photo, 'Square');
					$imgtitle = htmlentities($photo['title']);
					$imgcode = flickpress_make_thumb_sq($imgtitle, $photourl);
					echo '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=showphoto&amp;fptype=user&amp;fpid=' . $_GET['fpid'] . '&amp;fpuname=' . $_GET['fpuname'] . '&amp;photoid=' . $photo['id'] . '&amp;returnto=recent&amp;fp_page=' . $page . '">' . $imgcode . '</a> ';
					unset($photourl,$imgcode,$flickrcode);
				}
				if ($pages > 1) {
					$linkbase = get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=recent&amp;fptype=user&amp;fpid=' . $_GET['fpid'] . '&amp;fpuname=' . $_GET['fpuname'] . '&amp;fp_page=';
					$page_nav_html = "<div class='tablenav-pages prevnext'>" . paginate_links( array('base' => $linkbase.'%_%','format' => '%#%','prev_text' => __('&laquo; prev'),'next_text' => __('next &raquo;'),'total' => $pages,'current' => $page)) . "</div>\n";
					echo $page_nav_html;
				}
				echo "</p>\n";
			} else {
				echo '<p>' . __('This user has no public photos.','flickpress') . "</p>\n";
			}
		} else {
			echo '<p>' . __('An error occurred.','flickpress') . "<br />\n<pre>\n";
			print_r($photos);
			echo "\n</pre>\n</p>";
		}
	} else {
			echo '<p>' . __('An error occurred.','flickpress') . "<br />\n<pre>\n";
			print_r($photos);
			echo "\n</pre>\n</p>\n";
	}
	echo '</div>
</body>
</html>';
	die();
}

function flickpress_make_thumb_sq($title, $url) {
	$thumb = '<img alt="' . $title . '" title="' . $title . '" src="' . $url . '" width="75" height="75" />';
	return $thumb;
}

function flickpress_popup_interesting() {
	global $flickpress_options, $fp_parent_ID, $flickpress_per_page, $table_prefix;
	$phpflickpress = new phpFlickpress($flickpress_options['apikey']);
	$fcon = "mysql://" . DB_USER . ":" . DB_PASSWORD . "@" . DB_HOST . "/" . DB_NAME;
	$phpflickpress->enableCache($type = 'db', $fcon , $cache_expire = 600, $table = $table_prefix.'flickpress_cache');
	if (isset($_GET['fp_page'])) {
		if ($_GET['fp_page'] > 1) {
			$page = $_GET['fp_page'];
		} else {
			$page = 1;
		}
	} else {
		$page = 1;
	}
	if ($photos = $phpflickpress->interestingness_getList(NULL,NULL,NULL,$flickpress_per_page,$page)) {
		echo '<h3><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=users" class="current">' . __('Home','flickpress') . '</a> : '  . '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=interesting&amp;fp_page=' . $page . '">' . __('Interesting','flickpress') . "</a></h3>\n";
		echo "\n<p>\n";
		$num_photos = $photos['photos']['total'];
		$pages = ceil($num_photos/$flickpress_per_page);
		foreach ((array)$photos['photos']['photo'] as $photo) {
			$photourl = $phpflickpress->buildPhotoURL($photo, 'Square');
			$imgtitle = htmlentities($photo['title']);
			$imgcode = flickpress_make_thumb_sq($imgtitle, $photourl);
			echo '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=showphoto&amp;photoid=' . $photo['id'] . '&amp;returnto=interesting&amp;fp_page=' . $page . '">' . $imgcode . '</a> ';
			unset($photourl,$imgcode,$flickrcode);
		}
		if ($pages > 1) {
			$linkbase = get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=interesting&amp;fp_page=';
			$page_nav_html = "<div class='tablenav-pages prevnext'>" . paginate_links( array('base' => $linkbase.'%_%','format' => '%#%','prev_text' => __('&laquo; prev'),'next_text' => __('next &raquo;'),'total' => $pages,'current' => $page)) . "</div>\n";
			echo $page_nav_html;
		}
		echo "</p>\n";
	} else {
		echo '<p>' . __('Failed to get data from Flickr. Possible error message:','flickpress') . "<br />\n<pre>\n";
		print_r($photos);
		echo "\n</pre>\n</p>";
	}
	echo '</div>
</body>
</html>';
	die();
}

function flickpress_popup_favorites() {
	global $flickpress_options, $fp_parent_ID, $flickpress_per_page, $table_prefix;
	$phpflickpress = new phpFlickpress($flickpress_options['apikey']);
	$fcon = "mysql://" . DB_USER . ":" . DB_PASSWORD . "@" . DB_HOST . "/" . DB_NAME;
	$phpflickpress->enableCache($type = 'db', $fcon , $cache_expire = 600, $table = $table_prefix.'flickpress_cache');
	$userlink = '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=options&amp;fptype=user&amp;fpid=' . $_GET['fpid'] . '&amp;fpuname=' . urlencode($_GET['fpuname']) . '">' . $_GET['fpuname'] . '</a>';
	$page = 1; // probably not necessary
	if (isset($_GET['fp_page'])) {
		if ($_GET['fp_page'] > 1) {
			$page = $_GET['fp_page'];
		} else {
			$page = 1;
		}
	} else {
		$page = 1;
	}
	$user_info = $phpflickpress->people_getInfo($_GET['fpid']);
	$photos_url = $user_info['photosurl'];
	echo '<h3><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=users">' . __('Home','flickpress') . '</a> : ' . $userlink . ' : ' . '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=faves&amp;fptype=user&amp;fpid=' . $_GET['fpid'] . '&amp;fpuname=' . $_GET['fpuname'] . '">' . __('favorites','flickpress') . "</a></h3>\n";
	echo "<p>\n";
	if ($photos = $phpflickpress->favorites_getPublicList($_GET['fpid'],NULL,NULL,NULL,NULL,$flickpress_per_page,$page)) {
		$num_photos = $photos['photos']['total'];
		$pages = ceil($num_photos/$flickpress_per_page);
		if ($num_photos > 0) {
			foreach ((array)$photos['photos']['photo'] as $photo) {
				$photourl = $phpflickpress->buildPhotoURL($photo, 'Square');
				$imgtitle = htmlentities($photo['title']);
				$imgcode = flickpress_make_thumb_sq($imgtitle, $photourl);
				echo '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=showphoto&amp;fptype=user&amp;fpid=' . $_GET['fpid'] . '&amp;fpuname=' . $_GET['fpuname'] . '&amp;photoid=' . $photo['id'] . '&amp;returnto=faves&amp;fp_page=' . $page . '">' . $imgcode . '</a> ';
				unset($photourl,$imgcode,$flickrcode);
			}
			if ($pages > 1) {
				$linkbase = get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=faves&amp;fptype=user&amp;fpid=' . $_GET['fpid'] . '&amp;fpuname=' . $_GET['fpuname'] . '&amp;fp_page=';
				$page_nav_html = "<div class='tablenav-pages prevnext'>" . paginate_links( array('base' => $linkbase.'%_%','format' => '%#%','prev_text' => __('&laquo; prev'),'next_text' => __('next &raquo;'),'total' => $pages,'current' => $page)) . "</div>\n";
				echo $page_nav_html;
			}
			echo "</p>\n";
		} else {
			echo '<p>' . __('This user has no favorites.','flickpress') . "</p>\n";
		}
	} else {
		echo '<p>' . __('Failed to get data from Flickr. Possible error message:','flickpress') . "<br />\n<pre>\n";
		print_r($photos);
		echo "\n</pre>\n</p>";
	}
	echo '</div>
</body>
</html>';
	die();
}

function flickpress_popup_search() {
	global $flickpress_options, $fp_parent_ID, $flickpress_per_page, $table_prefix;
	$phpflickpress = new phpFlickpress($flickpress_options['apikey']);
	$fcon = "mysql://" . DB_USER . ":" . DB_PASSWORD . "@" . DB_HOST . "/" . DB_NAME;
	$phpflickpress->enableCache($type = 'db', $fcon , $cache_expire = 600, $table = $table_prefix.'flickpress_cache');
	if (isset($_POST['searchtext'])) {
		$searchtext = $_POST['searchtext'];
		if (isset($_POST['licensetype'])) {
			$s_licenses = $_POST['licensetype'];
		} elseif (isset($_POST['fpid'])) {
			$fpid = $_POST['fpid'];
			$fpuname = $_POST['fpuname'];
		} else {
			echo '<h3><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=users">' . __('Home','flickpress') . '</a> : ' . __('Commons search','flickpress') . ' : &laquo;' . $searchtext . '&raquo;</h3>';
			die(__('Search failed: missing user id or license type.'));
		}
	} else {
		$searchtext = $_GET['searchtext'];
		if (isset($_GET['licenses'])) {
			$s_licenses = explode(' ',$_GET['licenses']);
		} elseif (isset($_GET['fpid'])) {
			$fpid = $_GET['fpid'];
			$fpuname = $_GET['fpuname'];
		} else {
			echo '<h3><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=users">' . __('Home','flickpress') . '</a> : ' . __('Commons search','flickpress') . ' : &laquo;' . $searchtext . '&raquo;</h3>';
			die(__('Search failed: missing user id or license type.'));
		}
	}
	if (isset($_GET['fp_page'])) {
		if ($_GET['fp_page'] > 1) {
			$page = $_GET['fp_page'];
		} else {
			$page = 1;
		}
   } else {
		$page = 1;
	}
	if (isset($s_licenses)) {
		$fplicenses = $phpflickpress->photos_licenses_getInfo();
		$comma_licenses = implode(',',$s_licenses);
		$plus_licenses = implode('+',$s_licenses);
		$searcharray = array('text' => $searchtext,'license' => $comma_licenses,'page' => $page,'per_page' => $flickpress_per_page);
	} elseif (isset($fpid)) {
		$searcharray = array('text' => $searchtext,'user_id' => $fpid,'page' => $page,'per_page' => $flickpress_per_page);
	}
	$photos = $phpflickpress->photos_search($searcharray);
	if (isset($s_licenses)) {
		$addthis = 'licenses=' . $plus_licenses;
	} else {
		$addthis = 'fpid=' . $fpid . '&amp;fpuname=' . urlencode($fpuname);
	}
	if (isset($s_licenses)) {
		echo '<h3><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=users">' . __('Home','flickpress') . '</a> : ' . __('Commons search','flickpress') . ' : ' . $searchtext . '</h3>';
	} else {
		echo '<h3><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=users">' . __('Home','flickpress') . '</a> : ' . '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=options&amp;fptype=user&amp;fpid=' . $fpid . '&amp;fpuname=' . urlencode($fpuname) . '">' . $fpuname . '</a> : ' . __('search','flickpress') . ' : ' . $searchtext . "</h3>\n";
	}
	echo "\n<p>";
	if (isset($s_licenses)) {
		$addthis = 'licenses=' . $plus_licenses; 
	} else { 
		$addthis = 'fpid=' . $fpid . '&amp;fpuname=' . urlencode($fpuname); 
	}
	if ($photos['total'] > 0) {
		foreach ((array)$photos['photo'] as $photo) {
			$photourl = $phpflickpress->buildPhotoURL($photo, 'Square');
			$imgtitle = htmlentities($photo['title']);
			$imgcode = flickpress_make_thumb_sq($imgtitle, $photourl);
			echo '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=showphoto&amp;photoid=' . $photo['id'] . '&amp;fp_page=' . $page . '&amp;insearch=' . urlencode($searchtext) . '&amp;' .$addthis . '">' . $imgcode . '</a> ';
			unset($photourl,$imgcode);
		}
		if ($photos['pages'] > 1) {
			$linkbase = get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;searchtext=' . urlencode($searchtext) . '&amp;' . $addthis . '&amp;fp_page=';
			$page_nav_html = "<div class='tablenav-pages prevnext'>" . paginate_links( array('base' => $linkbase.'%_%','format' => '%#%','prev_text' => __('&laquo; prev'),'next_text' => __('next &raquo;'),'total' => $photos['pages'],'current' => $page)) . "</div>\n";
			echo $page_nav_html;
		}
	} else {
		echo '<strong>' . __('None found!','flickpress') . '</strong>';
	}
   echo "</p>\n";
	echo '<form name="flickpress_search" method="post" action="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php">
   <input type="hidden" name="fp_parent_ID" value="' . $fp_parent_ID . '" />';
	if (isset($s_licenses)) {
		echo '<p><a class="fptoggle" style="cursor:pointer">' . __('Choose licenses &raquo;','flickpress') . '</a></p>
		<div class="fpshowhide">
		<ul>
		';
		foreach ($fplicenses as $fplicense) {
			if ($fplicense['id'] !== '0' && in_array($fplicense['id'],$s_licenses) ) {
				echo '<li><label><input name="licensetype[]" value="' . $fplicense['id'] . '" type="checkbox" checked="checked" /> ' . $fplicense['name'] . ' <a href="' . $fplicense['url'] . '">' . __('(about)','flickpress') . "</a></label></li>\n";
			} elseif ($fplicense['id'] !== '0') {
				echo '<li><label><input name="licensetype[]" value="' . $fplicense['id'] . '" type="checkbox" /> ' . $fplicense['name'] . ' <a href="' . $fplicense['url'] . '">' . __('(about)','flickpress') . "</a></label></li>\n";
			}
		}
		echo '</ul>
		</div>
		';
	} else {
		echo '<input type="hidden" name="fpid" value="' . $fpid . '" />
   <input type="hidden" name="fpuname" value="' . $fpuname . '" />
';
	}
	echo '<input type="text" name="searchtext" value="" size="15" class="regular-text" /> <input type="submit" name="Submit" value="' . __('New search &raquo;','flickpress') . '" class="button" /></form>
	</div>
</body>
</html>';
	die();
}

function flickpress_popup_sets() {
	global $flickpress_options, $fp_parent_ID, $flickpress_per_page, $table_prefix;
	$phpflickpress = new phpFlickpress($flickpress_options['apikey']);
	$fcon = "mysql://" . DB_USER . ":" . DB_PASSWORD . "@" . DB_HOST . "/" . DB_NAME;
	$phpflickpress->enableCache($type = 'db', $fcon , $cache_expire = 600, $table = $table_prefix.'flickpress_cache');
	$userlink = '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=options&amp;fptype=user&amp;fpid=' . $_GET['fpid'] . '&amp;fpuname=' . urlencode($_GET['fpuname']) . '">' . $_GET['fpuname'] . '</a>';
	$photos_url = $phpflickpress->urls_getUserPhotos($_GET['fpid']);
	$setslink = '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=sets&amp;fptype=user&amp;fpid=' . $_GET['fpid'] . '&amp;fpuname=' . $_GET['fpuname'] . '">' . __('photosets','flickpress') . '</a>';
	if (isset($_GET['showset'])) {
		if (isset($_GET['fp_page'])) {
			if ($_GET['fp_page'] > 1) {
				$page = $_GET['fp_page'];
			} else {
				$page = 1;
			}
		} else {
			$page = 1;
		}
		if (($setphotos = $phpflickpress->photosets_getPhotos($photoset_id = $_GET['showset'], $extras = NULL, $privacy_filter = NULL, $per_page = $flickpress_per_page, $page = $page)) && ($setinfo = $phpflickpress->photosets_getInfo($photoset_id = $_GET['showset']))) {
			echo '<h3><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=users">' . __('Home','flickpress') . '</a> : ' . $userlink . ' : ' . $setslink . ' : ' . htmlentities($setinfo['title']) . "</h3>\n";
			echo '<p>';
			foreach ((array)$setphotos['photoset']['photo'] as $photo) {
				$photourl = $phpflickpress->buildPhotoURL($photo, "Square");
				$imgtitle = htmlentities($photo['title']);
				$imgcode = flickpress_make_thumb_sq($imgtitle, $photourl);
				echo '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=showphoto&amp;fptype=user&amp;fpid=' . $_GET['fpid'] . '&amp;fpuname=' . $_GET['fpuname'] . '&amp;photoid=' . $photo['id'] . '&amp;returnto=sets&amp;showset=' . $_GET['showset'] . '&amp;fp_page=' . $page . '">' . $imgcode . '</a> ';
				unset($photourl,$imgcode,$flickrcode);
			}
			echo "</p>\n";
			if ($setphotos['photoset']['pages'] > 1) {
				$linkbase = get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=sets&amp;showset=' . $_GET['showset'] . '&amp;fptype=user&amp;fpid=' . $_GET['fpid'] . '&amp;fpuname=' . $_GET['fpuname'] . '&amp;fp_page=';
				$page_nav_html = "<div class='tablenav-pages prevnext'>" . paginate_links( array('base' => $linkbase.'%_%','format' => '%#%','prev_text' => __('&laquo; prev'),'next_text' => __('next &raquo;'),'total' => $setphotos['photoset']['pages'],'current' => $page)) . "</div>\n";
				echo $page_nav_html;
			}
		} else {
			echo '<p>' . __('Failed to get data from Flickr. Possible error message:','flickpress') . "<br />\n<pre>\n";
			print_r($setphotos);
			echo "\n</pre>\n</p>";
		}
	} else {
		echo '<h3><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=users">' . __('Home','flickpress') . '</a> : ' . $userlink . __(' : photosets','flickpress'). "</h3>\n";
		if ($photosets = $phpflickpress->photosets_getList($_GET['fpid'])) {
			echo "\n<ul>\n";
			foreach ((array)$photosets['photoset'] as $photoset) {
				echo '<li><strong><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=sets&amp;showset=' . $photoset['id'] . '&amp;fptype=user&amp;fpid=' . $_GET['fpid'] . '&amp;fpuname=' . $_GET['fpuname'] . '">' . htmlentities($photoset['title']) . ' (' . $photoset['photos'] . ')</a></strong></li>';
			}
			echo "</ul>\n";
		} else {
			echo '<p>' . __('No photosets found or failed to get data from Flickr. Possible error message:','flickpress') . "<br />\n<pre>\n";
			print_r($photosets);
			echo "\n</pre>\n</p>";
		}
	}
	echo '</div>
</body>
</html>';
	die();
}

function flickpress_popup_tags() {
	global $flickpress_options, $fp_parent_ID, $flickpress_per_page, $table_prefix;
	$phpflickpress = new phpFlickpress($flickpress_options['apikey']);
	$fcon = "mysql://" . DB_USER . ":" . DB_PASSWORD . "@" . DB_HOST . "/" . DB_NAME;
	$phpflickpress->enableCache($type = 'db', $fcon , $cache_expire = 600, $table = $table_prefix.'flickpress_cache');
	$userlink = '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=options&amp;fptype=user&amp;fpid=' . $_GET['fpid'] . '&amp;fpuname=' . urlencode($_GET['fpuname']) . '">' . $_GET['fpuname'] . '</a>';
	$photos_url = $phpflickpress->urls_getUserPhotos($_GET['fpid']);
	$tagslink = '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=tags&amp;fptype=user&amp;fpid=' . $_GET['fpid'] . '&amp;fpuname=' . $_GET['fpuname'] . '&amp;fpshowtags=popular">' . __('tags','flickpress') . '</a>';
	// show the photos for a tag
	if (isset($_GET['showtag'])) {
	echo '<h3><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=users">' . __('Home','flickpress') . '</a> : ' . $userlink . ' : ' . $tagslink . ' : ' . $_GET['showtag'] . "</h3>\n";
		if (isset($_GET['fp_page'])) {
			if ($_GET['fp_page'] > 1) {
				$page = $_GET['fp_page'];
			} else {
				$page = 1;
			}
		} else {
			$page = 1;
		}
		if ($tagphotos = $phpflickpress->photos_search(array("user_id" => $_GET['fpid'], "tags" => $_GET['showtag'], "per_page" => $flickpress_per_page, "page" => $page))) {
			$num_photos = $tagphotos['total'];
			$pages = ceil($num_photos/$flickpress_per_page);
			echo "<p>";
			foreach ((array)$tagphotos['photo'] as $tagphoto) {
				$photourl = $phpflickpress->buildPhotoURL($tagphoto, "Square");
				$imgtitle = htmlentities($tagphoto['title']);
				$imgcode = flickpress_make_thumb_sq($imgtitle, $photourl);
				echo '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=showphoto&amp;fptype=user&amp;fpid=' . $_GET['fpid'] . '&amp;fpuname=' . $_GET['fpuname'] . '&amp;photoid=' . $tagphoto['id'] . '&amp;returnto=tags&amp;showtag=' . $_GET['showtag'] . '&amp;fp_page=' . $page . '">' . $imgcode . '</a> ';
				unset($photourl,$imgcode,$flickrcode);
			}
			if ($pages > 1) {
				$linkbase = get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=tags&amp;showtag=' . $_GET['showtag'] . '&amp;fptype=user&amp;fpid=' . $_GET['fpid'] . '&amp;fpuname=' . $_GET['fpuname'] . '&amp;fp_page=';
				$page_nav_html = "<div class='tablenav-pages prevnext'>" . paginate_links( array('base' => $linkbase.'%_%','format' => '%#%','prev_text' => __('&laquo; prev'),'next_text' => __('next &raquo;'),'total' => $pages,'current' => $page)) . "</div>\n";
				echo $page_nav_html;
			}
			echo "</p>\n";
		} else {
			echo '<p>' . __('Failed to get data from Flickr. Possible error message:','flickpress') . "<br />\n<pre>\n";
			print_r($tagphotos);
			echo "\n</pre>\n</p>";
		}
	// list the user's tags
	} else {
		echo '<h3><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=users">' . __('Home','flickpress') . '</a> : ' . $userlink . ' : ' . __('tags','flickpress') . "</h3>\n";
		if (isset($_GET['fpshowtags'])) {
		if ($_GET['fpshowtags'] == 'all') {
			if ($tags = $phpflickpress->tags_getListUser($_GET['fpid'])) {
				echo "<p>" . __('Displaying all tags.','flickpress') . ' <a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=tags&amp;fptype=user&amp;fpid=' . $_GET['fpid'] . '&amp;fpuname=' . $_GET['fpuname'] . '&amp;fpshowtags=popular">' . __('Show popular tags.','flickpress') . "</a></p>\n<ul>\n";
				foreach ((array)$tags as $tag) {
					echo '<li><strong><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=tags&amp;showtag=' . $tag . '&amp;fptype=user&amp;fpid=' . $_GET['fpid'] . '&amp;fpuname=' . $_GET['fpuname'] . '">' . htmlentities($tag) . '</a></strong></li>';
				}
				echo "\n</ul>\n";
			} else {
				echo '<p>' . __('No tags found or failed to get data from Flickr. Possible error message:','flickpress') . "<br />\n<pre>\n";
				print_r($tags);
				echo "\n</pre>\n</p>";
			}
		} else {
			if ($tags = $phpflickpress->tags_getListUserPopular($_GET['fpid'],20)) {
				echo "<p>" . __('Displaying popular tags.','flickpress') . ' <a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=tags&amp;fptype=user&amp;fpid=' . $_GET['fpid'] . '&amp;fpuname=' . $_GET['fpuname'] . '&amp;fpshowtags=all">' . __('Show all tags.','flickpress') . "</a></p>\n<ul>\n";
				foreach ((array)$tags as $tag) {
					echo '<li><strong><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=tags&amp;showtag=' . $tag['_content'] . '&amp;fptype=user&amp;fpid=' . $_GET['fpid'] . '&amp;fpuname=' . $_GET['fpuname'] . '">' . htmlentities($tag['_content']) . '</a></strong> (' . $tag['count'] . ')</li>';
				}
				echo "\n</ul>\n";
			} else {
				echo '<p>' . __('No tags found or failed to get data from Flickr. Possible error message:','flickpress') . "<br />\n<pre>\n";
				print_r($tags);
				echo "\n</pre>\n</p>";
			}
		}
		}
	}
	echo "</div>\n</body>\n</html>";
	die();
}

function flickpress_popup_showphoto() {
	global $flickpress_options, $fp_parent_ID, $table_prefix;
	$phpflickpress = new phpFlickpress($flickpress_options['apikey']);
	$fcon = "mysql://" . DB_USER . ":" . DB_PASSWORD . "@" . DB_HOST . "/" . DB_NAME;
	$phpflickpress->enableCache($type = 'db', $fcon , $cache_expire = 600, $table = $table_prefix.'flickpress_cache');
	if (isset($_GET['fp_page'])) {
		if ($_GET['fp_page'] > 1) {
			$page = $_GET['fp_page'];
		} else {
			$page = 1;
		}
	} else {
		$page = 1;
	}
	if (isset($_GET['licenses'])) {
		$plus_licenses = str_replace(' ','+',$_GET['licenses']);
	}
	$photoinfo = $phpflickpress->photos_getInfo($_GET['photoid'],NULL);
	if ($photoexif = $phpflickpress->photos_getExif($_GET['photoid'],NULL)) {
		foreach ((array)$photoexif['exif'] as $e) {
			if (empty($fpexif[$e['label']])) {
				$fpexif[$e['label']] = (empty($e['clean']) ? $e['raw'] : $e['clean']);
			}
		}
		if (!empty($fpexif['Model']) | !empty($fpexif['Shutterspeed']) | !empty($fpexif['Aperture']) | !empty($fpexif['Focal Length']) | !empty($fpexif['Exposure Bias']) | !empty($fpexif['ISO Speed']) | !empty($fpexif['Flash'])) {
			$exiftable = '<table class="flickr-exif"><tbody>';
			if (!empty($fpexif['Model']))
				$exiftable .= "\n<tr><td>" . __('Camera:','flickpress') . '</td><td>' . $fpexif['Model'] . '</td></tr>';
			if (!empty($fpexif['Exposure']))
				$exiftable .= "\n<tr><td>" . __('Exposure:','flickpress') . '</td><td>' . $fpexif['Exposure'] . '</td></tr>';
			if (!empty($fpexif['Aperture']))
				$exiftable .= "\n<tr><td>" . __('Aperture:','flickpress') . '</td><td>' . $fpexif['Aperture'] . '</td></tr>';
			if (!empty($fpexif['Focal Length']))
				$exiftable .= "\n<tr><td>" . __('Focal Length:','flickpress') . '</td><td>' . $fpexif['Focal Length'] . '</td></tr>';
			if (!empty($fpexif['Exposure Bias']))
				$exiftable .= "\n<tr><td>" . __('Exposure Bias:','flickpress') . '</td><td>' . $fpexif['Exposure Bias'] . '</td></tr>';
			if (!empty($fpexif['ISO Speed']))
				$exiftable .= "\n<tr><td>" . __('ISO Speed:','flickpress') . '</td><td>' . $fpexif['ISO Speed'] . '</td></tr>';
			if (!empty($fpexif['Flash']))
				$exiftable .= "\n<tr><td>" . __('Flash:','flickpress') . '</td><td>' . $fpexif['Flash'] . '</td></tr>';
			if (!empty($fpexif['Lens Type']))
				$exiftable .= "\n<tr><td>" . __('Lens Type:','flickpress') . '</td><td>' . $fpexif['Lens Type'] . '</td></tr>';
			$exiftable .= "\n</tbody></table>";
			$hasexif = TRUE;
		} else {
			$hasexif = FALSE;
			$exiftable = '<p>' . __('No EXIF information available.','flickpress') . "</p>\n";
		}
	} else {
		$hasexif = FALSE;
		$exiftable = '<p>' . __('No EXIF information available.','flickpress') . "</p>\n";
	}
	if (!empty($photoinfo['photo']['description'])) {
		$hasdesc = TRUE;
		$description = $photoinfo['photo']['description'];
	} else {
		$hasdesc = FALSE;
		$description = __('No photo description available.','flickpress');
	}
	if (!empty($photoinfo['photo']['title'])) {
		$title = $photoinfo['photo']['title'];
	} elseif (!empty($flickpress_options['untitled'])) {
		$title = $flickpress_options['untitled'];
	} else {
		$title = '(untitled)';
	}
	$tags = array();
	if (is_array($photoinfo['photo']['tags'])) {
		foreach ($photoinfo['photo']['tags']['tag'] as $phototag) {
			$tags[] = $phototag['_content'];
		}
		$displaytags = implode(', ', $tags);
		$hastags = TRUE;
	} else {
		$hastags = FALSE;
	}
	$sizes = $phpflickpress->photos_getSizes($_GET['photoid']);
	if ($flickpress_options['caporder'] == 'authortitle') {
		$caption = wp_kses_stripslashes($flickpress_options['before']) . '<a href="' . $phpflickpress->urls_GetUserPhotos($photoinfo['photo']['owner']['nsid']) . '">' . $photoinfo['photo']['owner']['username'] . '</a>' . wp_kses_stripslashes($flickpress_options['between']) . '<a href="' . $photoinfo['photo']['urls']['url']['0']['_content'] . '">' . $title . '</a>' . wp_kses_stripslashes($flickpress_options['after']);
	} elseif ($flickpress_options['caporder'] == 'titleonly') {
		$caption = wp_kses_stripslashes($flickpress_options['before']) . '<a href="' . $photoinfo['photo']['urls']['url']['0']['_content'] . '">' . $title . '</a>' . wp_kses_stripslashes($flickpress_options['after']);
	} else { // default to title-then-author
		$caption = wp_kses_stripslashes($flickpress_options['before']) . '<a href="' . $photoinfo['photo']['urls']['url']['0']['_content'] . '">' . $title . '</a>' . wp_kses_stripslashes($flickpress_options['between']) . '<a href="' . $phpflickpress->urls_GetUserPhotos($photoinfo['photo']['owner']['nsid']) . '">' . $photoinfo['photo']['owner']['username'] . '</a>' . wp_kses_stripslashes($flickpress_options['after']);
	}
	foreach ($sizes as $size) {
		if ($size['label'] == 'Original')
			break;
		$tbimg = $size['source'];
	}
	if (isset($_GET['fpimport']) && current_user_can('upload_files') && function_exists('media_sideload_image')) {
		$sideload = media_sideload_image($tbimg, $fp_parent_ID, $title);
		if (!is_wp_error($sideload)) {
			_e("<p class='fpupdated'>Added image to Media Library.</p>\n","flickpress");
		} else {
			_e("<p class='fpupdated'>Failed to add image to Media Library.</p>\n","flickpress");
		}
	}
	echo '<script type="text/javascript">' . "
	//<![CDATA[
	var imgurl = '" . rawurlencode($photoinfo['photo']['urls']['url']['0']['_content']) . "';
	var tbimg = '" . rawurlencode($tbimg) . "';
	var imgtitle = '" . rawurlencode($title) . "';
	var imgtitleenc = '" . htmlentities($title, ENT_QUOTES) . "';
	var imgdescription = '" . rawurlencode($photoinfo['photo']['description']) . "';
	var imgcaption = '" . rawurlencode($caption) . "';
	var imgexif = '" . rawurlencode($exiftable) . "';
	//]]>
	</script>";
	if (isset($_GET['insearch'])) {
		if (isset($plus_licenses)) {
			$importlink = '<p><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=showphoto&amp;photoid=' . $_GET['photoid'] . '&amp;fp_page=' . $page . '&amp;insearch=' . urlencode($_GET['insearch']) . '&amp;licenses=' . $plus_licenses . '&amp;fpimport=yes"">' . __('Import into Media Library','flickpress') . '</a></p>';
			$linkback = '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;searchtext=' . urlencode($_GET['insearch']) . '&amp;fp_page=' . $page . '&amp;licenses=' . $plus_licenses . '">';
			$bottomlink = '<p>' . $linkback . sprintf(__('Return to page %s of photos in your search for &laquo;%s&raquo;','flickpress'), $page, $_GET['insearch']) . "</a></p>\n";
			$toplink = '<h3><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=users">' . __('Home','flickpress') . '</a> : ' . __('Commons search','flickpress') . ' : ' . $linkback . $_GET['insearch'] . '</a> : ' . $caption . "</h3>\n";
		} else {
			$importlink = '<p><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=showphoto&amp;photoid=' . $_GET['photoid'] . '&amp;fp_page=' . $page . '&amp;insearch=' . urlencode($_GET['insearch']) . '&amp;fpid=' . $_GET['fpid'] . '&amp;fpuname=' . $_GET['fpuname'] . '&amp;fpimport=yes"">' . __('Import into Media Library','flickpress') . '</a></p>';
			$linkback = '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;searchtext=' . urlencode($_GET['insearch']) . '&amp;fp_page=' . $page . '&amp;fpid=' . $_GET['fpid'] . '&amp;fpuname=' . $_GET['fpuname'] . '">';
			$bottomlink = '<p>' . $linkback . sprintf(__('Return to page %s of photos in your search for &laquo;%s&raquo;','flickpress'), $page, $_GET['insearch']) . "</a></p>\n";
			$toplink = '<h3><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fpaction=users">' . __('Home','flickpress') . '</a> : ' . '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=options&amp;fptype=user&amp;fpid=' . $_GET['fpid'] . '&amp;fpuname=' . urlencode($_GET['fpuname']) . '">' . $_GET['fpuname'] . '</a> : ' . __('search','flickpress') . ' : ' . $linkback . $_GET['insearch'] . '</a> : ' . $title . "</h3>\n";
		}
	} else {
		if (isset($_GET['fpid']))
			$idstring = '&amp;fpid=' . $_GET['fpid'];
		else
			$idstring = '';
		if (isset($_GET['fpuname'])) {
			$unamestring = '&amp;fpuname=' . $_GET['fpuname'];
			$unametxt = $_GET['fpuname'];
		} else {
			$unamestring = '';
			$unametxt = '';
		}
		$beglinkback = '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=' . $_GET['returnto'] . '&amp;fptype=user' . $idstring . $unamestring . '&amp;fp_page=' . $page;
		$begtoplink = '<h3><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=users">' . __('Home','flickpress') . '</a> : ' . '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=options&amp;fptype=user' . $idstring . $unamestring . '">' . $unametxt . '</a> : ' . '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fptype=user' . $idstring . $unamestring . '&amp;fp_page=' . $page;
		$endtoplink = '</a> : ' . $title . "</h3>\n";
		if (isset($_GET['returnto'])) {
		if ($_GET['returnto'] == 'sets') {
			$setinfo = $phpflickpress->photosets_getInfo($_GET['showset']);
			$linkback = $beglinkback . '&amp;showset=' . $_GET['showset'] . '">';
			$importlink = '<p><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=showphoto&amp;fptype=user&amp;fpid=' . $_GET['fpid'] . '&amp;fpuname=' . $_GET['fpuname'] . '&amp;photoid=' . $_GET['photoid'] . '&amp;returnto=sets&amp;showset=' . $_GET['showset'] . '&amp;fp_page=' . $page . '&amp;fpimport=yes">' . __('Import into Media Library','flickpress') . '</a></p>';
			$bottomlink = '<p>' . $linkback . sprintf(__('Return to page %s of photos from the &laquo;%s&raquo; photoset','flickpress'), $page, $setinfo['title']) . "</a></p>\n";
			$toplink = $begtoplink . '&amp;fpaction=sets">' . __('photosets','flickpress') . '</a> : ' . $linkback . $setinfo['title'] . $endtoplink;
		} elseif ($_GET['returnto'] == 'tags') {
			$importlink = '<p><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=showphoto&amp;fptype=user&amp;fpid=' . $_GET['fpid'] . '&amp;fpuname=' . $_GET['fpuname'] . '&amp;photoid=' . $_GET['photoid'] . '&amp;returnto=tags&amp;showtag=' . $_GET['showtag'] . '&amp;fp_page=' . $page . '&amp;fpimport=yes">' . __('Import into Media Library','flickpress') . '</a></p>';
			$linkback = $beglinkback . '&amp;showtag=' . $_GET['showtag'] . '">';
			$bottomlink = '<p>' . $linkback . sprintf(__('Return to page %s of photos from the &laquo;%s&raquo; tag','flickpress'), $page, $_GET['showtag']) . "</a></p>\n";
			$toplink = $begtoplink . '&amp;fpaction=tags&amp;fpshowtags=popular">' . __('tags','flickpress') . '</a> : ' . $linkback . $_GET['showtag'] . $endtoplink;
		} elseif ($_GET['returnto'] == 'faves') {
			$linkback = $beglinkback . '">';
			$importlink = '<p><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=showphoto&amp;fptype=user&amp;fpid=' . $_GET['fpid'] . '&amp;fpuname=' . $_GET['fpuname'] . '&amp;photoid=' . $_GET['photoid'] . '&amp;returnto=faves&amp;fp_page=' . $page . '&amp;fpimport=yes">' . __('Import into Media Library','flickpress') . '</a></p>';
			$bottomlink = '<p>' . $linkback . sprintf(__('Return to page %s of %s’s favorite photos','flickpress'), $page, $unametxt) . "</a></p>\n";
			$toplink = $begtoplink . '&amp;fpaction=faves&amp;fp_page=' . $page . '">' . __('favorites','flickpress') . $endtoplink;
		} elseif ($_GET['returnto'] == 'interesting') {
			$linkback = '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=' . $_GET['returnto'] . '&amp;fp_page=' . $page . '">';
			$importlink = '<p><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=showphoto&amp;photoid=' . $_GET['photoid'] . '&amp;returnto=interesting&amp;fp_page=' . $page . '&amp;fpimport=yes">' . __('Import into Media Library','flickpress') . '</a></p>';
			$bottomlink = '<p>' . $linkback . sprintf(__('Return to page %s of interesting photos','flickpress'), $page) . "</a></p>\n";
			$toplink = '<h3><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=users">' . __('Home','flickpress') . '</a> : ' . $linkback . __('Interesting','flickpress') . $endtoplink;
		} elseif($_GET['returnto'] == 'recent') {
			$linkback = $beglinkback . '">';
			$context = $phpflickpress->photos_getContext($_GET['photoid'],NULL);
			$prevnext = '';
			if ($context['nextphoto']['id'] !== 0)
				$prevnext .= '<a class="prev page-numbers" href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=showphoto&amp;fptype=user&amp;fpid=' . $_GET['fpid'] . '&amp;fpuname=' . $_GET['fpuname'] . '&amp;photoid=' . $context['nextphoto']['id'] . '&amp;returnto=recent&amp;fp_page=' . $page . '">' . __('&laquo; Newer','flickpress') . '</a>';
			if (($context['prevphoto']['id'] !== 0) && ($context['nextphoto']['id'] !== 0))
				$prevnext .= ' <span class="page-numbers dots">...</span> ';
			if ($context['prevphoto']['id'] !== 0)
				$prevnext .= '<a class="next page-numbers" href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=showphoto&amp;fptype=user&amp;fpid=' . $_GET['fpid'] . '&amp;fpuname=' . $_GET['fpuname'] . '&amp;photoid=' . $context['prevphoto']['id'] . '&amp;returnto=recent&amp;fp_page=' . $page . '">' . __('Older &raquo;','flickpress') . '</a>';
			$importlink = '<p><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?fp_parent_ID=' . $fp_parent_ID . '&amp;fpaction=showphoto&amp;fptype=user&amp;fpid=' . $_GET['fpid'] . '&amp;fpuname=' . $_GET['fpuname'] . '&amp;photoid=' . $_GET['photoid'] . '&amp;returnto=recent&amp;fp_page=' . $page . '&amp;fpimport=yes">' . __('Import into Media Library','flickpress') . '</a> ';
			$bottomlink = '<p>' . $linkback . sprintf(__('Return to page %s of recent photos','flickpress'), $page) . "</a></p>\n";
			$toplink = $begtoplink . '&amp;fpaction=recent&amp;fp_page=' . $page . '">' . __('recent','flickpress') . $endtoplink . "\n<div class='tablenav-pages prevnext'>" . $prevnext . "</div>\n";
		}
		}
	}
	echo $toplink;
	foreach ($sizes as $size) {
		$fcodes[$size['label']] = '<img alt="' . $title . '" src="' . $size['source'] . '" title="' . $title . '" width="' . $size['width'] . '" height="' . $size['height'] . '" />';
		$capflinked[$size['label']] = '<a href="' . $photoinfo['photo']['urls']['url']['0']['_content'] . '">' . $fcodes[$size['label']] . '</a>';
		$flinked[$size['label']] = '<a href="' . $photoinfo['photo']['urls']['url']['0']['_content'] . '">' . '<img alt="' . $title . '" src="' . $size['source'] . '" title="' . $title . '" width="' . $size['width'] . '" height="' . $size['height'] . '" class="' . $flickpress_options['insclass'] . '" />' . '</a>';
		$divwidth = $size['width'] + 10;
		$finserts[$size['label']] = '<strong><a class="fpinserting" href="#" onclick="insertcode(\'' . esc_js($size['source']) . '\',document.getElementById (\'add_caption\').checked,document.getElementById (\'add_desc\').checked,document.getElementById (\'add_exif\').checked,' . $divwidth . ',' . $size['width'] . ',' . $size['height'] . '); return false;">' . $size['label'] . "</a></strong> (" . $size['width'] . 'x' . $size['height'] . ")<br />\n";
	}
	if (isset($fcodes['Small'])) {
		$popcode = $fcodes['Small'];
	} elseif (isset($fcodes['Thumbnail'])) {
		$popcode = $fcodes['Thumbnail'];
	} else {
		$popcode = __('Odd, there is no image to display...','flickpress');
	}
	echo '<div class="flickmiddle"><div id="flickleft"><p><a href="' . $photoinfo['photo']['urls']['url']['0']['_content'] . '">' . $popcode . '</a></p>
		<p><strong>' . __('Title:','flickpress') . ' </strong>' . $title . '</p>
		<p><strong>' . __('Owner:','flickpress') . '</strong> <a href="' . substr($photoinfo['photo']['urls']['url']['0']['_content'],0,strrpos($photoinfo['photo']['urls']['url']['0']['_content'],'/',-2)+1) . '">' . $photoinfo['photo']['owner']['username'] . "</a></p>\n";
	if ($hasdesc === TRUE)
		echo '<p><strong>' . __('Description:','flickpress') . '</strong></p><p>' . $description . "</p>\n";
	if ($hastags === TRUE)
		echo '<p><strong>' . __('Tags:','flickpress') . '</strong></p><p>' . $displaytags . "</p>\n";
	if ($hasexif === TRUE)
		echo '<p><strong>' . __('EXIF:','flickpress') . '</strong><br />' . $exiftable . "</p>\n";
	$fplicenses = $phpflickpress->photos_licenses_getInfo();
	echo '<p><strong>' . __('License:','flickpress') . '</strong> ';
	foreach ($fplicenses as $fplicense) {
		if ($fplicense['id'] == $photoinfo['photo']['license']) {
			if ($fplicense['url']) {
				echo '<a href="' . $fplicense['url'] . '">' . $fplicense['name'] . '</a><br />';
			} else {
				echo $fplicense['name'] . '<br />';
			}
		}
	}
	if ($flickpress_options['captions'] == 'no') {
		$fpchecked = '';
	} else {
		$fpchecked = ' checked="checked"';
	}
	echo '</p></div>
	<div id="flickright">
	<p><input name="add_caption" id="add_caption" value="1" type="checkbox"' . $fpchecked . ' /> <label for="add_caption">' . __('Caption the inserted image with the photo title and owner (e.g., to comply with licenses that require attribution).','flickpress') . '</label></p>';
	if ($hasdesc === TRUE)
		echo '<p><input name="add_desc" id="add_desc" value="1" type="checkbox" /> <label for="add_desc">' . __('Add the photo description.','flickpress') . '</label></p>';
	else
		echo '<input type="hidden" name="add_desc" id="add_desc" value="0" />';
	if ($hasexif === TRUE)
		echo '<p><input name="add_exif" id="add_exif" value="1" type="checkbox" /> <label for="add_exif">' . __('Add a table of EXIF information.','flickpress') . '</label></p>';
	else
		echo '<input type="hidden" name="add_exif" id="add_exif" value="0" />';
	echo '<p>' . __('<strong>Click a size to add it to your post:</strong>','flickpress') . '</p>
	<p>';
	foreach ($finserts as $finsert) {
		echo $finsert;
	}
	if (!current_user_can('upload_files'))
		$importlink = '';
	echo "<span class='fpinserted'>" . __('Inserted it!','flickpress') . "</span></p></div></div>\n<div id='flickfoot'><p>" . __('Please be sure that your use is compatible with the photo license.','flickpress') . '</p>' . $bottomlink . $importlink . "\n</div></div>\n</body>\n</html>";
	die();
}

?>
