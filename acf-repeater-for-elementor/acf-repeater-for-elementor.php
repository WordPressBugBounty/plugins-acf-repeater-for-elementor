<?php
/*
Plugin Name: ACF Repeater for Elementor
Plugin URI: http://wordpress.org/plugins/acf-repeater-for-elementor/
Description: Easy and simple way to use acf pro repeater in elementor.
Author: Sympl
Version: 2.0
Author URI: https://sympl.co.il/
*/

//TODO! 2.1 - Render accordion tabs better (not by duplicating the widget but by injecting new tabs into the existing widget)

//Import the elementor settings
require_once( plugin_dir_path( __FILE__ ) . 'arfe-elementor-settings.php' );

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}
// Check for required plugins on activation
function conditional_plugin_activation_check() {
    // Check if ACF Pro is active
    if ( ! class_exists( 'ACF' ) || ! function_exists( 'acf_get_setting' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die(
            __( 'This plugin requires ACF Pro to be installed and active.', 'acf-repeater-for-elementor' ),
            'Plugin Activation Error',
            array( 'back_link' => true )
        );
    }

    // Check if Elementor Pro is active
    if ( ! did_action( 'elementor/loaded' ) || ! class_exists( 'ElementorPro\Plugin' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die(
            __( 'This plugin requires Elementor Pro to be installed and active.', 'acf-repeater-for-elementor' ),
            'Plugin Activation Error',
            array( 'back_link' => true )
        );
    }
}
register_activation_hook( __FILE__, 'conditional_plugin_activation_check' );

// Prevent plugin from running if dependencies are not met
function conditional_plugin_dependency_check() {
    if ( ! class_exists( 'ACF' ) || ! function_exists( 'acf_get_setting' ) || ! did_action( 'elementor/loaded' ) || ! class_exists( 'ElementorPro\Plugin' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-error"><p>This plugin requires ACF Pro and Elementor Pro to be installed and active.</p></div>';
        } );
        deactivate_plugins( plugin_basename( __FILE__ ) );
    }
}
add_action( 'admin_init', 'conditional_plugin_dependency_check' );


// Add the ACF Repeater settings to Elementor widgets - use Elementor_ACF_Repeater_Settings class
$elementor_acf_repeater_settings = new Elementor_ACF_Repeater_Settings();


add_action( 'elementor/widgets/register', function( $widgets_manager ) {
    require_once __DIR__ . '/widgets/arfe-repeater-carousel.php';
    $widgets_manager->register( new \ARFE_Repeater_For_Elementor\Widgets\ARFE_Repeater_Carousel() );
} );

add_action('elementor/frontend/container/before_render', 'arfe_catch_output', 10, 1);
//add_action('elementor/frontend/container/before_render', 'arfe_handle_carousel', 10, 1);
add_action('elementor/frontend/column/before_render', 'arfe_catch_output', 10, 1);
add_action('elementor/frontend/section/before_render', 'arfe_catch_output', 10, 1);




function arfe_catch_output( $section ) {
    // Catch output
    ob_start();
}

add_action('elementor/frontend/container/after_render', 'arfe_handle_wrapper', 10, 1);
add_action('elementor/frontend/column/after_render', 'arfe_handle_wrapper', 10, 1);
add_action('elementor/frontend/section/after_render', 'arfe_handle_wrapper', 10, 1);

// And then
function arfe_handle_wrapper( $section ) {
    // Collect output
    $content = ob_get_clean();

    // Alter the output anyway you want, in your case wrapping 
    // it with a classed div should be something like this
    // make sure to echo it
    	if($repeater_name = arfe_check_if_repeater_class_in_widget($section)) {
			echo arfe_prepare_content_by_repeater($content, $repeater_name);
	} else {
			echo $content;
		}
}

function arfe_prepare_content_by_repeater($content, $repeater_name) {
		$repeater = get_field($repeater_name);
		if(!$repeater || count($repeater) == 0) {
			return "";
		}
		
		$new_view = '';
		foreach($repeater as $row) {
			$single_content = $content;
			$single_content = apply_filters('arfe_repeater_row_content', $single_content, $row);
			$new_view = $new_view.''.$single_content;
		}
		return $new_view;
}

add_action('arfe_repeater_row_content', 'arfe_repeater_row_content_fn', 10, 2);
function arfe_repeater_row_content_fn ($single_content, $row)
{
    foreach($row as $key => $value ) {
        $single_content = arfe_replace_content($single_content, $key, $value);
    }
    return $single_content;
}


function arfe_replace_content($content, $key, $value) {
    // Replace the content with the value of the key
    // We use preg_replace to handle cases where the key might be in a format like {{key}}
    return str_replace("#".$key, $value, $content);
}

function arfe_check_if_repeater_class_in_widget($widget) {
    $classes = $widget->get_settings('_css_classes');
    //Check if there is a class name start with arfe_repeater_ - if so return the name of the repeater
    if (preg_match('/arfe_repeater_([A-Za-z0-9\-_]+)/', $classes, $matches)) {
        return $matches[1]; // Return the repeater name
    }

    //Legacy support for old class names
    //TODO: Remove this in the future
    if (preg_match('/repeater_([A-Za-z0-9\-_]+)/', $classes, $matches)) {
        // If the class name is found with the old format, fix it to the new format
        // Update the class name in the widget settings
        $elementor_data = get_post_meta( get_the_ID(), '_elementor_data', true );

        // Stringify the data
        $elementor_data = json_encode( $elementor_data, true );
        // Look for repeater_ in the data and replace it with 'arfe_repeater_'
        $elementor_data = preg_replace('/repeater_/', 'arfe_repeater_', $elementor_data, 1);

        // Update the post meta with the modified data
        update_post_meta( get_the_ID(), '_elementor_data', json_decode($elementor_data) );

        return $matches[1];
    }



	return false;
}




//Handle print widget content from widget wrappers
add_action( 'elementor/frontend/widget/should_render', function( $should_render ) {
    ob_start();
    return $should_render;
});

add_action( 'elementor/frontend/widget/after_render', function( $widget ) {
    $widget_content_output = ob_get_clean();

    $repeater_name = arfe_check_if_repeater_class_in_widget($widget);
    // We handle the loop-carousel in the elementor/widget/render_content hook

    if($repeater_name && $widget->get_name() != "loop-carousel" && $widget->get_name() != "arfe_repeater_carousel") {
        echo arfe_prepare_content_by_repeater($widget_content_output, $repeater_name);
    } else {
        echo $widget_content_output;
    }
});




