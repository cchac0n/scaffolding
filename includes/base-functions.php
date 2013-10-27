<?php
/*
Author: Hall Internet Marketing
URL: https://github.com/hallme/scaffolding

All stock functions used on every scaffolding site live here.
Custom functions go in functions.php to facilitate future updates if necessary.
*/

/******************************************
TABLE OF CONTENTS

1. Initiating Scaffolding
2. Cleaning Up wp_head
3. Scripts & Enqueueing
4. Theme Support
5. Client UX Functions
6. Dashboard Widgets

******************************************/

/*********************
INITIATING SCAFFOLDING
*********************/
add_action('after_setup_theme','scaffolding_build', 16);

function scaffolding_build() {

	add_action('init', 'scaffolding_head_cleanup');										// launching operation cleanup
	add_filter('the_generator', 'scaffolding_rss_version');								// remove WP version from RSS
	add_filter( 'wp_head', 'scaffolding_remove_wp_widget_recent_comments_style', 1 );	// remove pesky injected css for recent comments widget
	add_action('wp_head', 'scaffolding_remove_recent_comments_style', 1);				// clean up comment styles in the head
	add_filter('gallery_style', 'scaffolding_gallery_style');							// clean up gallery output in wp
	add_action('wp_enqueue_scripts', 'scaffolding_scripts_and_styles', 999);			// enqueue base scripts and styles

	scaffolding_theme_support();														// launching this stuff after theme setup
	add_action( 'widgets_init', 'scaffolding_register_sidebars' );						// adding sidebars to Wordpress (these are created in functions.php)
	add_filter( 'get_search_form', 'scaffolding_wpsearch' ); 							// adding the scaffolding search form (created in functions.php)
	add_filter('the_content', 'scaffolding_filter_ptags_on_images'); 					// cleaning up random code around images
	add_filter('excerpt_more', 'scaffolding_excerpt_more');								// cleaning up excerpt
}

/*********************
CLEANING UP WP_HEAD
*********************/

function scaffolding_head_cleanup() {
	
	// remove_action( 'wp_head', 'feed_links_extra', 3 );						// category feeds
	// remove_action( 'wp_head', 'feed_links', 2 );								// post and comment feeds
	remove_action( 'wp_head', 'rsd_link' );										// EditURI link
	remove_action( 'wp_head', 'wlwmanifest_link' );								// windows live writer
	remove_action( 'wp_head', 'index_rel_link' );								// index link
	remove_action( 'wp_head', 'parent_post_rel_link', 10, 0 );					// previous link
	remove_action( 'wp_head', 'start_post_rel_link', 10, 0 );					// start link
	remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0 );		// links for adjacent posts
	remove_action( 'wp_head', 'wp_generator' );									// WP version
	add_filter( 'style_loader_src', 'scaffolding_remove_wp_ver_css_js', 9999 );	// remove WP version from css	
	add_filter( 'script_loader_src', 'scaffolding_remove_wp_ver_css_js', 9999 );// remove WP version from scripts
}

// remove WP version from RSS
function scaffolding_rss_version() { return ''; }

// remove WP version from scripts
function scaffolding_remove_wp_ver_css_js( $src ) {
	if ( strpos( $src, 'ver=' ) )
		$src = remove_query_arg( 'ver', $src );
	return $src;
}

// remove injected CSS for recent comments widget
function scaffolding_remove_wp_widget_recent_comments_style() {
	if ( has_filter('wp_head', 'wp_widget_recent_comments_style') ) {
		remove_filter('wp_head', 'wp_widget_recent_comments_style' );
	}
}

// remove injected CSS from recent comments widget
function scaffolding_remove_recent_comments_style() {
	global $wp_widget_factory;
	if (isset($wp_widget_factory->widgets['WP_Widget_Recent_Comments'])) {
		remove_action('wp_head', array($wp_widget_factory->widgets['WP_Widget_Recent_Comments'], 'recent_comments_style'));
	}
}

