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
		'wp_edgecast_setting_account_num',	// id
		'Account Number',			// title
		'wp_edgecast_setting_account_num_fn',	// callback
		'wp_edgecast', 				// page
		'wp_edgecast_settings_section'		// section
	);

	add_settings_field(
		'wp_edgecast_setting_api_token',	// id
		'API Token',				// title
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

	add_settings_field(
		'wp_edgecast_setting_enabled',		// id
		'Enable the Plugin',			// title
		'wp_edgecast_setting_enabled_fn',	// callback
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

function wp_edgecast_setting_account_num_fn() {
	// output for the account number box
	$options = get_option('wp_edgecast_options');
	echo '<input name="wp_edgecast_options[account_num]" type="text" value="' . $options['account_num'] . '" size="50"/>';
	echo '&nbsp;';
	echo 'Please enter your EdgeCast account number from the top-right corner of the EdgeCast control panel.';
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

function wp_edgecast_setting_enabled_fn() {
	// output for the enabled checkbox
	$options = get_option('wp_edgecast_options');
	echo '<input name="wp_edgecast_options[enabled]" type="checkbox" value="1" ' . ($options['enabled'] ? 'checked="checked"' : '') . "/>";
	echo '&nbsp;';
	echo 'Enable the plugin for actual use. Please verify all the above data before checking this box.';
}

function wp_edgecast_page_fn() {
	// header for the options page
	$options = get_option('wp_edgecast_options');
	$blog_url = get_bloginfo('url');

	if ( isset( $_POST['wp_edgecast_hidden'] ) && $_POST['wp_edgecast_hidden'] == 'Y' && $options['enabled'] ) {
		// Purge that cache!
		$data = array(
			'MediaType' => $options['media_type'],
			'MediaPath' => wp_edgecast_url_builder( $blog_url, $options['url'], '' ),
		);

		$json_data = json_encode( $data );
		// write a file for the PUT
		$tmpfile = tmpfile();
		fwrite( $tmpfile, $json_data );
		fseek( $tmpfile, 0 );

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'http://api.edgecast.com/v2/mcc/customers/' . $options['account_num'] . '/edge/purge');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_PUT, true);
		curl_setopt($ch, CURLOPT_INFILE, $tmpfile);
		curl_setopt($ch, CURLOPT_INFILESIZE, strlen( $json_data ));
		curl_setopt($ch, CURLOPT_VERBOSE, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, 
			array(
				'Authorization: TOK:'. $options['api_token'],
				'Accept: application/json',
				'Content-Type: application/json'
			) 
		);

		$result = curl_exec( $ch );
		curl_close( $ch );
	}
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
	<div class="wrap">
		<div class="icon32" id="icon-options-general"><br /></div>
		<h2>Purge Cache</h2>
		<p>This will send a purge request to EdgeCast for the entirety of your site.</p>
		<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
		<input type='hidden' name='wp_edgecast_hidden' value='Y'/>
		<p class='submit'>
			<input class='button-primary' type='submit' value='Purge Cache'/>
		</p>
		</form>
	</div>
<?php	
}

function wp_edgecast_options_validate($input) {

	// sanitize
	if ( $input['url'] ) {
		if ( $input['url'] == '' ) {
			$input['url'] = false;
            $input['enabed'] = false;
		} else {
			// make sure it starts with http
			if ( ! preg_match('/^http:\/\//',  $input['url']) ) {
				$input['url'] = 'http://' . $input['url'];
			}

			// strip trailing slash
			$input['url'] = rtrim( $input['url'], '/' );
		}
	} else {
		$input['url'] = false;
        $input['enabled'] = false;
	}

	if ( ! $input['api_token'] || $input['api_token'] == '' ) {
		$input['api_token'] = false;
        $input['enabled'] = false;
	}
	
	if ( ! $input['account_num'] || $input['account_num'] == '' ) {
		$input['account_num'] = false;
        $input['enabled'] = false;
	}

	if ( ! $input['media_type'] ) {
		$input['media_type'] = false;
        $input['enabled'] = false;
	}

	return $input;
}

function wp_edgecast_publish_post($post_id, $post) {
	global $table_prefix, $wpdb;

	/* only if we're publishing a post */
	if ($post->post_status != 'publish') {
		return;
	}

	/* make sure we're all configured */
	$options = get_option('wp_edgecast_options');

	if ( ! $options['enabled'] )
		return;

	$urls = array(
		get_bloginfo('url', 'raw'),
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
			array_push( $urls, get_term_link( $term, $taxonomy['taxonomy'] ) );
		}
	}

	// store data
	$blog_url = get_bloginfo('url');

	foreach($urls as $url) {
		// prepare our data
		$data = array(
			'MediaType' => $options['media_type'],
			'MediaPath' => wp_edgecast_url_builder( $blog_url, $options['url'], $url )
		);
		$json_data = json_encode( $data );
		
		// write a file for the PUT
		$tmpfile = tmpfile();
		fwrite( $tmpfile, $json_data );
		fseek( $tmpfile, 0 );

		// setup our curl stuff
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'http://api.edgecast.com/v2/mcc/customers/' . $options['account_num'] . '/edge/purge');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_PUT, true);
		curl_setopt($ch, CURLOPT_INFILE, $tmpfile);
		curl_setopt($ch, CURLOPT_INFILESIZE, strlen( $json_data ));
		curl_setopt($ch, CURLOPT_VERBOSE, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, 
			array(
				'Authorization: TOK:'. $options['api_token'],
				'Accept: application/json',
				'Content-Type: application/json'
			) 
		);

		$result = curl_exec( $ch );
		curl_close( $ch );
	}
}

function wp_edgecast_comment_post( $comment_id, $approval_status ) {
	if ( $approval_status != 1 ) {
		return;
	}

	$comment = get_comment( $comment_id );
	wp_edgecast_publish_post( $comment->comment_post_ID, get_post( $comment->comment_post_ID ) );
}

function wp_edgecast_url_builder( $wp_url, $edge_url, $my_url ) {
	// first, strip trailing slash
	$wp_url		= rtrim( $wp_url, '/' );
	$edge_url	= rtrim( $edge_url, '/' );
	$my_url 	= rtrim( $my_url, '/' );

	if ( $my_url == '' ) {
		$return = $edge_url;
	} else {
		$return = str_replace( $wp_url, $edge_url, $my_url );
	}
	$return .= '/*';

	return $return;
}

?>
