<?php
use Elementor\Controls_Manager;


class ACF_Repeater_Dynamic_Tag extends \Elementor\Core\DynamicTags\Tag {
    public function get_name() {
        return 'acf-repeater-field';
    }

    public function get_title() {
        return esc_html__('ACF Repeater Inner Fields', 'acf-repeater-for-elementor');
    }

    public function get_group() {
        return 'acf'; // Use a standard Elementor group
    }

    public function get_categories() {
        return [
            'text',
        ];
    }

    protected function register_controls() {
        //Get all existing repeater fields
        $options = ACF_Repeater_Dynamic_Tag::GET_ALL_INNER_REPEATER_FIELDS_FLATTENED();

        $this->add_control(
            'repeater_sub_field',
            [
                'label' => esc_html__('Select Option', 'acf-repeater-for-elementor'),
                'type' => Controls_Manager::SELECT,
                'groups' => $options,
                'default' => '',
            ]
        );
    }

    public function render() {
        $settings = $this->get_settings();
        $selected_field = $settings['repeater_sub_field'];
        $dynamic = isset($selected_field) && $selected_field !=='' ? '#'.$settings['repeater_sub_field'] : '';

        echo esc_html($dynamic);
    }

    public static function GET_ALL_INNER_REPEATER_FIELDS_FLATTENED()
    {
        $repeater_fields = get_field_objects();
        $flattened = [];

        if ($repeater_fields) {
            foreach ($repeater_fields as $field_name => $field) {
                if ($field['type'] === 'repeater') {
                    self::FLATTEN_REPEATER_FIELDS($field, $flattened);
                }
            }
        }

        return $flattened;
    }

    private static function FLATTEN_REPEATER_FIELDS($field, &$output, $breadcrumb = '')
    {
        $current_label = $breadcrumb ? $breadcrumb . ' > ' . $field['label'] : $field['label'];
        $options = [];

        foreach ($field['sub_fields'] as $sub_field) {
            if ($sub_field['type'] === 'repeater') {
                // Recursive call for nested repeater
                self::FLATTEN_REPEATER_FIELDS($sub_field, $output, $current_label);
            } else {
                $options[$sub_field['name']] = esc_html($sub_field['label'], 'acf-repeater-for-elementor');
            }
        }

        if (!empty($options)) {
            $output[] = [
                'label' => esc_html($current_label, 'acf-repeater-for-elementor'),
                'options' => $options,
            ];
        }
    }
}