// remove injected CSS from gallery
function scaffolding_gallery_style($css) {
	return preg_replace("!<style type='text/css'>(.*?)</style>!s", '', $css);
}

/*********************
SCRIPTS & ENQUEUEING
*********************/
function scaffolding_scripts_and_styles() {
	global $wp_styles; // call global $wp_styles variable to add conditional wrapper around ie stylesheet the WordPress way
	if (!is_admin()) {

		// jQuery loaded from cdnjs
		wp_register_script( 'scaffolding-jquery', 'http://cdnjs.cloudflare.com/ajax/libs/jquery/2.0.3/jquery.min.js', array(), '', false );

		// modernizr (without media query polyfill)
		wp_register_script( 'scaffolding-modernizr', 'http://cdnjs.cloudflare.com/ajax/libs/modernizr/2.6.2/modernizr.min.js', array(), '', false );

		// register main stylesheet
		wp_register_style( 'scaffolding-stylesheet', get_stylesheet_directory_uri() . '/css/style.css', array(), '', 'all' );

		// ie-only style sheet
		wp_register_style( 'scaffolding-ie-only', get_stylesheet_directory_uri() . '/css/ie.css', array(), '' );

		// comment reply script for threaded comments
		if ( is_singular() AND comments_open() AND (get_option('thread_comments') == 1)) {
			wp_enqueue_script( 'comment-reply' );
		}

		//adding scripts file in the footer
		wp_register_script( 'scaffolding-js', get_stylesheet_directory_uri() . '/js/scripts.js', array( 'jquery' ), '', true );

		// enqueue styles and scripts
		wp_enqueue_script( 'scaffolding-modernizr' );
		wp_enqueue_style( 'scaffolding-stylesheet' );
		wp_enqueue_style('scaffolding-ie-only');

		$wp_styles->add_data( 'scaffolding-ie-only', 'conditional', 'lt IE 9' ); // add conditional wrapper around ie stylesheet

		wp_enqueue_script( 'scaffolding-jquery' );
		wp_enqueue_script( 'scaffolding-js' );

	}
}

/*********************
THEME SUPPORT
*********************/

// Adding WP 3+ Functions & Theme Support
function scaffolding_theme_support() {

	add_theme_support('post-thumbnails');						// wp thumbnails (sizes handled in functions.php)

	set_post_thumbnail_size(125, 125, true);					// default thumb size

	/*  Feature Currently Disabled
	// wp custom background (thx to @bransonwerner for update)
	add_theme_support( 'custom-background',
		array(
		'default-image' => '',  // background image default
		'default-color' => '', // background color default (dont add the #)
		'wp-head-callback' => '_custom_background_cb',
		'admin-head-callback' => '',
		'admin-preview-callback' => ''
		)
	);
	*/


	add_theme_support('automatic-feed-links');					// rss thingy

	// to add header image support go here: http://themble.com/support/adding-header-background-image-support/
	//adding custome header suport
	add_theme_support( 'custom-header', array(
		'default-image'=> '%s/images/headers/default.jpg',
		'random-default'=> false,
		'width'=> 999,  // Make sure to set this
		'height'=> 262, // Make sure to set this
		'flex-height'=> false,
		'flex-width'=> false,
		'default-text-color'=> 'ffffff',
		'header-text'=> false,
		'uploads'=> true,
		'wp-head-callback'=> 'scaffolding_custom_headers_callback',
		'admin-head-callback'=> '',
		'admin-preview-callback'=> '',
		)
	);

/* Feature Currently Disabled
	// adding post format support
	add_theme_support( 'post-formats',
		array(
			'aside',			// title less blurb
			'gallery',			// gallery of images
			'link',			  	// quick link to other site
			'image',			// an image
			'quote',			// a quick quote
			'status',			// a Facebook like status update
			'video',			// video
			'audio',			// audio
			'chat'				// chat transcript
		)
	);
*/

	// wp menus
	add_theme_support( 'menus' );

	// registering wp3+ menus
	register_nav_menus(
		array(
			'main-nav' => __( 'Main Menu', 'scaffoldingtheme' ),	// main nav in header
			'footer-nav' => __( 'Footer Menu', 'scaffoldingtheme' ) // secondary nav in footer
		)
	);
} /* end scaffolding theme support */



