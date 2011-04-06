<?php
/* This page has the functions from the hooks */

function wp_edgecast_init() {
	add_settings_section(
		'wp_edgecast_settings_section',		// id
		'WP EdgeCast Settings',			// title
		'wp_edgecast_setting_section_fn',	// callback
		'wp_edgecast'				// page
	);

	add_settings_field(
		'wp_edgecast_setting_api_token',	// id
		'API Key',				// title
		'wp_edgecast_setting_api_token_fn',	// callback
		'wp_edgecast',				// page
		'wp_edgecast_settings_section'		// section
	);

	add_settings_field(
		'wp_edgecast_setting_url',		// id
		'EdgeCast URL',				// title
		'wp_edgecast_setting_url_fn',		// callback
		'wp_edgecast',				// page
		'wp_edgecast_settings_section'		// section
	);

	add_settings_field(
		'wp_edgecast_setting_media_type',	// id
		'Object Type',				// title
		'wp_edgecast_setting_media_type_fn',	// callback
		'wp_edgecast',				// page
		'wp_edgecast_settings_section'		// section
	);

	register_setting(
		'wp_edgecast_options',  		// option_group
		'wp_edgecast_options',			// option_name
		'wp_edgecast_options_validate'		// sanitize_callback
	);

}

function wp_edgecast_settings_page() {
	// setup the page variables 
	add_menu_page(
		'WP EdgeCast Page',			// page_title
		'EdgeCast Options', 			// menu_title
		'administrator',			// capability
		'wp_edgecast', 				// menu_slug
		'wp_edgecast_page_fn'			// callback
	);
}

function wp_edgecast_setting_section_fn() {
	// output for the settings page header
	echo '<p>Here is where you can fill in all the appropriate settings for the EdgeCast API calls.</p>';
	echo '<p>The cache will be purged when a post is created or modified, and as comments are added.</p>';
}

function wp_edgecast_setting_api_token_fn() {
	// output for the api token text box
	$options = get_option('wp_edgecast_options');
	echo '<input name="wp_edgecast_options[api_token]" type="text" value="' . $options['api_token'] . '" size="50"/>';
	echo '&nbsp;';
	echo 'Please enter the API Token found on the \'My Settings\' page of your EdgeCast control panel.';
}

function wp_edgecast_setting_url_fn() {
	// output for the url text box
	$options = get_option('wp_edgecast_options');
	echo '<input name="wp_edgecast_options[url]" type="text" value="' . $options['url'] . '" size="50"/>';
	echo '&nbsp;';
	echo 'This is the URL that you are using for your CDN. This can be a CNAME, or a Large/Small object URL.';
}

function wp_edgecast_setting_media_type_fn() {
	// output for the media type drop down
	$options = get_option('wp_edgecast_options');
	$types = array( 
		// 2  => 'Flash Media Streaming',
		 3 => 'HTTP Large Object',
		 8 => 'HTTP Small Object', 
		// 14 => 'Application Delivery Network'
	);

	echo '<select name="wp_edgecast_options[media_type]">';
	foreach($types as $value => $name) {
		echo "<option value='$value'";
		if ($options['media_type'] == $value) {
			echo " selected='selected'";
		}
		echo ">$name</option>";
	}
	echo '</select>';
	echo '&nbsp;';
	echo 'Choose the type of media this is, as configured in your EdgeCast control panel.';
}

function wp_edgecast_page_fn() {
	// header for the options page
?>
	<div class="wrap">
		<div class="icon32" id="icon-options-general"><br /></div>
		<h2>WP EdgeCast Settings</h2>
		<form action="options.php" method="post">
		<?php settings_fields('wp_edgecast_options'); ?>
		<?php do_settings_sections('wp_edgecast'); ?>
		<p class='submit'>
			<input name="submit" type="submit" class="button-primary" value="<? esc_attr_e('Save Changes'); ?>" />
		</p>
		</form>
	</div>
<?php	
}

function wp_edgecast_options_validate($input) {
	
	return $input;
}

function wp_edgecast_publish_post($post_id, $post) {
	global $table_prefix, $wpdb;

	/* only if we're publishing a post */
	if ($post->post_status != 'publish') {
		return;
	}

	$urls = array(
		get_bloginfo('siteurl', 'raw'),
		get_bloginfo('home', 'raw'),
		get_permalink( $post_id )
	);

	$query = <<<END
		SELECT DISTINCT 
		  t.taxonomy 
		FROM 
		  `${table_prefix}term_taxonomy` AS t 
		INNER JOIN 
		  `${table_prefix}term_relationships` AS r 
		ON 
		  r.term_taxonomy_id = t.term_taxonomy_id 
		WHERE r.object_id = $post_id
END;

	$taxonomies = $wpdb->get_results( $query, 'ARRAY_A' );

	foreach( $taxonomies as $taxonomy ) {
		$terms = get_the_terms( $post_id, $taxonomy['taxonomy'] );
		foreach( $terms as $term ) {
			array_push( $urls, get_term_link( $term, $taxonomy ) );
		}
	}
	
	echo "<pre>";
	var_dump( $urls );
}

?>