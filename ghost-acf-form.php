<?php
/*
Plugin Name: Ghost ACF Forms
Description: Render one or more ACF forms on the front end of your website using shortcodes
Version: 1.0
Author: Ghost (Digital) Limited
Author URI: http://ghostlimited.com
Text Domain: ghost-acf-form
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Register Custom Post Type Form Entry
function GhostACFForm_create_formentry_cpt() {

	$labels = array(
		'name' => _x( 'Form Entries', 'Post Type General Name', 'ghost-acf-form' ),
		'singular_name' => _x( 'Form Entry', 'Post Type Singular Name', 'ghost-acf-form' ),
		'menu_name' => _x( 'Form Entries', 'Admin Menu text', 'ghost-acf-form' ),
		'name_admin_bar' => _x( 'Form Entry', 'Add New on Toolbar', 'ghost-acf-form' ),
		'archives' => __( 'Form Entry Archives', 'ghost-acf-form' ),
		'attributes' => __( 'Form Entry Attributes', 'ghost-acf-form' ),
		'parent_item_colon' => __( 'Parent Form Entry:', 'ghost-acf-form' ),
		'all_items' => __( 'All Form Entries', 'ghost-acf-form' ),
		'add_new_item' => __( 'Add New Form Entry', 'ghost-acf-form' ),
		'add_new' => __( 'Add New', 'ghost-acf-form' ),
		'new_item' => __( 'New Form Entry', 'ghost-acf-form' ),
		'edit_item' => __( 'Edit Form Entry', 'ghost-acf-form' ),
		'update_item' => __( 'Update Form Entry', 'ghost-acf-form' ),
		'view_item' => __( 'View Form Entry', 'ghost-acf-form' ),
		'view_items' => __( 'View Form Entries', 'ghost-acf-form' ),
		'search_items' => __( 'Search Form Entry', 'ghost-acf-form' ),
		'not_found' => __( 'Not found', 'ghost-acf-form' ),
		'not_found_in_trash' => __( 'Not found in Trash', 'ghost-acf-form' ),
		'featured_image' => __( 'Featured Image', 'ghost-acf-form' ),
		'set_featured_image' => __( 'Set featured image', 'ghost-acf-form' ),
		'remove_featured_image' => __( 'Remove featured image', 'ghost-acf-form' ),
		'use_featured_image' => __( 'Use as featured image', 'ghost-acf-form' ),
		'insert_into_item' => __( 'Insert into Form Entry', 'ghost-acf-form' ),
		'uploaded_to_this_item' => __( 'Uploaded to this Form Entry', 'ghost-acf-form' ),
		'items_list' => __( 'Form Entries list', 'ghost-acf-form' ),
		'items_list_navigation' => __( 'Form Entries list navigation', 'ghost-acf-form' ),
		'filter_items_list' => __( 'Filter Form Entries list', 'ghost-acf-form' ),
	);
	$args = array(
		'label' => __( 'Form Entry', 'ghost-acf-form' ),
		'description' => __( '', 'ghost-acf-form' ),
		'labels' => $labels,
		'menu_icon' => 'dashicons-list-view',
		'supports' => array('title', 'custom-fields'),
		'taxonomies' => array(),
		'public' => false,
		'show_ui' => true,
		'show_in_menu' => true,
		'menu_position' => 25,
		'show_in_admin_bar' => true,
		'show_in_nav_menus' => false,
		'can_export' => true,
		'has_archive' => false,
		'hierarchical' => false,
		'exclude_from_search' => true,
		'show_in_rest' => false,
		'publicly_queryable' => false,
		'capability_type' => 'post',
		'capabilities' => array(
			'create_posts' => 'do_not_allow', // false < WP 4.5, credit @Ewout
		),
		'map_meta_cap' => true, // Set to `false`, if users are not allowed to edit/delete existing post
		'rewrite' => false,
	);
	register_post_type( 'ghost-form-entry', $args );

}
add_action( 'init', 'GhostACFForm_create_formentry_cpt', 0 );

//Form entry save
function GhostACFForm_after_save_post( $post_id ) {

	// Bail early if not a ghost-form-entry post
	if( get_post_type($post_id) !== 'ghost-form-entry' ) {
		return $post_id;
	}

	//We don't need to run this if we're in the admin area
	if( is_admin() ) {
		return $post_id;
	}

	//Get form and title
	if(session_id() == '') {
		session_start();
	}
	$ghost_acf_form_key = $_SESSION['ghost_acf_form_key'];
	$form = acf_get_field_group($ghost_acf_form_key);
	$ghost_acf_form_name = $form['title'];

	// Get custom fields (field group exists for content_form)
	$first_name = get_field('first_name', $post_id);
	$last_name = get_field('last_name', $post_id);
	$name = $first_name . " " . $last_name;
	$email = get_field('email', $post_id);
	$message = get_field('message', $post_id);

	// Create/update post
	$data['ID']         = $post_id;
	$title              = $ghost_acf_form_name . ' | ' . $name . ' - ' . $email;
	$data['post_title'] = $title;
	$data['post_name']  = sanitize_title( $title );
	wp_update_post( $data );

	// Update post with the form ID it was created by
	update_post_meta( $post_id, 'form_key', $ghost_acf_form_key);

	//Get email to address as set by shortcode
	$ghost_acf_form_email_to = $_SESSION['ghost_acf_form_email_to'];

	//Check if emails are disabled by shortcode
	$email_disabled = $_SESSION['ghost_acf_form_email_disabled'];
	if($email_disabled == true || $email_disabled == "true" || $email_disabled == "1") {
		return;
	}

	//Put together email
	$headers = 'Content-Type: text/html; charset=UTF-8'. "\r\n";
	$headers .= 'From: ' . $name . ' <' . $email . '>' . "\r\n";
	$subject = "New submission for form '" . $ghost_acf_form_name . "' from " . $name . " <".$email.">";

	$body = file_get_contents(__DIR__ . '/email-templates/email-header.html');

	$body .= "<h2><strong>" . $ghost_acf_form_name . "</strong> - New submission</h2>\r\n";
	$body .= "A new submission has been entered for this form.<br /><br />\r\n";
	$body .= '<strong>From:</strong> ' . $name ."<br />\r\n";
	$body .= '<strong>Email:</strong> <a href="mailto:'.$email.'">' . $email . "</a><br /><br />\r\n";
	if(!empty($message)) {
		$body .= "<strong>Message:</strong><br />\r\n";
		$body .= nl2br(strip_tags(str_replace("<br />","\n", str_replace("<br>", "\n", $message))));
		$body .= "<br /><br />\n";
	}
	$body .= '<table class="btn btn-primary" border="0" cellspacing="0" cellpadding="0">
		<tbody>
			<tr>
				<td align="left">
					<table border="0" cellspacing="0" cellpadding="0">
						<tbody>
							<tr>
								<td><a href="'.admin_url( 'post.php?post='.$post_id.'&action=edit' ).'" target="_blank" rel="noopener">View Entry</a></td>
							</tr>
						</tbody>
					</table>
				</td>
			</tr>
		</tbody>
	</table>'; //Buttons in emails are awful!

	$body .= file_get_contents(__DIR__ . '/email-templates/email-footer.html');

    // send email
	$result = wp_mail($ghost_acf_form_email_to, $subject, $body, $headers );

	return $post_id;
}
add_action('acf/save_post', 'GhostACFForm_after_save_post', 20);

//Add acf_form_head if shortcode detected
function GhostACFForm_enqueue_acf_form() {
	if( is_admin() )
		return;

	global $post;
	if( has_shortcode( $post->post_content, 'ghost_acf_form' ) ){
		acf_form_head();
	}
}
add_action( 'wp', 'GhostACFForm_enqueue_acf_form', 15 );

//Shortcode
function GhostACFForm_shortcode( $atts ) {

	if( is_admin() || wp_doing_ajax() )
		return;

	$args = shortcode_atts( array(
		'form_name' => '',
		'email_to' => get_option( 'admin_email' ),
		'email_off' => false
	), $atts );

	$form_name = $args['form_name'];

	if(empty($form_name)) {
		return;
	}

	$forms = acf_get_field_groups(array('post_type' => 'ghost-form-entry'));

	foreach($forms as $form) {
		//Form found
		if($form['title'] == $form_name) {
			//Save the form args in memory to save into the post
			if(session_id() == '') {
				session_start();
			}
			$_SESSION['ghost_acf_form_key'] = $form['key'];
			$_SESSION['ghost_acf_form_email_to'] = $args['email_to'];
			$_SESSION['ghost_acf_form_email_disabled'] = $args['email_off'];

			//Render the form
			return GhostACFForm_render_form($form);
		}
	}
}
add_shortcode( 'ghost_acf_form', 'GhostACFForm_shortcode' );

//Render form
function GhostACFForm_render_form($form) {

	$field_groups = array($form['key']);
	$fields = acf_get_fields($form['key']);

	//Check we have first_name, last_name and email fields
	$checked_fields = 0;
	foreach($fields as $field) {
		switch ($field['name']) {
			case 'first_name':
			case 'last_name':
			case 'email':
			$checked_fields++;
			break;
		}
	}
	if($checked_fields != 3) {
		return "<strong>[Form does not have the required First Name, Last Name and Email fields. Please add these fields to your form.]</strong>";
	}

	$output = "";
	$output .= '<div id="ghost-acf-form">';

	$sent = !empty($_GET['entry']) && $_GET['entry'] == "confirmed";
	if(!$sent) {
		ob_start();
		acf_form(array(
			'field_groups' => $field_groups,
			'post_id' => 'new_post',
			'post_title' => false,
			'new_post' => array(
				'post_type' => 'ghost-form-entry',
				'post_status'	=> 'publish',
			),
			'submit_value' => __("Submit", 'ghost-acf-form'),
			'return' => get_permalink() . '?entry=confirmed#ghost-acf-form',
			'honeypot' => true,
			'html_submit_button'	=> '<input type="submit" class="btn btn-primary" value="%s" />',
			'recaptcha' => true,
		));
		$output .= ob_get_clean();
	}
	else {
		$output .= '<div class="card text-center">';
		$output .= '<div class="card-body">';
		$output .= '<strong>Thank you.</strong><br />';
		$output .= '<br />';
		$output .= '<strong>Your submission has been recorded.</strong>';
		$output .= '</div>';
		$output .= '</div>';
	}
	$output .= '</div>';

	return $output;
}

//Find the relevent form for the form entry
function GhostACFForm_select_form($groups) {
	if(!is_admin()) {
		return $groups;
	}

	if(get_current_screen()->id != 'ghost-form-entry') {
		return $groups;
	}

	global $post;

	$form_key = get_post_meta( $post->ID, 'form_key', true );

	if(empty($form_key)) {
		return $groups;
	}
	else {
		foreach($groups as $group) {
			if($group['key'] == $form_key) {
				return array($group);
			}
		}
	}
}
add_filter('acf/get_field_groups', 'GhostACFForm_select_form');

function GhostACFForm_session_start(){
	if( ! session_id() ) {
		session_start();
	}
}
add_action('init', 'GhostACFForm_session_start');