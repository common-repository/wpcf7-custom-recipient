<?php
/*
Plugin Name: Contact Form 7 Custom Recipient
Description: Define the contact form recipient for each page, post or custom post type individually
Author: Jordi Fontseca
Version: 0.1
Author URI: https://15robots.com/
*/

if ( !function_exists( 'add_action' ) ) {
	exit;
}

// change form recipient (if custom recipient exists)
function wpcf7_use_custom_recipient ($WPCF7_ContactForm) {
	$submission = WPCF7_Submission::get_instance();
	$container_post_id = $submission->get_meta('container_post_id');
	$custom_recipient = get_post_meta( $container_post_id, 'wpcf7_custom_recipient', true );
	if ( ! empty( $custom_recipient ) ) {
		$wpcf7_properties = $WPCF7_ContactForm->get_properties();
		$wpcf7_properties['mail']['recipient'] = $custom_recipient;
		$WPCF7_ContactForm->set_properties($wpcf7_properties);
	}
}
add_action("wpcf7_before_send_mail", "wpcf7_use_custom_recipient");

// add metabox to public post types
function add_wpcf7_custom_recipient_meta_box() {
	add_meta_box(
		'wpcf7_custom_recipient_meta_box',
		'CF7 Custom Recipient',
		'show_wpcf7_custom_recipient_meta_box',
		get_post_types(array('public' => true), 'names'),
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes', 'add_wpcf7_custom_recipient_meta_box' );

// show metabox content
function show_wpcf7_custom_recipient_meta_box() {
	global $post;
	$meta = get_post_meta( $post->ID, 'wpcf7_custom_recipient', true );
	echo '<input type="hidden" name="wpcf7_custom_recipient_meta_box_nonce" value="'.wp_create_nonce(basename(__FILE__)).'" />';
	echo '<label>Email:</label> ';
	echo '<input type="text" name="wpcf7_custom_recipient" id="wpcf7_custom_recipient" value="' . $meta . '">';
}

// save metabox
function save_wpcf7_custom_recipient_meta($post_id) {
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
		return $post_id;

	if (!isset($_POST['wpcf7_custom_recipient_meta_box_nonce']) || !wp_verify_nonce($_POST['wpcf7_custom_recipient_meta_box_nonce'], basename(__FILE__)))
		return $post_id;

	global $post;
	$post_type = get_post_type_object( $post->post_type );
	if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) ) {
		return $post_id;
	}

	$old = get_post_meta($post_id, "wpcf7_custom_recipient", true);
	$new = $_POST["wpcf7_custom_recipient"];
	if ($new && $new != $old) {
		update_post_meta($post_id, "wpcf7_custom_recipient", sanitize_email($new));
	} elseif ('' == $new && $old) {
		delete_post_meta($post_id, "wpcf7_custom_recipient", $old);
	}
}
add_action('save_post', 'save_wpcf7_custom_recipient_meta');
