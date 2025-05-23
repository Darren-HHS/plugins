<?php
/**
 * Plugin name: Estatik Custom Homepage
 * Version: 1.0
 * Description: Plugin provides custom widgets for Homepage.
 * Author: Estatik
 * Author URI: https://estatik.net
 */

define( 'ECHP_URL', plugin_dir_url( __FILE__ ) );

/**
 * Register styles and scripts.
 */
function ch_enqueue_scripts() {
	wp_register_style( 'jquery-ui', '//ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css' );
	wp_register_style( 'esc-styles', plugin_dir_url( __FILE__ ) . 'assets/ch.css' );
	wp_enqueue_style( 'esc-styles' );
	wp_enqueue_script( 'jquery-ui', 'https://code.jquery.com/ui/1.12.0/jquery-ui.min.js', [ 'jquery' ] );
	wp_enqueue_script( 'esc-js', plugin_dir_url( __FILE__ ) . 'assets/esc.js', [ 'jquery', 'es-admin-googlemap-api' ] );
}

add_action( 'wp_enqueue_scripts', 'ch_enqueue_scripts' );

/**
 * @param $templates
 * Add new template.
 * @return mixed
 */
function ch_add_page_template( $templates ) {
	$templates['templates/full-width-template.php'] = '100% Width Template';
	return $templates;
}
add_filter ('theme_page_templates', 'ch_add_page_template');

/**
 * @param $template
 * Override template path from theme to plugin.
 * @return mixed|string
 */
function ch_page_template( $template ) {
	$post = get_post();
	$page_template = get_post_meta( $post->ID, '_wp_page_template', true );
	if ('full-width-template.php' == basename($page_template)) {
		$template = dirname( __FILE__ ) . '/templates/full-width-template.php';
	}
	return $template;
}
add_filter( 'page_template', 'ch_page_template' );

/**
 * Register new widgets.
 * Ch_Homepage_Hero - hero widget with search on the background image.
 */
function ch_register_widgets() {
  require_once 'includes/class-ch-homepage-hero-widget.php';
  register_widget( 'Ch_Homepage_Hero' );
  require_once 'includes/class-ch-homepage-cat-links.php';
  register_widget( 'Ch_Homepage_Cat_links' );
  require_once 'includes/class-ch-homepage-map.php';
  register_widget( 'Ch_Homepage_Map' );
}
add_action( 'widgets_init', 'ch_register_widgets' );

/**
 * @param $form_options
 * @param $widget
 * Add new option to ept_prop_cat_grid_form with new size.
 * @return mixed
 */
function ch_extend_ept_prop_cat_grid_form( $form_options, $widget ) {
	// Lets add a new theme option.
	if ( ! empty($form_options["items_per_row"]["options"]) ) {
		$form_options["items_per_row"]["options"]['3'] = __('4 cols', 'ept');
	}

	return $form_options;
}
add_filter('siteorigin_widgets_form_options_ept-prop-cat-grid', 'ch_extend_ept_prop_cat_grid_form', 10, 2);

