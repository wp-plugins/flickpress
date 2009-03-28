<?php
/* flickpress popup tool */
require_once( dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-config.php');
require_once( ABSPATH . 'wp-content/plugins/flickpress/include.php');
if ($user_ID == '')
	die (__('Try logging in','flickpress'));
if (!current_user_can($flickpress_options['capability']))
	die (__('Ask the administrator to promote you.','flickpress'));
if (empty($flickpress_options['apikey']))
	die (__('No Flickr API key found, please enter one at Settings:flickpress.','flickpress'));
$flick = new phpFlickr($flickpress_options['apikey']);
$fcon = "mysql://" . DB_USER . ":" . DB_PASSWORD . "@" . DB_HOST . "/" . DB_NAME;
$flick->enableCache($type = 'db', $fcon , $cache_expire = 600, $table = $table_prefix.'flickpress_cache');
$check = $flick->test_echo();
if ($check['stat'] !== 'ok')
	die (__('The Flickr API key you entered is not working, please verify at Settings:flickpress and your Flickr account.','flickpress'));
$per_page = 20;
// print the header and the magical javascript
echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>' . __('flickpress: insert images from Flickr','flickpress') . '</title>
<link rel="stylesheet" href="' . get_bloginfo('wpurl') . '/wp-admin/css/global.css" type="text/css" />
<link rel="stylesheet" href="' . get_bloginfo('wpurl') . '/wp-admin/wp-admin.css" type="text/css" />
<link rel="stylesheet" href="' . get_bloginfo('wpurl') . '/wp-admin/css/colors-fresh.css" type="text/css" />
<link rel="stylesheet" href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.css" type="text/css" />
<link rel="shortcut icon" href="' . get_bloginfo('wpurl') . '/wp-images/wp-favicon.png" />
<meta http-equiv="Content-Type" content="text/html; charset=' . get_settings('blog_charset') . '" />
<script type="text/javascript">
//<![CDATA[
window.focus();
function insertcode(linkcode,docaption,caption,divwidth) {
	if (docaption == "1") {
		var linkcode = "<div class=\"wp-caption alignnone\" style=\"width: " + divwidth + "px\;\">" + linkcode + "<p class=\"wp-caption-text\">" + caption + "</p></div>";
	}
   var winder = window.top;
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
		$useradd_info = $flick->people_findByEmail($_POST['email']);
		if ($useradd_info) {
			$update_array = array('flickrid'=>$useradd_info['id'],'flickrname'=>$useradd_info['username']);
			flickpress_update($update_array);
		} else {
			echo '<p class="fperror">' . __('Failed to find a user with that email address!','flickpress') . "</p>\n";
		}
	}
	echo '<h3>' . __('Insert images from Flickr','flickpress') . '</h3>
	<p>' . __('Listed below are Flickr accounts we know about, with yours at the top if one was found using your WordPress email address. To add a different account just enter an email address in the little form below. To browse and insert photos into your post just click one of the usernames.','flickpress') . '</p>
';
	if ($flickpress_stored = flickpress_getlist()) {
		foreach ($flickpress_stored as $flickr_user) {
			echo '<p><strong><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=options&amp;type=user&amp;id=' . $flickr_user['flickrid'] . '&amp;uname=' . $flickr_user['flickrname'] . '">' . $flickr_user['flickrname'] . "</a></strong></p>\n";
		}
	}
	echo '<form name="flickpress_adduser" method="post">
        <input type="hidden" name="useradd" value="update" />
        ' . __('Enter a Flickr user email to add:','flickpress') . '<br /><input type="text" name="email" value="" size="15" /> <input type="submit" name="Submit" value="' . __('Look up Flickr user','flickpress') . '" /><br />
	</p>
	</div>
</body>
</html>';
die();
}

if (isset($_GET['uname'])) {
	$userlink = '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=options&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '">' . $_GET['uname'] . '</a>';
}

