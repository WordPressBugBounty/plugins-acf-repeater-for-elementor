<?php
/*
Plugin Name: ACF Repeater for Elementor
Plugin URI: http://wordpress.org/plugins/acf-repeater-for-elementor/
Description: Easy and simple way to use acf pro repeater in elementor.
Author: Sympl
Version: 2.2
Author URI: https://sympl.co.il/
*/

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
$elementor_acf_repeater_settings = new Elementor_ACF_Field_Settings();


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

    if( empty($classes) ) {
        $classes = $widget->get_settings('css_classes');
    }

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

        // Update doesnt work with old versions elementor pages.
        // Update the post meta with the modified data
//        update_post_meta( get_the_ID(), '_elementor_data', json_decode($elementor_data) );

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
        // Special handling for accordion widget
        if($widget->get_name() == "accordion" || $widget->get_name() == "toggle") {
            echo arfe_prepare_accordion_by_repeater($widget_content_output, $repeater_name);
        } else if($widget->get_name() == "nested-accordion") {
            echo arfe_prepare_nested_accordion_by_repeater($widget_content_output, $repeater_name);
        } else {
            echo arfe_prepare_content_by_repeater($widget_content_output, $repeater_name);
        }
    } else {
        echo $widget_content_output;
    }
});


/**
 * Process accordion widget with repeater field
 * Instead of duplicating the entire accordion widget, this function adds tabs to the existing accordion
 */
