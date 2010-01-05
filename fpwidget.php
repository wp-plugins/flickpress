<?php
/*
Plugin Name: flickpress widget
Plugin URI: http://familypress.net/flickpress/
Description: Adds a sidebar widget to dispay Flickr images.
Author: Isaac Wedin
Version: 1.4
Author URI: http://familypress.net/
*/

// Put functions into one big function we'll call at the plugins_loaded
// action. This ensures that all required plugin functions are defined.
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
