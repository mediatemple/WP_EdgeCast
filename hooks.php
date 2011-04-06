<?php
/**
 * @package WP_EdgeCast
 * @version 0.1
 */
/*
Plugin Name: WP_EdgeCast
Plugin URI: http://github.com/naydichev/WP_EdgeCast
Description: Plugin to purge EdgeCast cache for posts and comments.
Author: Julian Naydichev
Version: 0.1
Author URI: http://github.com/naydichev/
*/

require_once('functions.php');

add_action('admin_init', 'wp_edgecast_init');
add_action('admin_menu', 'wp_edgecast_settings_page');

add_action('wp_insert_post', 'wp_edgecast_publish_post', 12, 2);
add_action('comment_post', 'wp_edgecast_comment_post', 12, 2);
?>