function arfe_prepare_accordion_by_repeater($content, $repeater_name) {
    $repeater = get_field($repeater_name);
    if(!$repeater || count($repeater) == 0) {
        return "";
    }

    // Create a DOMDocument to parse the HTML content
    $dom = new \DOMDocument();
    // Suppress warnings for HTML5 tags
    @$dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    $xpath = new \DOMXPath($dom);

    // Find the main accordion container
    $accordion = $xpath->query('//div[contains(@class, "elementor-accordion")]')->item(0);

    if (!$accordion) {
        return $content; // Return original content if accordion not found
    }

    // Find the first accordion item to use as a template
    $template_item = $xpath->query('.//div[contains(@class, "elementor-accordion-item")]', $accordion)->item(0);

    if (!$template_item) {
        return $content; // Return original content if no accordion item found
    }

    // Find the title and content elements in the template item
    $title_wrapper = $xpath->query('.//*[contains(@class, "elementor-tab-title")]', $template_item)->item(0);
    $content_element = $xpath->query('.//*[contains(@class, "elementor-tab-content")]', $template_item)->item(0);

    // Extract base ID and tab count for generating unique IDs
    $base_id = '';
    $tab_count = 1;

    if ($title_wrapper && $title_wrapper->hasAttribute('id')) {
        $id_attr = $title_wrapper->getAttribute('id');
        // Parse ID like "elementor-tab-title-1231"
        if (preg_match('/elementor-tab-title-(\d+)(\d+)$/', $id_attr, $matches)) {
            $base_id = $matches[1]; // e.g., "123"
            $tab_count = intval($matches[2]); // e.g., "1"
        }
    }

    // Clone the template item for each repeater row
    $index = 0;
    foreach ($repeater as $row) {
        $new_item = $template_item->cloneNode(true);
        $index++;

        // Find elements that need unique IDs in the new item
        $new_title_wrapper = $xpath->query('.//*[contains(@class, "elementor-tab-title")]', $new_item)->item(0);
        $new_content_element = $xpath->query('.//*[contains(@class, "elementor-tab-content")]', $new_item)->item(0);
        $title_element = $xpath->query('.//a[contains(@class, "elementor-accordion-title")]', $new_item)->item(0);

        // Calculate new tab count
        $new_tab_count = $tab_count + $index;

        // Update IDs and attributes for the title wrapper
        if ($new_title_wrapper) {
            if ($new_title_wrapper->hasAttribute('id')) {
                $new_title_wrapper->setAttribute('id', 'elementor-tab-title-' . $base_id . $new_tab_count);
            }
            if ($new_title_wrapper->hasAttribute('data-tab')) {
                $new_title_wrapper->setAttribute('data-tab', $new_tab_count);
            }
            if ($new_title_wrapper->hasAttribute('aria-controls')) {
                $new_title_wrapper->setAttribute('aria-controls', 'elementor-tab-content-' . $base_id . $new_tab_count);
            }
        }

        // Update IDs and attributes for the content element
        if ($new_content_element) {
            if ($new_content_element->hasAttribute('id')) {
                $new_content_element->setAttribute('id', 'elementor-tab-content-' . $base_id . $new_tab_count);
            }
            if ($new_content_element->hasAttribute('data-tab')) {
                $new_content_element->setAttribute('data-tab', $new_tab_count);
            }
            if ($new_content_element->hasAttribute('aria-labelledby')) {
                $new_content_element->setAttribute('aria-labelledby', 'elementor-tab-title-' . $base_id . $new_tab_count);
            }
        }

        // Replace placeholders in title
        if ($title_element) {
            $title = $title_element->textContent;
            foreach ($row as $key => $value) {
                $title = str_replace("#" . $key, $value, $title);
            }
            $title_element->textContent = $title;
        }

        // Replace placeholders in content
        if ($new_content_element) {
            $content_html = $dom->saveHTML($new_content_element);

            // Replace placeholders in content HTML
            foreach ($row as $key => $value) {
                $content_html = str_replace("#" . $key, $value, $content_html);
            }

            // Update the content element's innerHTML
            $temp_dom = new \DOMDocument();
            @$temp_dom->loadHTML(mb_convert_encoding($content_html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            $temp_content = $temp_dom->getElementsByTagName('div')->item(0);

            if ($temp_content) {
                // Import the node from temp_dom to our main dom
                $imported_content = $dom->importNode($temp_content, true);

                // Replace the old content with the new one
                if ($new_content_element->parentNode) {
                    $new_content_element->parentNode->replaceChild($imported_content, $new_content_element);
                }
            }
        }

        // Add the new item to the accordion
        $accordion->appendChild($new_item);
    }

    // Remove the template item (first item) as it was just used as a template
    $accordion->removeChild($template_item);

    // Get the modified HTML
    $html = $dom->saveHTML();

    return $html;
}

/**
 * Process nested-accordion widget with repeater field
 * Instead of duplicating the entire accordion widget, this function adds items to the existing nested-accordion
 */
function arfe_prepare_nested_accordion_by_repeater($content, $repeater_name) {
    $repeater = get_field($repeater_name);
    if(!$repeater || count($repeater) == 0) {
        return "";
    }

    // Create a DOMDocument to parse the HTML content
    $dom = new \DOMDocument();
    // Suppress warnings for HTML5 tags
    @$dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    $xpath = new \DOMXPath($dom);

    // Find the main accordion container
    $accordion = $xpath->query('//div[contains(@class, "e-n-accordion")]')->item(0);

    if (!$accordion) {
        return $content; // Return original content if accordion not found
    }

    // Find the first accordion item to use as a template
    $template_item = $xpath->query('.//details[contains(@class, "e-n-accordion-item")]', $accordion)->item(0);

    if (!$template_item) {
        return $content; // Return original content if no accordion item found
    }

    // Find the title and content elements in the template item
    $title_wrapper = $xpath->query('.//summary[contains(@class, "e-n-accordion-item-title")]', $template_item)->item(0);
    $title_text = $xpath->query('.//*[contains(@class, "e-n-accordion-item-title-text")]', $template_item)->item(0);
    $content_element = $xpath->query('.//div[@role="region"]', $template_item)->item(0);

    // Extract base ID for generating unique IDs
    $base_id = '';
    $item_count = 1;

    if ($template_item && $template_item->hasAttribute('id')) {
        $id_attr = $template_item->getAttribute('id');
        // Parse ID like "e-n-accordion-item-1440"
        if (preg_match('/e-n-accordion-item-(\d+)$/', $id_attr, $matches)) {
            $base_id = $matches[1]; // e.g., "1440"
        }
    }

    // Check if the template item has the open attribute
    $has_open_attr = $template_item->hasAttribute('open');

    // Clone the template item for each repeater row
    $index = 0;
    foreach ($repeater as $row) {
        $new_item = $template_item->cloneNode(true);
        $index++;

        // Generate a new unique ID for this item
        $new_item_id = 'e-n-accordion-item-' . ($base_id + $index);
        $new_item->setAttribute('id', $new_item_id);

        // Only keep the open attribute for the first item
        if ($has_open_attr) {
            if ($index > 1) {
                $new_item->removeAttribute('open');
            }
        }

        // Find elements that need unique IDs in the new item
        $new_title_wrapper = $xpath->query('.//summary[contains(@class, "e-n-accordion-item-title")]', $new_item)->item(0);
        $new_title_text = $xpath->query('.//*[contains(@class, "e-n-accordion-item-title-text")]', $new_item)->item(0);
        $new_content_element = $xpath->query('.//div[@role="region"]', $new_item)->item(0);

        // Update attributes for the title wrapper
        if ($new_title_wrapper) {
            $new_title_wrapper->setAttribute('aria-controls', $new_item_id);
            $new_title_wrapper->setAttribute('data-accordion-index', $item_count + $index);
        }

        // Update attributes for the content element
        if ($new_content_element) {
            $new_content_element->setAttribute('aria-labelledby', $new_item_id);
        }

        // Replace placeholders in title
        if ($new_title_text) {
            $title = $new_title_text->textContent;
            foreach ($row as $key => $value) {
                $title = str_replace("#" . $key, $value, $title);
            }
            $new_title_text->textContent = $title;
        }

        // Replace placeholders in content
        if ($new_content_element) {
            $content_html = $dom->saveHTML($new_content_element);

            // Replace placeholders in content HTML
            foreach ($row as $key => $value) {
                $content_html = str_replace("#" . $key, $value, $content_html);
            }

            // Update the content element's innerHTML
            $temp_dom = new \DOMDocument();
            @$temp_dom->loadHTML(mb_convert_encoding($content_html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            $temp_content = $temp_dom->getElementsByTagName('div')->item(0);

            if ($temp_content) {
                // Import the node from temp_dom to our main dom
                $imported_content = $dom->importNode($temp_content, true);

                // Replace the old content with the new one
                if ($new_content_element->parentNode) {
                    $new_content_element->parentNode->replaceChild($imported_content, $new_content_element);
                }
            }
        }

        // Add the new item to the accordion
        $accordion->appendChild($new_item);
    }

    // Remove the template item (first item) as it was just used as a template
    $accordion->removeChild($template_item);

    // Get the modified HTML
    $html = $dom->saveHTML();

    return $html;
}
