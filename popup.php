<?php
/* flickpress popup tool */
require_once( dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php');
require_once( ABSPATH . 'wp-content/plugins/flickpress/include.php');
$flickpress_options = get_option('flickpress_options');
if ($user_ID == '')
	die (__('Try logging in','flickpress'));
if (!current_user_can($flickpress_options['usecap']))
	die (__('Ask the administrator to promote you, or verify that the capability entered at Settings:flickpress is correct.','flickpress'));
if (empty($flickpress_options['apikey']))
	die (__('No Flickr API key found, please enter one at Settings:flickpress.','flickpress'));
$flick = new phpFlickr($flickpress_options['apikey']);
$fcon = "mysql://" . DB_USER . ":" . DB_PASSWORD . "@" . DB_HOST . "/" . DB_NAME;
$flick->enableCache($type = 'db', $fcon , $cache_expire = 600, $table = $table_prefix.'flickpress_cache');
$check = $flick->test_echo();
if ($check['stat'] !== 'ok')
	die (__('The Flickr API key you entered is not working, please verify it at Settings:flickpress and your Flickr account.','flickpress'));
$per_page = 32;
// print the header and the magical javascript
echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>' . __('flickpress: insert Flickr photos','flickpress') . '</title>
<link rel="stylesheet" href="' . get_bloginfo('wpurl') . '/wp-admin/css/global.css" type="text/css" />
<link rel="stylesheet" href="' . get_bloginfo('wpurl') . '/wp-admin/wp-admin.css" type="text/css" />
<link rel="stylesheet" href="' . get_bloginfo('wpurl') . '/wp-admin/css/colors-fresh.css" type="text/css" />
<link rel="stylesheet" href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.css" type="text/css" />
<link rel="shortcut icon" href="' . get_bloginfo('wpurl') . '/wp-images/wp-favicon.png" />
<meta http-equiv="Content-Type" content="text/html; charset=' . get_settings('blog_charset') . '" />
<script type="text/javascript" src="' . get_bloginfo('wpurl') . '/wp-includes/js/jquery/jquery.js"></script>
<script type="text/javascript">
//<![CDATA[
window.focus();
var winder = window.top;
function insertcode(linkcode,docaption,caption,divwidth) {
	if (docaption == "1") {
		var linkcode = "\n<div class=\"wp-caption alignnone\" style=\"width: " + divwidth + "px\;\">" + linkcode + "<p class=\"wp-caption-text\">" + caption + "</p></div>\n";
	} else {
		var linkcode = "\n" + linkcode + "\n";
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
   jQuery("div.fpshowhide").hide();
   jQuery("a.fptoggle").click(function(){
      jQuery("div.fpshowhide").toggle();
   });
});
//]]>
</script>
</head>
<body>
	<ul id="popupmenu">
		<li><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=users" class="current">' . __('Home','flickpress') . '</a></li>
	</ul>
<div class="wrap">
';

// display the user browser
if ($_GET['action'] == 'users' || $_POST['action'] == 'useradd') {
	if (isset($_POST['useradd'])) {
		if (strpos($_POST['email'],'@') === FALSE) {
			$useradd_info = $flick->people_findByUsername($_POST['email']);
		} else {
			$useradd_info = $flick->people_findByEmail($_POST['email']);
		}
		if ($useradd_info) {
			$update_array = array('flickrid'=>$useradd_info['id'],'flickrname'=>$useradd_info['username']);
			if (flickpress_update($update_array)) {
				printf(__("<p class='fpupdated'>Added %s, you may now browse their photos.</p>\n","flickpress"),$useradd_info['username']);
			} else {
				printf(__("<p class='fperror'>Failed to add %s, possibly due to a database error.</p>\n","flickpress"),$useradd_info['username']);
			}
		} else {
				printf(__("<p class='fperror'>No Flickr user found linked to %s, check the email or username and try again.</p>\n","flickpress"),$_POST['email']);
		}
	}
	echo '<h3>' . __('Insert photos from a Flickr account','flickpress') . '</h3>
	<p>' . __('Click one of the usernames to browse their photos:','flickpress') . '</p>
';
	if ($flickpress_stored = flickpress_getlist()) {
		foreach ($flickpress_stored as $flickr_user) {
			echo '<p><strong><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=options&amp;type=user&amp;id=' . $flickr_user['flickrid'] . '&amp;uname=' . urlencode($flickr_user['flickrname']) . '">' . $flickr_user['flickrname'] . "</a></strong></p>\n";
		}
	}
	$licenses = $flick->photos_licenses_getInfo();
	echo '<p><form name="flickpress_adduser" method="post">
        <input type="hidden" name="useradd" value="update" />
        ' . __('Enter a Flickr username or email to add:','flickpress') . '<br /><input type="text" name="email" value="" size="15" /> <input type="submit" name="Submit" value="' . __('Look up Flickr user &raquo;','flickpress') . '" /></form>
	</p>
	<h3>' . __('Search for CC-licensed, government, and Flickr Commons photos','flickpress') . '</h3>
	<form name="flickpress_search" method="post" action="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php">
	<p><a class="fptoggle" style="cursor:pointer">' . __('Choose licenses &raquo;','flickpress') . '</a></p>
	<div class="fpshowhide">
	<p>' . __('Please be sure that your use is compatible with the photo license.','flickpress') . '</p>
	<ul>';
	foreach ($licenses as $license) {
		if ($license['id'] !== '0') {
			echo '<li><input name="licensetype[]" value="' . $license['id'] . '" type="checkbox" checked="checked" /> <a href="' . $license['url'] . '">' . $license['name'] . "</a></li>\n";
		}
	}
   echo '</ul>
	</div>
	<p><input type="text" name="searchtext" value="" size="15" /> <input type="submit" name="Submit" value="' . __('Find photos &raquo;','flickpress') . '" /></form>
	</p>
	</div>';
echo '</body>
</html>';
die();
}

if (isset($_GET['uname'])) {
	$userlink = '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=options&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . urlencode($_GET['uname']) . '">' . $_GET['uname'] . '</a>';
}

// display the user options page
if ($_GET['action'] == 'options') {
	echo '<h3>' . $_GET['uname'] . '</h3>
	<p><strong>Browse:</strong><br />
	<ul>
	<li><strong><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=sets&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '">' . __('sets','flickpress') . ' &raquo;</a></strong></li>
	<li><strong><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=tags&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '">' . __('tags','flickpress') . ' &raquo;</a></strong></li>
	<li><strong><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=recent&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '">' . __('recent','flickpress') . ' &raquo;</a></strong></li>
	</ul>
	<p><strong>Search:</strong><br />
	<form name="flickpress_search" method="post" action="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php">
	<input type="hidden" name="userid" value="' . $_GET['id'] . '" />
	<input type="hidden" name="uname" value="' . $_GET['uname'] . '" />
   <input type="text" name="searchtext" value="" size="15" /> <input type="submit" name="Submit" value="' . __('Find photos &raquo;','flickpress') . '" /></form></p>
        </div>
</body>
</html>';
die();
}

// Display a user's recent photos
if ($_GET['action'] == 'recent') {
	if (isset($_GET['page'])) {
		$page = $_GET['page'];
	} else {
		$page = 1;
	}
	$user_info = $flick->people_getInfo($_GET['id']);
	$photos_url = $user_info['photosurl'];
	$num_photos = $user_info['photos']['count'];
	$photos = $flick->people_getPublicPhotos($_GET['id'],NULL,NULL,$per_page,$page);
	echo '<h3>' . $userlink . __(' : recent','flickpress') . "</h3>\n";
        echo "\n<p>\n";
	foreach ((array)$photos['photos']['photo'] as $photo) {
		$photourl = $flick->buildPhotoURL($photo, 'Square');
		$imgcode = '<img alt="' . $photo['title'] . '" src="' . $photourl . '" width="75" height="75" />';
		echo '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=showphoto&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '&amp;photoid=' . $photo['id'] . '&amp;returnto=recent&amp;page=' . $page . '">' . $imgcode . '</a> ';
		unset($photourl,$imgcode,$flickrcode);
	}
	echo "</p>\n";
	echo "<p>\n";
	$pages = ceil($num_photos/$per_page);
	for ($i=1;$i<=$pages;$i++) {
		if ($i == $page) {
			echo $i . " \n";
		} else {
			echo '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=recent&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '&amp;page=' . $i . '">' . $i . "</a> \n";
		}
	}
	echo '</p>
	</div>
</body>
</html>';
die();
}

// Display the search results
if (isset($_POST['searchtext']) || isset($_GET['searchtext'])) {
	if (isset($_POST['searchtext'])) {
		$searchtext = $_POST['searchtext'];
		if (isset($_POST['licensetype'])) {
			$s_licenses = $_POST['licensetype'];
		} elseif (isset($_POST['userid'])) {
			$userid = $_POST['userid'];
			$uname = $_POST['uname'];
		} else {
			die(__('Search failed: missing userid or licensetype.'));
		}
	} else {
		$searchtext = $_GET['searchtext'];
		if (isset($_GET['licenses'])) {
			$s_licenses = explode(' ',$_GET['licenses']);
		} elseif (isset($_GET['userid'])) {
			$userid = $_GET['userid'];
			$uname = $_GET['uname'];
		} else {
			die(__('Search failed: missing userid or licensetype.'));
		}
	}
	if (isset($_GET['page'])) {
		$page = $_GET['page'];
   } else {
		$page = 1;
	}
	if (isset($s_licenses)) {
		$licenses = $flick->photos_licenses_getInfo();
		$comma_licenses = implode(',',$s_licenses);
		$plus_licenses = implode('+',$s_licenses);
		$searcharray = array('text' => $searchtext,'license' => $comma_licenses,'page' => $page,'per_page' => $per_page);
	} elseif (isset($userid)) {
		$searcharray = array('text' => $searchtext,'user_id' => $userid,'page' => $page,'per_page' => $per_page);
	}
	$photos = $flick->photos_search($searcharray);
	if (isset($s_licenses)) {
		echo '<h3>' . __('Searched CC-licensed, government, and Flickr Commons photos for: &laquo;','flickpress') . $searchtext . '&raquo;</h3>';
	} else {
		echo '<h3><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=options&amp;type=user&amp;id=' . $userid . '&amp;uname=' . urlencode($uname) . '">' . $uname . '</a>' . __(' : search : &laquo;','flickpress') . $searchtext . "&raquo;</h3>\n";
	}
	echo "\n<p>";
	if (isset($s_licenses)) {
		$addthis = 'licenses=' . $plus_licenses; 
	} else { 
		$addthis = 'userid=' . $userid . '&amp;uname=' . urlencode($uname); 
	}
	if ($photos['total'] > 0) { 
		foreach ((array)$photos['photo'] as $photo) {
			$photourl = $flick->buildPhotoURL($photo, 'Square');
			$imgcode = '<img alt="' . $photo['title'] . '" src="' . $photourl . '" width="75" height="75" />';
			echo '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=showphoto&amp;photoid=' . $photo['id'] . '&amp;page=' . $page . '&amp;insearch=' . urlencode($searchtext) . '&amp;' .$addthis . '">' . $imgcode . '</a> ';
			unset($photourl,$imgcode);
		}
	} else {
		echo '<strong>' . __('None found!','flickpress') . '</strong>';
	}
   echo "</p>\n";
   echo "<p>\n";
	if (((int)$photos['total'] > 0) && ((int)$photos['pages'] > 1)) {
		if ((int)$page > 1) {
			echo '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?searchtext=' . urlencode($searchtext) . '&amp;page=' . ($page - 1) . '&amp;' . $addthis . '">' . __('Previous page','flickpress') . "</a> ";
		}
		if ((int)$photos['pages'] !== (int)$page) {
			echo '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?searchtext=' . urlencode($searchtext) . '&amp;page=' . ($page + 1) . '&amp;' . $addthis . '">' . __('Next page','flickpress') . "</a> ";
		}
	}
	echo '</p>
	<p>
		<form name="flickpress_search" method="post" action="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php">';
	if (isset($s_licenses)) {
		echo '<p><a class="fptoggle">' . __('Choose licenses &raquo;','flickpress') . '</a></p>
		<div class="fpshowhide">
		<ul>
		';
		foreach ($licenses as $license) {
			if ($license['id'] !== '0' && in_array($license['id'],$s_licenses) ) {
				echo '<li><input name="licensetype[]" value="' . $license['id'] . '" type="checkbox" checked="checked" /> <a href="' . $license['url'] . '">' . $license['name'] . "</a></li>\n";
			} elseif ($license['id'] !== '0') {
				echo '<li><input name="licensetype[]" value="' . $license['id'] . '" type="checkbox" /> <a href="' . $license['url'] . '">' . $license['name'] . "</a></li>\n";
			}
		}
		echo '</ul>
		</div>
		';
	} else {
		echo '<input type="hidden" name="userid" value="' . $userid . '" />
   <input type="hidden" name="uname" value="' . $uname . '" />
';
	}
	echo '<input type="text" name="searchtext" value="" size="15" /> <input type="submit" name="Submit" value="' . __('New search &raquo;','flickpress') . '" /></form>
	</p>
	</div>
</body>
</html>';
die();
}

// display a user's photosets (should this be paged?)
if ($_GET['action'] == 'sets') {
	$photos_url = $flick->urls_getUserPhotos($_GET['id']);
	$setslink = '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=sets&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '">' . __('photosets','flickpress') . '</a>';
	if (isset($_GET['showset'])) {
		if (isset($_GET['page'])) {
			$page = $_GET['page'];
		} else {
			$page = 1;
		}
		$setphotos = $flick->photosets_getPhotos($photoset_id = $_GET['showset'], $extras = NULL, $privacy_filter = NULL, $per_page = $per_page, $page = $page);
		$setinfo = $flick->photosets_getInfo($photoset_id = $_GET['showset']);
		echo '<h3>' . $userlink . ' : ' . $setslink . ' : ' . $setinfo['title'] . "</h3>\n<p>Click to insert a photo:</p>\n<p>\n";
		foreach ((array)$setphotos['photoset']['photo'] as $photo) {
			$photourl = $flick->buildPhotoURL($photo, "Square");
			$imgcode = '<img alt="' . $photo['title'] . '" src="' . $photourl . '" width="75" height="75" />';
			echo '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=showphoto&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '&amp;photoid=' . $photo['id'] . '&amp;returnto=sets&amp;showset=' . $_GET['showset'] . '&amp;page=' . $page . '">' . $imgcode . '</a> ';
			unset($photourl,$imgcode,$flickrcode);
		}
		if ($setphotos['photoset']['pages'] > 1) {
			echo "<p>\n";
			for ($i=1;$i<=$setphotos['photoset']['pages'];$i++) {
				if ($i == $page) {
					echo $i . " \n";
				} else {
					echo '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=sets&amp;showset=' . $_GET['showset'] . '&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '&amp;page=' . $i . '">' . $i . "</a> \n";
				}
			}
			echo "</p>\n";
		}
		echo "</p>\n";
	} else {
		echo '<h3>' . $userlink . __(' : photosets','flickpress'). "</h3>\n";
		$photosets = $flick->photosets_getList($_GET['id']);
		echo "\n<ul>\n";
		foreach ((array)$photosets['photoset'] as $photoset) {
			echo '<li><strong><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=sets&amp;showset=' . $photoset['id'] . '&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '">' . $photoset['title'] . ' (' . $photoset['photos'] . ')</a></strong></li>';
		}
		echo "</ul>\n";
        }
        echo '</div>
</body>
</html>';
die();
}

// display a user's tags or the photos for one of the tags
if ($_GET['action'] == 'tags') {
	$photos_url = $flick->urls_getUserPhotos($_GET['id']);
	$tagslink = '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=tags&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '">' . __('tags','flickpress') . '</a>';
	// show the photos for a tag
	if (isset($_GET['showtag'])) {
	echo '<h3>' . $userlink . ' : ' . $tagslink . ' : ' . $_GET['showtag'] . "</h3>\n";
		if (isset($_GET['page'])) {
			$page = $_GET['page'];
		} else {
			$page = 1;
		}
		$tagphotos = $flick->photos_search(array("user_id" => $_GET['id'], "tags" => $_GET['showtag'], "per_page" => $per_page, "page" => $page));
		foreach ((array)$tagphotos['photo'] as $tagphoto) {
			$photourl = $flick->buildPhotoURL($tagphoto, "Square");
			$imgcode = '<img alt="' . $tagphoto['title'] . '" src="' . $photourl . '" width="75" height="75" />';
			echo '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=showphoto&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '&amp;photoid=' . $tagphoto['id'] . '&amp;returnto=tags&amp;showtag=' . $_GET['showtag'] . '&amp;page=' . $page . '">' . $imgcode . '</a> ';
			unset($photourl,$imgcode,$flickrcode);
		}
		$num_photos = $tagphotos['total'];
		$pages = ceil($num_photos/$per_page);
		for ($i=1;$i<=$pages;$i++) {
			if ($i == 1) {
				echo '<p>';
			}
			echo '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=tags&amp;showtag=' . $_GET['showtag'] . '&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '&amp;page=' . $i . '">' . $i . "</a> \n";
			if ($i == $pages) {
				echo "</p>\n";
			}
		}
	// list the user's tags
	} else {
		echo '<h3>' . $userlink . __(' : tags ','flickpress') . "</h3>\n";
		if ($tags = $flick->tags_getListUser($_GET['id'])) {
        	echo "\n<ul>\n";
        foreach ((array)$tags as $tag) {
				echo '<li><strong><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=tags&amp;showtag=' . $tag . '&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '">' . $tag . '</a></strong></li>';
			}
			echo "\n</ul>\n";
		} else {
			echo '<p><strong>' . __('No tags found!','flickpress') . '</strong></p>';
		}
	}
        echo '</div>
</body>
</html>';
die();
}

// Display photo info and insert stuff
if ($_GET['action'] == 'showphoto') {
	if (isset($_GET['licenses'])) {
		$plus_licenses = str_replace(' ','+',$_GET['licenses']);
	}
	$photoinfo = $flick->photos_getInfo($_GET['photoid'],NULL);
	$sizes = $flick->photos_getSizes($_GET['photoid']);
	$caption = '<a href="' . $photoinfo['urls']['url']['0']['_content'] . '">' . $photoinfo['title'] . '</a> by <a href="' . $flick->urls_GetUserPhotos($photoinfo['owner']['nsid']) . '">' . $photoinfo['owner']['username'] . '</a>';
	if (isset($_GET['insearch'])) {
		if (isset($_GET['licenses'])) {
			echo '<h3>' . __('Searched CC-licensed, government, and Flickr Commons photos for &laquo;','flickpress') . $_GET['insearch'] . '&raquo;</h3>
		<p><strong>' . $caption . "</strong></p>\n<form>\n";
		} else {
			echo '<h3' . '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=options&amp;type=user&amp;id=' . $_GET['userid'] . '&amp;uname=' . urlencode($_GET['uname']) . '">' . $_GET['uname'] . '</a> : ' . $photoinfo['title'] . "</h3>\n<form>\n";
		}
	} else {
		echo '<h3>' . $userlink . ' : ' . $photoinfo['title'] . "</h3>\n<form>\n";
	}
	foreach ($sizes as $size) {
		$fcodes[$size['label']] = '<img alt="' . $photoinfo['title'] . '" src="' . $size['source'] . '" title="' . $photoinfo['title'] . '" width="' . $size['width'] . '" height="' . $size['height'] . '" />';
		$flinked[$size['label']] = '<a href="' . $photoinfo['urls']['url']['0']['_content'] . '">' . $fcodes[$size['label']] . '</a>';
		$divwidth = $size['width'] + 10;
		$finserts[$size['label']] = '<strong><a href="#" onClick="insertcode(\'' . js_escape($flinked[$size['label']]) . '\',document.getElementById (\'add_caption\').checked,\'' . js_escape($caption) . '\',' . $divwidth . '); return false;">' . $size['label'] . "</a></strong> (" . $size['width'] . 'x' . $size['height'] . ")<br />\n";
	}
	if (isset($fcodes['Small'])) {
		$popcode = $fcodes['Small'];
	} elseif (isset($fcodes['Thumbnail'])) {
		$popcode = $fcodes['Thumbnail'];
	} else {
		$popcode = __('Odd, there is no image to display...','flickpress');
	}
	echo '<p>' . $popcode . "</p>\n";
	if (!empty($photoinfo['description'])) {
		echo '<p>' . __('<strong>Description:</strong>','flickpress') . '<br />' . $photoinfo['description'] . "</p>\n";
	}
	$licenses = $flick->photos_licenses_getInfo();
	echo '<p>' . __('<strong>License:</strong>','flickpress') . '<br />';
	foreach ($licenses as $license) {
		if ($license['id'] == $photoinfo['license']) {
			if ($license['url']) {
				echo '<a href="' . $license['url'] . '">' . $license['name'] . '</a><br />';
			} else {
				echo $license['name'] . '<br />';
			}
		}
	}
	if ($flickpress_options['captions'] == 'yes') {
		$checked = ' checked="checked"';
	} else {
		$checked = '';
	}
	echo __('Please be sure that your use is compatible with the photo license.','flickpress') . '</p>
	<p><input name="add_caption" id="add_caption" value="1" type="checkbox"' . $checked . '> <label for="add_caption">' . __('Caption the inserted image with the photo title and owner (to comply with licenses that require attribution).','flickpress') . '</label></p>
	<p>' . __('<strong>Click a size to add it to your post:</strong>','flickpress') . '<br />
	';
	foreach ($finserts as $finsert) {
		echo $finsert;
	}
	echo "</p>\n";
	if (isset($_GET['insearch'])) {
		if (isset($plus_licenses)) {
			echo '<p><strong><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?searchtext=' . urlencode($_GET['insearch']) . '&amp;page=' . $_GET['page'] . '&amp;licenses=' . $plus_licenses . '">' . __('Return to page ','flickpress') . $_GET['page'] . __(' of photos from your search for &laquo;','flickpress') . $_GET['insearch'] . "&raquo;</a></p>\n</form>\n";
		} else {
			echo '<p><strong><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?searchtext=' . urlencode($_GET['insearch']) . '&amp;page=' . $_GET['page'] . '&amp;userid=' . $_GET['userid'] . '&amp;uname=' . $_GET['uname'] . '">' . __('Return to page ','flickpress') . $_GET['page'] . __(' of photos from your search for &laquo;','flickpress') . $_GET['insearch'] . "&raquo;</a></p>\n</form>\n";
		}
	} else {
		if ($_GET['returnto'] == 'sets') {
			$setinfo = $flick->photosets_getInfo($_GET['showset']);
			$displaytxt = '&amp;showset=' . $_GET['showset'] . '">' . __('Return to page ','flickpress') . $_GET['page'] . __(' of photos from the (','flickpress') . $setinfo['title'] . __(') photoset.','flickpress');
		} elseif ($_GET['returnto'] == 'tags') {
			$displaytxt = '&amp;showtag=' . $_GET['showtag'] . '">' . __('Return to page ','flickpress') . $_GET['page'] . __(' of photos from the (','flickpress') . $_GET['showtag'] . __(') tag.','flickpress');
		} else {
			$displaytxt = '">' . __('Return to page ','flickpress') . $_GET['page'] . __(' of recent photos.','flickpress');
		}
		echo '<p><strong><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=' . $_GET['returnto'] . '&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '&amp;page=' . $_GET['page'] . $displaytxt . "</a></p>\n</form>\n";
	}
	echo '</div>
</body>
</html>';
die();
}

?>
