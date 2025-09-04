<?php

namespace ARFE_Repeater_For_Elementor\Widgets;

use Elementor\Controls_Manager;
use ElementorPro\Modules\LoopBuilder\Widgets\Loop_Carousel;

if (!defined('ABSPATH')) exit;

class ARFE_Repeater_Carousel extends Loop_Carousel
{

    public function get_name()
    {
        return 'arfe_repeater_carousel';
    }

    public function get_title()
    {
        return __('ACF Repeater Loop Carousel', 'arfe-repeater-for-elementor');
    }

    public function get_icon()
    {
        return 'eicon-slider-push';
    }

    public function get_categories()
    {
        return ['general'];
    }

    protected function register_query_section()
    {
        // We override this method to prevent the default query controls from being registered.
    }


    public function get_query()
    {
        $settings = $this->get_settings_for_display();
        $repeater_name = $settings['arfe_repeater_field'];
        $repeater = get_field($repeater_name);
        return new ARFE_Repeater_Query($repeater);
    }

    public function before_render()
    {
        add_action('elementor/frontend/the_content', [$this, 'test_content'], 1, 1);
        parent::before_render();
    }

    public function test_content($content)
    {
        return $content;
    }

    // elementor-widget-loop-carousel Must be part of the class name so all the styling settings will be attached to it.
    protected function get_html_wrapper_class() {
        return 'elementor-widget-' . $this->get_name() . ' elementor-widget-loop-carousel';
    }

    public function after_render()
    {
        parent::after_render();
        remove_filter('elementor/frontend/the_content', [$this, 'test_content']);

//        $swiper_settings = $this->get_swiper_settings();
        // Output initialization script
        ?>
        <script>
            jQuery(window).on('elementor/frontend/init', function () {
                class ARFELoopCarousel extends elementorModules.frontend.handlers.CarouselBase {
                    getDefaultSettings() {
                        const defaultSettings = super.getDefaultSettings();
                        defaultSettings.selectors.carousel = '.elementor-loop-container';
                        return defaultSettings;
                    }

                    getSwiperSettings() {
                        const swiperOptions = super.getSwiperSettings();
                        const elementSettings = this.getElementSettings();
                        const isRtl = elementorFrontend.config.is_rtl;
                        const widgetSelector = `.elementor-element-${this.getID()}`;


                        // This is the crucial part - proper navigation setup
                        if ('yes' === elementSettings.arrows) {
                            swiperOptions.navigation = {
                                prevEl: isRtl ? `${widgetSelector} .elementor-swiper-button-next` : `${widgetSelector} .elementor-swiper-button-prev`,
                                nextEl: isRtl ? `${widgetSelector} .elementor-swiper-button-prev` : `${widgetSelector} .elementor-swiper-button-next`
                            };
                        }

                        swiperOptions.on.beforeInit = () => {
                            this.a11ySetSlidesAriaLabels();
                        };

                        return swiperOptions;
                    }

                    async onInit() {
                        // Don't call super.onInit() to avoid the early return when there's only one slide
                        // Instead, call the grandparent's onInit method
                        elementorModules.frontend.handlers.Base.prototype.onInit.apply(this, arguments);

                        // Initialize Swiper regardless of slide count
                        const Swiper = elementorFrontend.utils.swiper;
                        const swiperOptions = this.getSwiperSettings();

                        // Only proceed with Swiper initialization if we have a container
                        if (this.elements.$swiperContainer && this.elements.$swiperContainer.length) {
                            this.swiper = await new Swiper(this.elements.$swiperContainer, swiperOptions);

                            // Handle pause on hover if enabled
                            const elementSettings = this.getElementSettings();
                            if ('yes' === elementSettings.pause_on_hover) {
                                this.togglePauseOnHover(true);
                            }

                            // Expose the swiper instance in the frontend
                            this.elements.$swiperContainer.data('swiper', this.swiper);
                        }

                        this.ranElementHandlers = false;
                    }

                    a11ySetSlidesAriaLabels() {
                        const slides = Array.from(this.elements.$slides);
                        slides.forEach((slide, index) => {
                            slide.setAttribute('aria-label', `${parseInt(index + 1)} of ${slides.length}`);
                        });
                    }
                }



                elementorFrontend.hooks.addAction('frontend/element_ready/arfe_repeater_carousel.post', function ($scope) {
                    // Use the same handler as loop-carousel
                    const carousel = new ARFELoopCarousel({
                        $element: $scope
                    });
                });
            });
        </script>
        <?php
    }

    public function get_script_depends()
    {
        return ['elementor-pro-frontend'];
    }

}


class ARFE_Repeater_Query
{
    private $repeater;
    private $index = 0;
    private $count = 0;
    public $found_posts = 0;
    public $post_count = 0;
    public $max_num_pages = 1;
    public $in_the_loop = false;

    public function __construct($repeater)
    {
        $this->repeater = is_array($repeater) ? array_values($repeater) : [];
        $this->count = count($this->repeater);
        $this->found_posts = $this->count;
        $this->post_count = $this->count;
    }

    public function have_posts()
    {
        return $this->index < $this->count;
    }

    public function the_post()
    {
        $this->in_the_loop = true;
        $this->index++;
        // Register the action when the_post is called at skin-base.php line 926
        add_action('elementor/frontend/the_content', [$this, 'modify_the_content'], 10, 1);
    }

    public function modify_the_content($content)
    {
        $current_row = $this->get_current_row();

        // Process the row to translate values if the function exists
        if (function_exists('arfe_process_repeater_row')) {
            $current_row = arfe_process_repeater_row($current_row);
        }

        $content = apply_filters('arfe_repeater_row_content', $content, $current_row);
        // Remove the action after modifying the content
        remove_action('elementor/frontend/the_content', [$this, 'modify_the_content'], 10);
        return $content;
    }

    public function rewind_posts()
    {
        $this->index = 0;
        $this->in_the_loop = false;
    }

    public function get_current_row()
    {
        return $this->repeater[$this->index - 1] ?? null;
    }
}
