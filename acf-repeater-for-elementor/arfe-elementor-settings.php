<?php

/**
 * Elementor ACF Repeater Settings and Hide on Empty Field
 *
 * This class adds custom settings for ACF fields to Elementor widgets.
 *
 * @package ACF_Fields_For_Elementor
 */
class Elementor_ACF_Field_Settings
{
    public function __construct()
    {
        // Add custom Advanced settings for various Elementor widgets
        add_action('elementor/element/container/section_layout/after_section_end', array($this, 'add_repeater_section'), 10, 2);
        add_action('elementor/element/column/section_advanced/after_section_end', array($this, 'add_repeater_section'), 10, 2);
        add_action('elementor/element/section/section_advanced/after_section_end', array($this, 'add_repeater_section'), 10, 2);
        add_action('elementor/element/common/_section_style/after_section_end', array($this, 'add_repeater_section'), 10, 2);

        // Add hide on empty field settings
        add_action('elementor/element/container/section_layout/after_section_end', array($this, 'add_hide_on_empty_field_section'), 10, 2);
        add_action('elementor/element/column/section_advanced/after_section_end', array($this, 'add_hide_on_empty_field_section'), 10, 2);
        add_action('elementor/element/section/section_advanced/after_section_end', array($this, 'add_hide_on_empty_field_section'), 10, 2);
        add_action('elementor/element/common/_section_style/after_section_end', array($this, 'add_hide_on_empty_field_section'), 10, 2);

        // Process the field value and add it to CSS classes
        add_action('elementor/frontend/container/before_render', array($this, 'before_render_element'), 5, 1);
        add_action('elementor/frontend/column/before_render', array($this, 'before_render_element'), 5, 1);
        add_action('elementor/frontend/section/before_render', array($this, 'before_render_element'), 5, 1);
        add_action('elementor/frontend/widget/before_render', array($this, 'before_render_element'), 5, 1);
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
                    'type' => \Elementor\Controls_Manager::SELECT2,
                    'options' => $repeater_fields,
                    'default' => '',
                    'description' => __('Select the ACF repeater field to use with this element.', 'acf-repeater-for-elementor'),
                ]
            );

            // If current element is a container add a checkbox to enable/disable carousel mode
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
     * Add a section to hide the widget when ACF field is empty
     */
    public function add_hide_on_empty_field_section($element, $args)
    {
        // Start new section for hiding widget on empty field
        $element->start_controls_section(
            'section_hide_empty_field',
            [
                'label' => __('Hide on Condition ACF Field', 'acf-fields-for-elementor'),
                'tab' => \Elementor\Controls_Manager::TAB_ADVANCED,
            ]
        );

        // Get available ACF fields
        $acf_fields = $this->get_acf_fields();

        if (!empty($acf_fields)) {
            // Enable conditions toggle
            $element->add_control(
                'arfe_enable_conditions',
                [
                    'label' => __('Enable Hide Conditions', 'acf-fields-for-elementor'),
                    'type' => \Elementor\Controls_Manager::SWITCHER,
                    'label_on' => __('Yes', 'acf-fields-for-elementor'),
                    'label_off' => __('No', 'acf-fields-for-elementor'),
                    'return_value' => 'yes',
                    'default' => '',
                ]
            );

            // Relation between conditions
            $element->add_control(
                'arfe_conditions_relation',
                [
                    'label' => __('Conditions Relation', 'acf-fields-for-elementor'),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'options' => [
                        'and' => __('AND (all conditions must be true)', 'acf-fields-for-elementor'),
                        'or' => __('OR (at least one condition must be true)', 'acf-fields-for-elementor'),
                    ],
                    'default' => 'and',
                    'condition' => [
                        'arfe_enable_conditions' => 'yes',
                    ],
                ]
            );

            // Repeater for multiple conditions
            $repeater = new \Elementor\Repeater();

            $repeater->add_control(
                'field_name',
                [
                    'label' => __('ACF Field', 'acf-fields-for-elementor'),
                    'type' => \Elementor\Controls_Manager::SELECT2,
                    'options' => $acf_fields,
                    'default' => '',
                ]
            );

            $repeater->add_control(
                'operator',
                [
                    'label' => __('Operator', 'acf-fields-for-elementor'),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'options' => [
                        'empty' => __('Is Empty', 'acf-fields-for-elementor'),
                        'not_empty' => __('Is Not Empty', 'acf-fields-for-elementor'),
                        'equal' => __('Equal To', 'acf-fields-for-elementor'),
                        'not_equal' => __('Not Equal To', 'acf-fields-for-elementor'),
                        'contains' => __('Contains', 'acf-fields-for-elementor'),
                        'not_contains' => __('Does Not Contain', 'acf-fields-for-elementor'),
                        'greater_than' => __('Greater Than', 'acf-fields-for-elementor'),
                        'less_than' => __('Less Than', 'acf-fields-for-elementor'),
                        'greater_equal' => __('Greater Than or Equal', 'acf-fields-for-elementor'),
                        'less_equal' => __('Less Than or Equal', 'acf-fields-for-elementor'),
                    ],
                    'default' => 'empty',
                ]
            );

            $repeater->add_control(
                'value',
                [
                    'label' => __('Value', 'acf-fields-for-elementor'),
                    'type' => \Elementor\Controls_Manager::TEXT,
                    'default' => '',
                    'condition' => [
                        'operator!' => ['empty', 'not_empty'],
                    ],
                ]
            );

            $element->add_control(
                'arfe_hide_conditions',
                [
                    'label' => __('Hide Conditions', 'acf-fields-for-elementor'),
                    'type' => \Elementor\Controls_Manager::REPEATER,
                    'fields' => $repeater->get_controls(),
                    'default' => [
                        [
                            'field_name' => '',
                            'operator' => 'empty',
                            'value' => '',
                        ],
                    ],
                    'title_field' => '{{{ field_name }}} {{{ operator }}} {{{ value }}}',
                    'condition' => [
                        'arfe_enable_conditions' => 'yes',
                    ],
                ]
            );

            // Info message
            $element->add_control(
                'arfe_conditions_info',
                [
                    'type' => \Elementor\Controls_Manager::RAW_HTML,
                    'raw' => __('Element will be hidden when the conditions are met.', 'acf-fields-for-elementor'),
                    'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
                    'condition' => [
                        'arfe_enable_conditions' => 'yes',
                    ],
                ]
            );
        }

        $element->end_controls_section();
    }

    /**
     * Get all ACF fields for the current post
     */
    private function get_acf_fields()
    {
        $acf_fields = array('default' => __('-- Select Field --', 'acf-fields-for-elementor'));

        if (function_exists('acf_get_field_groups')) {
            $field_groups = acf_get_field_groups();

            foreach ($field_groups as $field_group) {
                $fields = acf_get_fields($field_group);

                if (!empty($fields)) {
                    foreach ($fields as $field) {
                        $acf_fields[$field['name']] = $field['label'];
                    }
                }
            }
        }

        return $acf_fields;
    }

    /**
     * Before rendering the element, check if it should be hidden based on ACF field value
     */
    public function before_render_element($element)
    {
        $settings = $element->get_settings_for_display();

        // Process the repeater field if it exists
        if (!empty($settings['arfe_repeater_field'])) {
            $this->process_repeater_field($element, $settings);
        }

        // Hide the widget if field is empty and switch is enabled
        $this->maybe_hide_widget_on_empty_field($element, $settings);
    }

    /**
     * Process the repeater field and add classes
     */
    private function process_repeater_field($element, $settings)
    {
        // Add the repeater class to the element's CSS classes
        $element_class = $element->get_settings('_css_classes');
        $repeater_class = 'arfe_repeater_' . $settings['arfe_repeater_field'];

        if (!empty($settings['arfe_repeater_field'])) {
            //This created to add a class name to the slider wrapper of a single slide.
            //We currently don't use that.
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

    /**
     * Check if the widget should be hidden based on ACF field condition
     */
    /**
     * Check if the widget should be hidden based on ACF field conditions
     */
    private function maybe_hide_widget_on_empty_field($element, $settings)
    {
        // Check if conditions are enabled
        if (empty($settings['arfe_enable_conditions']) || $settings['arfe_enable_conditions'] !== 'yes') {
            return;
        }
        // Check if we have conditions to evaluate
        if (empty($settings['arfe_hide_conditions']) || !is_array($settings['arfe_hide_conditions'])) {
            return;
        }

        $conditions = $settings['arfe_hide_conditions'];
        $relation = !empty($settings['arfe_conditions_relation']) ? $settings['arfe_conditions_relation'] : 'and';

        $should_hide = $this->evaluate_conditions($conditions, $relation);

        if ($should_hide) {
            // Hide the widget by adding a class
            $element->add_render_attribute('_wrapper', [
                'class' => 'hide'
            ]);
        }
    }

    /**
     * Evaluate multiple conditions with AND/OR relation
     */
    private function evaluate_conditions($conditions, $relation = 'and')
    {
        if (empty($conditions)) {
            return false;
        }

        $results = [];

        foreach ($conditions as $condition) {
            if (empty($condition['field_name']) || $condition['field_name'] === 'default') {
                continue;
            }

            $field_value = get_field($condition['field_name']);
            $operator = $condition['operator'];
            $compare_value = isset($condition['value']) ? $condition['value'] : '';

            $result = $this->compare_values($field_value, $compare_value, $operator);
            $results[] = $result;
        }

        // Return based on relation
        if ($relation === 'or') {
            // OR: at least one condition must be true
            return in_array(true, $results, true);
        } else {
            // AND: all conditions must be true
            return !in_array(false, $results, true) && !empty($results);
        }
    }

    /**
     * Compare field value with expected value using specified operator
     */
    private function compare_values($field_value, $compare_value, $operator)
    {
        switch ($operator) {
            case 'empty':
                return empty($field_value) || $field_value === false;

            case 'not_empty':
                return !empty($field_value) && $field_value !== false;

            case 'equal':
                return $field_value == $compare_value;

            case 'not_equal':
                return $field_value != $compare_value;

            case 'contains':
                if (is_array($field_value)) {
                    return in_array($compare_value, $field_value, true);
                }
                return strpos((string)$field_value, (string)$compare_value) !== false;

            case 'not_contains':
                if (is_array($field_value)) {
                    return !in_array($compare_value, $field_value, true);
                }
                return strpos((string)$field_value, (string)$compare_value) === false;

            case 'greater_than':
                return (float)$field_value > (float)$compare_value;

            case 'less_than':
                return (float)$field_value < (float)$compare_value;

            case 'greater_equal':
                return (float)$field_value >= (float)$compare_value;

            case 'less_equal':
                return (float)$field_value <= (float)$compare_value;

            default:
                return false;
        }
    }


}
