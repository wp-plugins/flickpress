<?php
/* flickpress popup tool */
require_once( dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php');
require_once( ABSPATH . 'wp-content/plugins/flickpress/include.php');
$flickpress_options = get_option('flickpress_options'); // get the options
if (empty($flickpress_options['insclass'])) { // if alignment class is empty use "alignnone"
	$flickpress_options['insclass'] = 'alignnone';
}
if ($user_ID == '')
	die (__('Try logging in','flickpress'));
if (!current_user_can($flickpress_options['usecap']))
	die (__('Ask the administrator to promote you, or verify that the capability entered at Settings:flickpress is correct.','flickpress'));
if (empty($flickpress_options['apikey']))
	die (__('No Flickr API key found, please enter one at Settings:flickpress.','flickpress'));
// start up phpFlickr...or now phpFlickrpress
$flick = new phpFlickpress($flickpress_options['apikey']);
// enable the cache
$fcon = "mysql://" . DB_USER . ":" . DB_PASSWORD . "@" . DB_HOST . "/" . DB_NAME;
$flick->enableCache($type = 'db', $fcon , $cache_expire = 600, $table = $table_prefix.'flickpress_cache');
// test phpFlickr, die if not working
$check = $flick->photos_getRecent(NULL,1,1);
if ($check['page'] !== 1)
	die (__('Your Flickr API key seems to be invalid, please verify it is correct. This can also mean the Flickr API has changed, so if your key is correct check for a plugin update.','flickpress'));
$per_page = 32;
$version = get_bloginfo('version');
$major_version = (int)substr($wp_version,0,1);
if( $major_version > 2) { $csspath = 'css/'; }
// print the header and the magical javascript
echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>' . __('flickpress: insert Flickr photos','flickpress') . '</title>
<link rel="stylesheet" href="' . get_bloginfo('wpurl') . '/wp-admin/css/global.css" type="text/css" />
<link rel="stylesheet" href="' . get_bloginfo('wpurl') . '/wp-admin/' . $csspath . 'wp-admin.css" type="text/css" />
<link rel="stylesheet" href="' . get_bloginfo('wpurl') . '/wp-admin/css/colors-fresh.css" type="text/css" />
<link rel="stylesheet" href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.css" type="text/css" />
<link rel="shortcut icon" href="' . get_bloginfo('wpurl') . '/wp-images/wp-favicon.png" />
<meta http-equiv="Content-Type" content="text/html; charset=' . get_settings('blog_charset') . '" />
<script type="text/javascript" src="' . get_bloginfo('wpurl') . '/wp-includes/js/jquery/jquery.js"></script>
<script type="text/javascript">
//<![CDATA[
window.focus();
var winder = window.top;
function insertcode(linkcode,docaption,dodesc,doexif,divwidth) {
	if (docaption == "1") {
		var linkcode = "<div class=\"wp-caption ' . $flickpress_options['insclass'] . '\" style=\"width: " + divwidth + "px\;\">" + linkcode + "<p class=\"wp-caption-text\">" + unescape(imgcaption) + "</p>";
		if (dodesc == "1") {
			var linkcode = linkcode + "<p class=\"wp-caption flickr-desc\">" + unescape(imgdescription) + "</p>";
		}
		if (doexif == "1") {
			var linkcode = linkcode + unescape(imgexif);
		}
		var linkcode = linkcode + "</div>\n";
	} else {
		var linkcode = linkcode + "\n";
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
<div class="wrap">
';

// display the user browser
if ($_GET['action'] == 'users' || isset($_POST['useradd']) ) {
	if (isset($_POST['useradd'])) {
		if (strpos($_POST['email'],'@') === FALSE) {
			$useradd_info = $flick->people_findByUsername($_POST['email']);
		} else {
			$useradd_info = $flick->people_findByEmail($_POST['email']);
		}
		if ($useradd_info) {
			if (flickpress_is_user($useradd_info['id'])) {
				printf(__("<p class='fpupdated'>%s has already been added.</p>\n","flickpress"),$useradd_info['username']);
			} else {
				$update_array = array('flickrid'=>$useradd_info['id'],'flickrname'=>$useradd_info['username']);
				if (flickpress_update($update_array)) {
					printf(__("<p class='fpupdated'>Added %s, you may now browse their photos.</p>\n","flickpress"),$useradd_info['username']);
				} else {
					printf(__("<p class='fperror'>Failed to add %s, possibly due to a database error.</p>\n","flickpress"),$useradd_info['username']);
				}
			}
		} else {
					printf(__("<p class='fperror'>No Flickr user found linked to %s, check the email or username and try again.</p>\n","flickpress"),$_POST['email']);
		}
	}
	echo '<h3>' . __('Insert photos from a Flickr account','flickpress') . '</h3>
	<p>' . __('Click one of the usernames to browse their photos:','flickpress') . '</p>
';
	if ($flickpress_stored = flickpress_getlist()) {
		$cells = count($flickpress_stored);
		echo "<table class='usertable'><tbody>\n";
		$col = 1;
		foreach ((array)$flickpress_stored as $flickr_user) {
			if ($col == 1) {
				echo '<tr>';
			}
			echo '<td><a class="usernames" href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=options&amp;type=user&amp;id=' . $flickr_user['flickrid'] . '&amp;uname=' . urlencode($flickr_user['flickrname']) . '">' . $flickr_user['flickrname'] . "</a></td>";
			if ($col == 3) {
				echo '</tr>';
				$col = 1;
			} else {
				$col++;
			}
		}
		if ($col == 2) {
			echo "<td></td><td></td></tr>";
		} elseif ($col == 3) {
			echo "<td></td></tr>";
		}
		echo "</tbody></table>\n";
	}
	$licenses = $flick->photos_licenses_getInfo();
	echo '<form name="flickpress_adduser" method="post" action="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php">
        <input type="hidden" name="useradd" value="update" />
        ' . __('Enter a Flickr username or email to add:','flickpress') . '<br /><input type="text" name="email" value="" size="15" class="regular-text" /> <input type="submit" name="Submit" value="' . __('Look up Flickr user &raquo;','flickpress') . '" class="button" /></form>
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
	<p><input type="text" name="searchtext" value="" size="15" class="regular-text" /> <input type="submit" name="Submit" value="' . __('Find photos &raquo;','flickpress') . '" class="button" /></p></form>
	<h3><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=interesting">' . __('Browse interesting photos','flickpress') . '</a></h3>
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
	echo '<h3><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=users" class="current">' . __('Home','flickpress') . '</a> : ' . $userlink . '</h3>
	<p><strong>Browse:</strong></p>
	<ul>
	<li><strong><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=sets&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '">' . __('photosets','flickpress') . ' &raquo;</a></strong></li>
	<li><strong><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=tags&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '">' . __('tags','flickpress') . ' &raquo;</a></strong></li>
	<li><strong><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=recent&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '">' . __('recent','flickpress') . ' &raquo;</a></strong></li>
	<li><strong><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=faves&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '">' . __('favorites','flickpress') . ' &raquo;</a></strong></li>
	</ul>
	<p><strong>Search:</strong></p>
	<form name="flickpress_search" method="post" action="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php">
	<input type="hidden" name="userid" value="' . $_GET['id'] . '" />
	<input type="hidden" name="uname" value="' . $_GET['uname'] . '" />
   <input type="text" name="searchtext" value="" size="15" class="regular-text" /> <input type="submit" name="Submit" value="' . __('Find photos &raquo;','flickpress') . '" class="button" /></form>
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
	if ($user_info = $flick->people_getInfo($_GET['id'])) {
		$photos_url = $user_info['photosurl'];
	echo '<h3><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=users" class="current">' . __('Home','flickpress') . '</a> : ' . $userlink . ' : ' . '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=recent&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '">' . __('recent','flickpress') . "</a></h3>\n";
		echo "\n<p>\n";
		if ($photos = $flick->people_getPublicPhotos($_GET['id'],NULL,NULL,$per_page,$page)) {
			$num_photos = $photos['photos']['total'];
			if ($num_photos > 0) {
				$pages = ceil($num_photos/$per_page);
				if ($pages > 1) {
					echo "<div class='prevnext'>";
					if ((int)$page > 1) {
						echo '<div class="alignleft"><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=recent&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '&amp;page=' . ($page - 1) . '">' . __('&laquo; Newer','flickpress') . "</a></div>";
					}
					if ((int)$pages !== (int)$page) {
						echo '<div class="alignright"><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=recent&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '&amp;page=' . ($page + 1) . '">' . __('Older &raquo;','flickpress') . "</a></div>";
					}
					echo "</div>\n";
				}
				foreach ((array)$photos['photos']['photo'] as $photo) {
					$photourl = $flick->buildPhotoURL($photo, 'Square');
					$imgcode = '<img alt="' . htmlentities($photo['title']) . '" src="' . $photourl . '" width="75" height="75" />';
					echo '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=showphoto&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '&amp;photoid=' . $photo['id'] . '&amp;returnto=recent&amp;page=' . $page . '">' . $imgcode . '</a> ';
					unset($photourl,$imgcode,$flickrcode);
				}
				echo "</p>\n";
				if ($pages > 1) {
					echo "<p>\n";
					for ($i=1;$i<=$pages;$i++) {
						if ($i == $page) {
							echo $i . " \n";
						} else {
							echo '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=recent&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '&amp;page=' . $i . '">' . $i . "</a> \n";
						}
					}
					echo "\n</p>\n";
				}
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
			echo "\n</pre>\n</p>";
	}
	echo '</div>
</body>
</html>';
die();
}

// Display recent interesting photos
if ($_GET['action'] == 'interesting') {
	if (isset($_GET['page'])) {
		$page = $_GET['page'];
	} else {
		$page = 1;
	}
	if ($photos = $flick->interestingness_getList(NULL,NULL,$per_page,$page)) {
		echo '<h3><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=users" class="current">' . __('Home','flickpress') . '</a> : '  . '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=interesting&amp;page=' . $page . '">' . __('Interesting','flickpress') . "</a></h3>\n";
		echo "\n<p>\n";
		$num_photos = $photos['total'];
		$pages = ceil($num_photos/$per_page);
		if ($pages > 1) {
			echo "<div class='prevnext'>";
			if ((int)$page > 1) {
				echo '<div class="alignleft"><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=interesting&amp;page=' . ($page - 1) . '">' . __('&laquo; Newer','flickpress') . "</a></div>";
			}
			if ((int)$pages !== (int)$page) {
				echo '<div class="alignright"><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=interesting&amp;page=' . ($page + 1) . '">' . __('Older &raquo;','flickpress') . "</a></div>";
			}
			echo "</div>\n";
		}
		foreach ((array)$photos['photo'] as $photo) {
			$photourl = $flick->buildPhotoURL($photo, 'Square');
			$imgcode = '<img alt="' . htmlentities($photo['title']) . '" src="' . $photourl . '" width="75" height="75" />';
			echo '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=showphoto&amp;photoid=' . $photo['id'] . '&amp;returnto=interesting&amp;page=' . $page . '">' . $imgcode . '</a> ';
			unset($photourl,$imgcode,$flickrcode);
		}
		echo "</p>\n";
		echo "<p>\n";
		for ($i=1;$i<=$pages;$i++) {
			if ($i == $page) {
				echo $i . " \n";
			} else {
				echo '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=interesting&amp;page=' . $i . '">' . $i . "</a> \n";
			}
		}
		echo "\n</p>\n";
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

// Display a user's favorite photos
if ($_GET['action'] == 'faves') {
	if (isset($_GET['page'])) {
		$page = $_GET['page'];
	} else {
		$page = 1;
	}
	$user_info = $flick->people_getInfo($_GET['id']);
	$photos_url = $user_info['photosurl'];
	echo '<h3><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=users">' . __('Home','flickpress') . '</a> : ' . $userlink . ' : ' . '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=faves&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '">' . __('favorites','flickpress') . "</a></h3>\n";
	echo "<p>\n";
	if ($photos = $flick->favorites_getPublicList($_GET['id'],NULL,NULL,NULL,$per_page,$page)) {
		$num_photos = $photos['photos']['total'];
		$pages = ceil($num_photos/$per_page);
		if ($num_photos > 0) {
			if ($pages > 1) {
				echo "<div class='prevnext'>";
				if ((int)$page > 1) {
					echo '<div class="alignleft"><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=faves&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '&amp;page=' . ($page - 1) . '">' . __('&laquo; Newer','flickpress') . "</a></div>";
				}
				if ((int)$pages !== (int)$page) {
					echo '<div class="alignright"><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=faves&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '&amp;page=' . ($page + 1) . '">' . __('Older &raquo;','flickpress') . "</a></div>";
				}
				echo "</div>\n";
			}
			foreach ((array)$photos['photos']['photo'] as $photo) {
				$photourl = $flick->buildPhotoURL($photo, 'Square');
				$imgcode = '<img alt="' . htmlentities($photo['title']) . '" src="' . $photourl . '" width="75" height="75" />';
				echo '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=showphoto&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '&amp;photoid=' . $photo['id'] . '&amp;returnto=faves&amp;page=' . $page . '">' . $imgcode . '</a> ';
				unset($photourl,$imgcode,$flickrcode);
			}
			echo "</p>\n";
			if ($pages > 1) {
				echo "<p>\n";
				for ($i=1;$i<=$pages;$i++) {
					if ($i == $page) {
						echo $i . " \n";
					} else {
						echo '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=faves&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '&amp;page=' . $i . '">' . $i . "</a> \n";
					}
				}
				echo "</p>\n";
			}
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
		$addthis = 'licenses=' . $plus_licenses;
	} else {
		$addthis = 'userid=' . $userid . '&amp;uname=' . urlencode($uname);
	}
	if (isset($s_licenses)) {
		echo '<h3><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=users">' . __('Home','flickpress') . '</a> : ' . __('Commons search','flickpress') . ' : &laquo;' . $searchtext . '&raquo;</h3>';
	} else {
		echo '<h3><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=users">' . __('Home','flickpress') . '</a> : ' . '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=options&amp;type=user&amp;id=' . $userid . '&amp;uname=' . urlencode($uname) . '">' . $uname . '</a> : ' . __('search','flickpress') . ' : &laquo;' . $searchtext . "&raquo;</h3>\n";
	}
	if (((int)$photos['total'] > 0) && ((int)$photos['pages'] > 1)) {
		echo "<div class='prevnext'>";
		if ((int)$page > 1) {
			echo '<div class="alignleft"><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?searchtext=' . urlencode($searchtext) . '&amp;page=' . ($page - 1) . '&amp;' . $addthis . '">' . __('&laquo; Newer','flickpress') . "</a></div>";
		}
		if ((int)$photos['pages'] !== (int)$page) {
			echo '<div class="alignright"><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?searchtext=' . urlencode($searchtext) . '&amp;page=' . ($page + 1) . '&amp;' . $addthis . '">' . __('Older &raquo;','flickpress') . "</a></div>";
		}
		echo "</div>\n";
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
			$imgcode = '<img alt="' . htmlentities($photo['title']) . '" src="' . $photourl . '" width="75" height="75" />';
			echo '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=showphoto&amp;photoid=' . $photo['id'] . '&amp;page=' . $page . '&amp;insearch=' . urlencode($searchtext) . '&amp;' .$addthis . '">' . $imgcode . '</a> ';
			unset($photourl,$imgcode);
		}
	} else {
		echo '<strong>' . __('None found!','flickpress') . '</strong>';
	}
   echo "</p>\n";
	echo "<p>" . __('Search again:','flickpress') . '</p>
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
	echo '<input type="text" name="searchtext" value="" size="15" class="regular-text" /> <input type="submit" name="Submit" value="' . __('New search &raquo;','flickpress') . '" class="button" /></form>
	</div>
</body>
</html>';
die();
}

// display a user's photosets
if ($_GET['action'] == 'sets') {
	$photos_url = $flick->urls_getUserPhotos($_GET['id']);
	$setslink = '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=sets&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '">' . __('photosets','flickpress') . '</a>';
	if (isset($_GET['showset'])) {
		if (isset($_GET['page'])) {
			$page = $_GET['page'];
		} else {
			$page = 1;
		}
		if (($setphotos = $flick->photosets_getPhotos($photoset_id = $_GET['showset'], $extras = NULL, $privacy_filter = NULL, $per_page = $per_page, $page = $page)) && ($setinfo = $flick->photosets_getInfo($photoset_id = $_GET['showset']))) {
			echo '<h3><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=users">' . __('Home','flickpress') . '</a> : ' . $userlink . ' : ' . $setslink . ' : ' . htmlentities($setinfo['title']) . "</h3>\n";
			if ((int)$setphotos['photoset']['pages'] > 1) {
				echo "<div class='prevnext'>";
				if ((int)$page > 1) {
					echo '<div class="alignleft"><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=sets&amp;showset=' . $_GET['showset'] . '&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '&amp;page=' . ($page - 1) . '">' . __('&laquo; Newer','flickpress') . "</a></div>";
				}
				if ((int)$setphotos['photoset']['pages'] !== (int)$page) {
					echo '<div class="alignright"><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=sets&amp;showset=' . $_GET['showset'] . '&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '&amp;page=' . ($page + 1) . '">' . __('Older &raquo;','flickpress') . "</a></div>";
				}
				echo "</div>\n";
			}
			echo '<p>';
			foreach ((array)$setphotos['photoset']['photo'] as $photo) {
				$photourl = $flick->buildPhotoURL($photo, "Square");
				$imgcode = '<img alt="' . htmlentities($photo['title']) . '" src="' . $photourl . '" width="75" height="75" />';
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
			echo '<p>' . __('Failed to get data from Flickr. Possible error message:','flickpress') . "<br />\n<pre>\n";
			print_r($setphotos);
			echo "\n</pre>\n</p>";
		}
	} else {
		echo '<h3><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=users">' . __('Home','flickpress') . '</a> : ' . $userlink . __(' : photosets','flickpress'). "</h3>\n";
		if ($photosets = $flick->photosets_getList($_GET['id'])) {
			echo "\n<ul>\n";
			foreach ((array)$photosets['photoset'] as $photoset) {
				echo '<li><strong><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=sets&amp;showset=' . $photoset['id'] . '&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '">' . htmlentities($photoset['title']) . ' (' . $photoset['photos'] . ')</a></strong></li>';
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

// display a user's tags or the photos for one of the tags
if ($_GET['action'] == 'tags') {
	$photos_url = $flick->urls_getUserPhotos($_GET['id']);
	$tagslink = '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=tags&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '">' . __('tags','flickpress') . '</a>';
	// show the photos for a tag
	if (isset($_GET['showtag'])) {
	echo '<h3><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=users">' . __('Home','flickpress') . '</a> : ' . '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=users">' . __('Home','flickpress') . '</a> : ' . $userlink . ' : ' . $tagslink . ' : ' . $_GET['showtag'] . "</h3>\n";
		if (isset($_GET['page'])) {
			$page = $_GET['page'];
		} else {
			$page = 1;
		}
		if ($tagphotos = $flick->photos_search(array("user_id" => $_GET['id'], "tags" => $_GET['showtag'], "per_page" => $per_page, "page" => $page))) {
			$num_photos = $tagphotos['total'];
			$pages = ceil($num_photos/$per_page);
			if ((int)$pages > 1) {
				echo "<div class='prevnext'>";
				if ((int)$pages > 1 && $page > 1) {
					echo '<div class="alignleft"><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=tags&amp;showtag=' . $_GET['showtag'] . '&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '&amp;page=' . ($page - 1) . '">' . __('&laquo; Newer','flickpress') . "</a></div>";
				}
				if ((int)$pages !== (int)$page) {
					echo '<div class="alignright"><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=tags&amp;showtag=' . $_GET['showtag'] . '&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '&amp;page=' . ($page + 1) . '">' . __('Older &raquo;','flickpress') . "</a></div>";
				}
				echo "</div>\n";
			}
			echo "<p>";
			foreach ((array)$tagphotos['photo'] as $tagphoto) {
				$photourl = $flick->buildPhotoURL($tagphoto, "Square");
				$imgcode = '<img alt="' . htmlentities($tagphoto['title']) . '" src="' . $photourl . '" width="75" height="75" />';
				echo '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=showphoto&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '&amp;photoid=' . $tagphoto['id'] . '&amp;returnto=tags&amp;showtag=' . $_GET['showtag'] . '&amp;page=' . $page . '">' . $imgcode . '</a> ';
				unset($photourl,$imgcode,$flickrcode);
			}
			echo "</p>\n";
			if ($pages > 1) {
				for ($i=1;$i<=$pages;$i++) {
					if ($i == 1) {
						echo '<p>';
					}
					if ($i == $page) {
						echo $i . ' ';
					} else {
						echo '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=tags&amp;showtag=' . $_GET['showtag'] . '&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '&amp;page=' . $i . '">' . $i . "</a> ";
					}
					if ($i == $pages) {
						echo "</p>\n";
					}
				}
			}
		} else {
			echo '<p>' . __('Failed to get data from Flickr. Possible error message:','flickpress') . "<br />\n<pre>\n";
			print_r($tagphotos);
			echo "\n</pre>\n</p>";
		}
	// list the user's tags
	} else {
		echo '<h3><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=users">' . __('Home','flickpress') . '</a> : ' . $userlink . ' : ' . __('tags','flickpress') . "</h3>\n";
		if ($tags = $flick->tags_getListUser($_GET['id'])) {
        	echo "\n<ul>\n";
        foreach ((array)$tags as $tag) {
				echo '<li><strong><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=tags&amp;showtag=' . $tag . '&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '">' . htmlentities($tag) . '</a></strong></li>';
			}
			echo "\n</ul>\n";
		} else {
			echo '<p>' . __('No tags found or failed to get data from Flickr. Possible error message:','flickpress') . "<br />\n<pre>\n";
			print_r($tags);
			echo "\n</pre>\n</p>";
		}
	}
        echo '</div>
</body>
</html>';
die();
}

// Display photo info and insert stuff
if ($_GET['action'] == 'showphoto') {
	if (isset($_GET['page'])) {
		$page = $_GET['page'];
	} else {
		$page = 1;
	}
	if (isset($_GET['licenses'])) {
		$plus_licenses = str_replace(' ','+',$_GET['licenses']);
	}
	$photoinfo = $flick->photos_getInfo($_GET['photoid'],NULL);
	if ($photoexif = $flick->photos_getExif($_GET['photoid'],NULL)) {
		foreach ((array)$photoexif['exif'] as $e) {
			if (empty($fpexif[$e['label']])) {
				$fpexif[$e['label']] = (empty($e['clean']) ? $e['raw'] : $e['clean']);
			}
		}
		if (!empty($fpexif['Model']) | !empty($fpexif['Shutterspeed']) | !empty($fpexif['Aperture']) | !empty($fpexif['Focal Length']) | !empty($fpexif['Exposure Bias']) | !empty($fpexif['ISO Speed']) | !empty($fpexif['Flash'])) {
			$exiftable = '<table class="flickr-exif"><tbody>';
			if (!empty($fpexif['Model']))
				$exiftable .= "\n<tr><td>" . __('Camera:','flickpress') . '</td><td>' . $fpexif['Model'] . '</td></tr>';
			if (!empty($fpexif['ShutterSpeed']))
				$exiftable .= "\n<tr><td>" . __('Exposure:','flickpress') . '</td><td>' . $fpexif['ShutterSpeed'] . '</td></tr>';
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
			$exiftable .= "\n</tbody></table>";
		} else {
			$exiftable = '<p>' . __('No EXIF information available.','flickpress') . "</p>\n";
		}
	} else {
			$exiftable = '<p>' . __('No EXIF information available.','flickpress') . "</p>\n";
	}
   if (!empty($photoinfo['description'])) {
      $description = $photoinfo['description'];
   } else {
      $description = __('No photo description available.','flickpress');
   }
	if (!empty($photoinfo['title'])) {
		$title = $photoinfo['title'];
	} else {
		$title = __('(untitled)','flickpress');
	}
	$sizes = $flick->photos_getSizes($_GET['photoid']);
	$caption = '<a href="' . $photoinfo['urls']['url']['0']['_content'] . '">' . $title . '</a> ' . __('by','flickpress') . ' <a href="' . $flick->urls_GetUserPhotos($photoinfo['owner']['nsid']) . '">' . $photoinfo['owner']['username'] . '</a>';
	echo '<script type="text/javascript">' . "
	//<![CDATA[
	var imgdescription = '" . rawurlencode($description) . "';
	var imgcaption = '" . rawurlencode($caption) . "';
	var imgexif = '" . rawurlencode($exiftable) . "';
	//]]>
	</script>";
	if (isset($_GET['insearch'])) {
		if (isset($plus_licenses)) {
			$linkback = '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?searchtext=' . urlencode($_GET['insearch']) . '&amp;page=' . $page . '&amp;licenses=' . $plus_licenses . '">';
			$bottomlink = '<p>' . $linkback . __('Return to page ','flickpress') . $page . __(' of photos from your search for &laquo;','flickpress') . $_GET['insearch'] . "&raquo;</a></p>\n";
			$toplink = '<h3><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=users">' . __('Home','flickpress') . '</a> : ' . __('Commons search','flickpress') . ' : ' . $linkback . '&laquo;' . $_GET['insearch'] . '&raquo;</a> : ' . $caption . "</h3>\n";
		} else {
			$linkback = '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?searchtext=' . urlencode($_GET['insearch']) . '&amp;page=' . $page . '&amp;userid=' . $_GET['userid'] . '&amp;uname=' . $_GET['uname'] . '">';
			$bottomlink = '<p>' . $linkback . __('Return to page ','flickpress') . $page . __(' of photos from your search for &laquo;','flickpress') . $_GET['insearch'] . "&raquo;</a></p>\n";
			$toplink = '<h3><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=users">' . __('Home','flickpress') . '</a> : ' . '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=options&amp;type=user&amp;id=' . $_GET['userid'] . '&amp;uname=' . urlencode($_GET['uname']) . '">' . $_GET['uname'] . '</a> : ' . __('search','flickpress') . ' : ' . $linkback . '&laquo;' . $_GET['insearch'] . '&raquo;</a> : ' . $title . "</h3>\n";
		}
	} else {
		$beglinkback = '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=' . $_GET['returnto'] . '&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '&amp;page=' . $page;
		$begtoplink = '<h3><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=users">' . __('Home','flickpress') . '</a> : ' . '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=options&amp;type=user&amp;id=' . $_GET['userid'] . '&amp;uname=' . urlencode($_GET['uname']) . '">' . $_GET['uname'] . '</a> : ' . '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '&amp;page=' . $page;
		$endtoplink = '</a> : ' . $title . "</h3>\n";
		if ($_GET['returnto'] == 'sets') {
			$setinfo = $flick->photosets_getInfo($_GET['showset']);
			$linkback = $beglinkback . '&amp;showset=' . $_GET['showset'] . '">';
			$bottomlink = '<p>' . $linkback . __('Return to page ','flickpress') . $page . __(' of photos from the (','flickpress') . $setinfo['title'] . __(') photoset.','flickpress') . "</a></p>\n";
			$toplink = $begtoplink . '&amp;action=sets">' . __('photosets','flickpress') . '</a> : ' . $linkback . $setinfo['title'] . $endtoplink;
		} elseif ($_GET['returnto'] == 'tags') {
			$linkback = $beglinkback . '&amp;showtag=' . $_GET['showtag'] . '">';
			$bottomlink = '<p>' . $linkback . __('Return to page ','flickpress') . $page . __(' of photos from the (','flickpress') . $_GET['showtag'] . __(') tag.','flickpress') . "</a></p>\n";
			$toplink = $begtoplink . '">' . __('tags','flickpress') . '</a> : ' . $linkback . $_GET['showtag'] . $endtoplink;
		} elseif ($_GET['returnto'] == 'faves') {
			$linkback = $beglinkback . '">';
			$bottomlink = '<p>' . $linkback . __('Return to page ','flickpress') . $page . __(' of favorite photos.','flickpress') . "</a></p>\n";
			$toplink = $begtoplink . '&amp;action=faves&amp;page=' . $_GET['page'] . '">' . __('favorites','flickpress') . $endtoplink;
		} elseif ($_GET['returnto'] == 'interesting') {
			$linkback = '<a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=' . $_GET['returnto'] . '&amp;page=' . $_GET['page'] . '">';
			$bottomlink = '<p>' . $linkback . __('Return to page ','flickpress') . $page . __(' of interesting photos.','flickpress') . "</a></p>\n";
			$toplink = '<h3><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=users">' . __('Home','flickpress') . '</a> : ' . $linkback . __('Interesting','flickpress') . $endtoplink;
		} elseif($_GET['returnto'] == 'recent') {
			$linkback = $beglinkback . '">';
			$context = $flick->photos_getContext($_GET['photoid'],NULL);
			if ($context['nextphoto']['id'] !== 0)
				$prevnext = '<div class="alignleft"><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=showphoto&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '&amp;photoid=' . $context['nextphoto']['id'] . '&amp;returnto=recent&amp;page=' . $page . '">' . __('&laquo; Newer','flickpress') . '</a></div>';
			if ($context['prevphoto']['id'] !== 0)
				$prevnext .= '<div class="alignright"><a href="' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=showphoto&amp;type=user&amp;id=' . $_GET['id'] . '&amp;uname=' . $_GET['uname'] . '&amp;photoid=' . $context['prevphoto']['id'] . '&amp;returnto=recent&amp;page=' . $page . '">' . __('Older &raquo;','flickpress') . '</a></div>';
			$bottomlink = '<p>' . $linkback . __('Return to page ','flickpress') . $page . __(' of recent photos.','flickpress') . "</a></p>\n";
			$toplink = $begtoplink . '&amp;action=recent&amp;page=' . $page . '">' . __('recent','flickpress') . $endtoplink . "\n<div class='prevnext'>" . $prevnext . "</div>\n";
		}
	}
	echo $toplink;
	foreach ($sizes as $size) {
		$fcodes[$size['label']] = '<img alt="' . $title . '" src="' . $size['source'] . '" title="' . $title . '" width="' . $size['width'] . '" height="' . $size['height'] . '" />';
		$flinked[$size['label']] = '<a href="' . $photoinfo['urls']['url']['0']['_content'] . '">' . $fcodes[$size['label']] . '</a>';
		$divwidth = $size['width'] + 10;
		$finserts[$size['label']] = '<strong><a class="fpinserting" href="#" onclick="insertcode(\'' . js_escape($flinked[$size['label']]) . '\',document.getElementById (\'add_caption\').checked,document.getElementById (\'add_desc\').checked,document.getElementById (\'add_exif\').checked,' . $divwidth . '); return false;">' . $size['label'] . "</a></strong> (" . $size['width'] . 'x' . $size['height'] . ")<br />\n";
	}
	if (isset($fcodes['Small'])) {
		$popcode = $fcodes['Small'];
	} elseif (isset($fcodes['Thumbnail'])) {
		$popcode = $fcodes['Thumbnail'];
	} else {
		$popcode = __('Odd, there is no image to display...','flickpress');
	}
	echo '<div id="flickleft"><p>' . $popcode . "</p>\n";
	echo '<p><strong>' . __('Description:','flickpress') . '</strong><br />';
	echo $description . "</p>\n";
	echo '<p><strong>' . __('EXIF:','flickpress') . '</strong></p>' . $exiftable . "\n";
	$licenses = $flick->photos_licenses_getInfo();
	echo '<p><strong>' . __('License:','flickpress') . '</strong><br />';
	foreach ($licenses as $license) {
		if ($license['id'] == $photoinfo['license']) {
			if ($license['url']) {
				echo '<a href="' . $license['url'] . '">' . $license['name'] . '</a><br />';
			} else {
				echo $license['name'] . '<br />';
			}
		}
	}
	if (empty($flickpress_options['captions']) || ($flickpress_options['captions'] == 'yes')) {
		$checked = ' checked="checked"';
	} else {
		$checked = '';
	}
	echo '</p></div>
	<div id="flickright">
	<p><input name="add_caption" id="add_caption" value="1" type="checkbox"' . $checked . ' /> <label for="add_caption">' . __('Caption the inserted image with the photo title and owner (to comply with licenses that require attribution).','flickpress') . '</label></p>';
	echo '<p><input name="add_desc" id="add_desc" value="1" type="checkbox" /> <label for="add_desc">' . __('Add the description too.','flickpress') . '</label></p>';
	echo '<p><input name="add_exif" id="add_exif" value="1" type="checkbox" /> <label for="add_exif">' . __('Add a table of EXIF info too.','flickpress') . '</label></p>
	<p>' . __('<strong>Click a size to add it to your post:</strong>','flickpress') . '</p>
	<p>';
	foreach ($finserts as $finsert) {
		echo $finsert;
	}
	echo "<span class='fpinserted'>" . __('Inserted it!','flickpress') . "</span></p></div>\n<div id='flickfoot'><p>" . __('Please be sure that your use is compatible with the photo license.','flickpress') . '</p>';

	echo $bottomlink;
	echo '</div></div>
</body>
</html>';
die();
}

?>