/*********************
CLIENT UX FUNCTIONS
*********************/

function increase_editor_permissions(){
	$role = get_role('editor');
	$role->add_cap('gform_full_access'); // Gives editors access to Gravity Forms
	$role->add_cap('edit_theme_options'); // Gives editors access to widgets & menus
}
add_action('admin_init','increase_editor_permissions');

// Removes the Powered By WPEngine widget
wp_unregister_sidebar_widget( 'wpe_widget_powered_by' );

//Remove some of the admin bar links to keep from confusing client admins
function remove_admin_bar_links() {
	global $wp_admin_bar;
	$wp_admin_bar->remove_menu('wp-logo'); // Remove Wordpress Logo From Admin Bar
	$wp_admin_bar->remove_menu('wpseo-menu'); // Remove SEO from Admin Bar
}
add_action( 'wp_before_admin_bar_render', 'remove_admin_bar_links' );

// Custom Backend Footer
function scaffolding_custom_admin_footer() {
	echo '<span id="footer-thankyou">Developed by <a href="http://www.hallme.com/" target="_blank">Hall Internet Marketing</a></span>. Built using <a href="https://github.com/hallme/scaffolding" target="_blank">scaffolding</a> a fork of <a href="http://themble.com/bones" target="_blank">bones</a>.';
}
add_filter('admin_footer_text', 'scaffolding_custom_admin_footer');

// CUSTOM LOGIN PAGE
// calling your own login css so you can style it
function scaffolding_login_css() {
	/* I couldn't get wp_enqueue_style to work :( */
	echo '<link rel="stylesheet" href="' . get_stylesheet_directory_uri() . '/css/login.css">';
}

// changing the logo link from wordpress.org to your site
function scaffolding_login_url() {  return home_url(); }

// changing the alt text on the logo to show your site name
function scaffolding_login_title() { return get_option('blogname'); }

// calling it only on the login page
add_action('login_head', 'scaffolding_login_css');
add_filter('login_headerurl', 'scaffolding_login_url');
add_filter('login_headertitle', 'scaffolding_login_title');


//Add page title attribute to a tags
function wp_list_pages_filter($output) {
    $output = preg_replace('/<a(.*)href="([^"]*)"(.*)>(.*)<\/a>/','<a$1 title="$4" href="$2"$3>$4</a>',$output);
    return $output;
}
add_filter('wp_list_pages', 'wp_list_pages_filter');


/*********************
DASHBOARD WIDGETS
*********************/
// disable default dashboard widgets
function disable_default_dashboard_widgets() {
	//remove_meta_box('dashboard_right_now', 'dashboard', 'core');// Right Now Widget
	//remove_meta_box('dashboard_recent_comments', 'dashboard', 'core');// Comments Widget
	remove_meta_box('dashboard_incoming_links', 'dashboard', 'core');// Incoming Links Widget
	remove_meta_box('dashboard_plugins', 'dashboard', 'core');// Plugins Widget
	remove_meta_box('dashboard_quick_press', 'dashboard', 'core');// Quick Press Widget
	remove_meta_box('dashboard_recent_drafts', 'dashboard', 'core');// Recent Drafts Widget
	//remove_meta_box('dashboard_primary', 'dashboard', 'core');//1st blog feed
	remove_meta_box('dashboard_secondary', 'dashboard', 'core');//2nd blog feed
	// removing plugin dashboard boxes
	//remove_meta_box('yoast_db_widget', 'dashboard', 'normal');		 // Yoast's SEO Plugin Widget
}
// removing the dashboard widgets
add_action('admin_menu', 'disable_default_dashboard_widgets');