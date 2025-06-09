<?php
/**
 * Elementor ACF Repeater Settings
 *
 * This class adds custom settings for ACF Repeater fields to Elementor widgets.
 *
 * @package ACF_Repeater_For_Elementor
 */
class Elementor_ACF_Repeater_Settings
{
    public function __construct()
    {
        // Add custom Advanced settings for various Elementor widgets
        add_action('elementor/element/container/section_layout/after_section_end', array($this, 'add_repeater_section'), 10, 2);
        add_action('elementor/element/column/section_advanced/after_section_end', array($this, 'add_repeater_section'), 10, 2);
        add_action('elementor/element/section/section_advanced/after_section_end', array($this, 'add_repeater_section'), 10, 2);
        add_action('elementor/element/common/_section_style/after_section_end', array($this, 'add_repeater_section'), 10, 2);

        // Process the repeater value and add it to CSS classes
        add_action('elementor/frontend/container/before_render', array($this, 'before_render_element'), 5, 1);
        add_action('elementor/frontend/column/before_render', array($this, 'before_render_element'), 5, 1);
        add_action('elementor/frontend/section/before_render', array($this, 'before_render_element'), 5, 1);
        add_action('elementor/frontend/widget/before_render', array($this, 'before_render_element'), 5, 1);

        //Commented: Currently doesn't work with Elementor Pro widget cache
        //Show the repeater fields as a selectable option in the Elementor editor dynamic tags
        //add_action('elementor/dynamic_tags/register', array($this, 'register_dynamic_tags'));
    }

    /**
     * Register dynamic tags for ACF Repeater fields
     */
    public function register_dynamic_tags($dynamic_tags)
    {
        require_once(plugin_dir_path(__FILE__) . 'arfe-dynamic-elementor-tag.php');
        $repeater_fields = ACF_Repeater_Dynamic_Tag::GET_ALL_INNER_REPEATER_FIELDS_FLATTENED();

        $dynamic_tags->register(new \ACF_Repeater_Dynamic_Tag());
    }

    /**
     * Add a dedicated ACF Repeater section to Elementor widgets
     */
    public function add_repeater_section($element, $args)
    {
        // Add new section for ACF Repeater
        $element->start_controls_section(
            'section_acf_repeater',
            [
                'label' => __('ACF Repeater', 'acf-repeater-for-elementor'),
                'tab' => \Elementor\Controls_Manager::TAB_ADVANCED,
            ]
        );

        // Get available repeater fields
        $repeater_fields = $this->get_acf_repeater_fields();

        if (!empty($repeater_fields)) {
            $element->add_control(
                'arfe_repeater_field',
                [
                    'label' => __('Select Repeater Field', 'acf-repeater-for-elementor'),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'options' => $repeater_fields,
                    'default' => '',
                    'description' => __('Select the ACF repeater field to use with this element.', 'acf-repeater-for-elementor'),
                ]
            );

            // If current element is a container add a checkbox to enable/disable to enable carousel mode
            if ($element->get_name() === 'container') {
                $element->add_control(
                    'arfe_repeater_carousel',
                    [
                        'label' => __('Enable Carousel Mode', 'acf-repeater-for-elementor'),
                        'type' => \Elementor\Controls_Manager::SWITCHER,
                        'label_on' => __('Yes', 'acf-repeater-for-elementor'),
                        'label_off' => __('No', 'acf-repeater-for-elementor'),
                        'return_value' => 'yes',
                        'default' => '',
                        'description' => __('Enable carousel mode for this repeater container.', 'acf-repeater-for-elementor'),
                    ]
                );
            }

            $element->add_control(
                'arfe_repeater_info',
                [
                    'type' => \Elementor\Controls_Manager::RAW_HTML,
                    'raw' => __('Use field patterns like #field_name in your content to insert repeater field values.', 'acf-repeater-for-elementor'),
                    'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
                    'condition' => [
                        'arfe_repeater_field!' => '',
                    ],
                ]
            );
        } else {
            $element->add_control(
                'arfe_repeater_not_found',
                [
                    'type' => \Elementor\Controls_Manager::RAW_HTML,
                    'raw' => __('No ACF repeater fields found. Please add repeater fields to your ACF field group.', 'acf-repeater-for-elementor'),
                    'content_classes' => 'elementor-panel-alert elementor-panel-alert-warning',
                ]
            );
        }

        $element->end_controls_section();
    }

    /**
     * Get all ACF repeater fields for the current post
     */
    private function get_acf_repeater_fields()
    {
        $repeater_fields = array('' => __('-- Select Repeater --', 'acf-repeater-for-elementor'));

        if (function_exists('acf_get_field_groups')) {
            $field_groups = acf_get_field_groups();

            foreach ($field_groups as $field_group) {
                $fields = acf_get_fields($field_group);

                if (!empty($fields)) {
                    foreach ($fields as $field) {
                        if ($field['type'] === 'repeater') {
                            $repeater_fields[$field['name']] = $field['label'];
                        }
                    }
                }
            }
        }

        return $repeater_fields;
    }

    /**
     * Before rendering the element, add the repeater name to CSS classes
     */
    public function before_render_element($element)
    {
        $settings = $element->get_settings_for_display();

        // Add the repeater class to the element's CSS classes
        $element_class = $element->get_settings('_css_classes');
        $repeater_class = 'arfe_repeater_' . $settings['arfe_repeater_field'];

        if (!empty($settings['arfe_repeater_field'])) {
            //This created to add a class name to the slider wrapper of a single slide.
            //We currently don't use that.

            if ($element->get_name() == 'loop-carousel') {

            }



            if (!empty($element_class)) {
                if (strpos($element_class, $repeater_class) === false) {
                    $element_class .= ' ' . $repeater_class;
                }
            } else {
                $element_class = $repeater_class;
            }

            $element->set_settings('_css_classes', $element_class);

            $element->add_render_attribute(
                '_wrapper',
                [
                    'class' => $element_class
                ]
            );
        }
    }


}