// display the options page
if ($_GET['action'] == 'options') {
	echo '<h3>' . $userlink . '</h3>
        <p>' . __('Click to display a list of <strong>sets</strong>, <strong>tags</strong>, or <strong>recent</strong> photos.','flickpress') . '</p>
	<ul>
	<li><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=sets&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '">' . __('sets','flickpress') . '</a></li>
	<li><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=tags&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '">' . __('tags','flickpress') . '</a></li>
	<li><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=recent&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '">' . __('recent','flickpress') . '</a></li>
	</ul>
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
	echo "</p>\n";
	echo '
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
		echo '<h3>' . $userlink . ' : ' . $setslink . "</h3>\n";
		$photosets = $flick->photosets_getList($_GET['id']);
		echo '<p>' . __('Choose a set to view:','flickpress') . '</p>';
		echo "\n<ul>\n";
		foreach ((array)$photosets['photoset'] as $photoset) {
			echo '<li><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=sets&amp;showset=' . $photoset['id'] . '&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '">' . $photoset['title'] . ' (' . $photoset['photos'] . ')</a></li>';
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
		$tags = $flick->tags_getListUser($_GET['id']);
		echo '<h3>' . $userlink . ' : ' . $tagslink . "</h3>\n<p>Choose a tag to view:</p>\n";
        	echo "\n<ul>\n";
	        foreach ((array)$tags as $tag) {
			echo '<li><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=tags&amp;showtag=' . $tag . '&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '">' . $tag . '</a></li>';
		}
		echo "\n</ul>\n";
	}
        echo '</div>
</body>
</html>';
die();
}

// Display photo info and insert stuff
if ($_GET['action'] == 'showphoto') {
	$photos_url = $flick->urls_getUserPhotos($_GET['id']);
	$photoinfo = $flick->photos_getInfo($_GET['photoid'],NULL);
	$sizes = $flick->photos_getSizes($_GET['photoid']);
	echo '<h3>' . $userlink . ' : ' . $photoinfo['title'] . "</h3>\n<form>\n";
	foreach ($sizes as $size) {
		$fcodes[$size['label']] = '<img alt="' . $photoinfo['title'] . '" src="' . $size['source'] . '" title="' . $photoinfo['title'] . '" width="' . $size['width'] . '" height="' . $size['height'] . '" />';
		$flinked[$size['label']] = '<a href="' . $photos_url . $_GET['photoid'] . '">' . $fcodes[$size['label']] . '</a>';
		$divwidth = $size['width'] + 10;
		$finserts[$size['label']] = '<strong><a href="#" onClick="insertcode(\'' . js_escape($flinked[$size['label']]) . '\',document.getElementById (\'add_caption\').checked,\'' . js_escape($photoinfo['title']) . '\',' . $divwidth . '); return false;">' . $size['label'] . "</a></strong> (" . $size['width'] . 'x' . $size['height'] . ")<br />\n";
	}
	if (isset($fcodes['Small'])) {
		$popcode = $fcodes['Small'];
	} elseif (isset($fcodes['Thumbnail'])) {
		$popcode = $fcodes['Thumbnail'];
	} else {
		$popcode = __('Odd, there is no image to show...','flickpress');
	}
	echo '<p>' . $popcode . "</p>\n";
	echo '<p>' . __('<strong>Description:</strong>','flickpress') . '<br />' . $photoinfo['description'] . "</p>\n";
	echo '<p>' . '<input name="add_caption" id="add_caption" value="1" type="checkbox"> <label for="add_caption">' . __('Caption the inserted image using its Flickr title.','flickpress') . "</label></p>\n";
	echo '<p>' . __('<strong>Insert this photo:</strong>','flickpress') . '<br />';
	foreach ($finserts as $finsert) {
		echo $finsert;
	}
	echo "</p>\n";
	if ($_GET['returnto'] == 'sets') {
		$setinfo = $flick->photosets_getInfo($_GET['showset']);
		$displaytxt = '&amp;showset=' . $_GET['showset'] . '">' . __('Return to page ','flickpress') . $_GET['page'] . __(' of photos from the (','flickpress') . $setinfo['title'] . __(') photoset.','flickpress');
	} elseif ($_GET['returnto'] == 'tags') {
		$displaytxt = '&amp;showtag=' . $_GET['showtag'] . '">' . __('Return to page ','flickpress') . $_GET['page'] . __(' of photos from the (','flickpress') . $_GET['showtag'] . __(') tag.','flickpress');
	} else {
		$displaytxt = '">' . __('Return to page ','flickpress') . $_GET['page'] . __(' of recent photos.','flickpress');
	}
	echo '<p><strong><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=' . $_GET['returnto'] . '&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '&amp;page=' . $_GET['page'] . $displaytxt . "</a></p>\n</form>\n";
	echo '</div>
</body>
</html>';
die();
}

?>
