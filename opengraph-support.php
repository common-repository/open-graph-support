<?php
/*
Plugin Name: Open Graph Support
Description: Makes your content appealing in Facebook and Google+ by enabling Open Graph meta data. Possibility to use Facebook App ID.
Version: 1.0.0
Author: Moki-Moki Ios
Author URI: http://mokimoki.net/
Text Domain: opengraph-support
License: GPL3
*/

/*
Copyright (C) 2017 Moki-Moki Ios http://mokimoki.net/

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * Open Graph Support
 * Makes your content appealing in Facebook and Google+.
 *
 * @version 1.0.0
 */

if (!defined('ABSPATH')) return;

add_action('init', array(OpengraphSupport::get_instance(), 'initialize'));
add_action('admin_notices', array(OpengraphSupport::get_instance(), 'plugin_activation_notice'));
register_activation_hook(__FILE__, array(OpengraphSupport::get_instance(), 'setup_plugin_on_activation')); 

class OpengraphSupport {
	
	const PLUGIN_NAME = "Open Graph Support";
	const REMOVE_HEADERS_REGEXP = '#(<h([1-6])[^>]*>)\s?(.*)?\s?(<\/h\2>)#';
	const META_DESCRIPTION_MAX_LENGTH = 160;
	
	private static $instance;
	private function __construct() {}	
	
	public static function get_instance() {
		if (!isset(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	public function initialize() {
		add_action('admin_init', array($this, 'initialize_settings'));		
		add_action('wp_head', array($this, 'print_meta_opengraph'));		
	}
	
	public function initialize_settings() {
		register_setting('reading', 'opengraph_facebook_app_id');

		add_settings_section( 
			'opengraph-support', 
			'Opengraph Support', 
			null, 
			'reading'
		);
		
		add_settings_field(
			'opengraph_facebook_app_id',
			'Facebook App ID',
			array($this, 'print_option_facebook_app_id'),
			'reading',
			'opengraph-support'
		);
	}
	
	public function setup_plugin_on_activation() {		
		set_transient('opengraph_activation_notice', TRUE, 5);
		add_action('admin_notices', array($this, 'plugin_activation_notice'));
	}
	
	public function plugin_activation_notice() {
		if (get_transient('opengraph_activation_notice')) {
			echo '<div class="notice updated"><p><strong>Opengraph Support is now up and running! Facebook App ID can be set at <a href="options-reading.php">Settings -> Reading</a>.</strong></p></div>';	
		}		
	}
	
	public function print_meta_opengraph() {
		$url = get_permalink();
		$title = get_the_title();
		$description = $this->get_meta_description();
		$image_url = $this->get_post_image_url();
		$facebook_app_id = get_option('opengraph_facebook_app_id', FALSE);
		
		echo '<!-- Open Graph Support -->'.PHP_EOL;
		echo '<meta property="og:url" content="'.$url.'" />'.PHP_EOL;
		echo '<meta property="og:title" content="'.$title.'" />'.PHP_EOL;
		
		if (!empty($description)) {
			echo '<meta property="og:description" content="'.$description.'"/>'.PHP_EOL;
		}
		
		if (!empty($image_url)) {
			echo '<meta property="og:image" content="'.$image_url.'"/>'.PHP_EOL;
		}
		
		if (!empty($facebook_app_id)) {
			echo '<meta property="fb:app_id" content="'.$facebook_app_id.'"/>'.PHP_EOL;
		}
		
		echo '<!-- End of Open Graph Support -->'.PHP_EOL;
	}
		
	private function get_meta_description() {	
		global $post;
		$description = get_post_field('post_content', $post->ID);
		$description = apply_filters('the_content', $description);
		$description = preg_replace(self::REMOVE_HEADERS_REGEXP, '', $description);
		$description = wp_strip_all_tags($description, TRUE);
		
		if (strlen($description) > self::META_DESCRIPTION_MAX_LENGTH) {
			$description = substr($description, 0, self::META_DESCRIPTION_MAX_LENGTH) . '...';
		}
		
		return $description;
	}
	
	private function get_post_image_url() {
		if (has_post_thumbnail()) {
			$image_id = get_post_thumbnail_id();
			$image_url = wp_get_attachment_image_src($image_id, 'full');
			return $image_url[0];
		}
		
		global $post;
		$found = preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $post->post_content, $matches);
		
		if ($found === 1) {
			return $matches[1][0];
		}
		
		return NULL;
	}
	
	public function print_option_facebook_app_id() {
		$facebook_app_id = get_option('opengraph_facebook_app_id');
		echo '<input type="text" name="opengraph_facebook_app_id" value="'.$facebook_app_id.'"/>';		
	}
}
