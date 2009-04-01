<?php
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

// Load the javascript for the popup tool. This runs when the button is clicked - it launches the Filez popup tool. If you're borrowing this code, you may or may not want to launch a file like this - if your tool is simple enough you could do the whole popup with some javascript instead.
function flickpress_popup_javascript() {
	echo '
<script type="text/javascript">
//<![CDATA[
function edflickpress() {
	tb_show("' . __('Flickr photos','flickpress') . '","' . get_bloginfo('wpurl') . '/wp-content/plugins/flickpress/popup.php?action=users&amp;TB_iframe=true",false);
}
//]]>
</script>
';
}

// Actions and filters to connect the plugin with WP.
add_action( 'edit_form_advanced', 'fp_add_quicktags' );
add_action( 'edit_page_form', 'fp_add_quicktags' );
add_action('admin_print_scripts', 'flickpress_popup_javascript');
add_filter( 'mce_external_plugins', 'flickpress_mce_external_plugins');
add_filter( 'mce_buttons', 'flickpress_mce_buttons');

?>
