=== ACF Repeater For Elementor ===
Contributors: Sympl
Donate link: https://www.paypal.com/donate/?hosted_button_id=GD9PZHTB5PBR8
Tags: elementor, acf, repeater, advanced custom fields, dynamic content
Requires at least: 5.0
Tested up to: 6.8.1
Stable tag: 4.7
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Seamlessly integrate ACF Repeater fields with Elementor widgets and sections for dynamic, repeatable content blocks.

== Description ==

**ACF Repeater For Elementor** bridges the gap between Advanced Custom Fields (ACF) Pro repeater functionality and Elementor's visual builder. This powerful plugin allows you to create dynamic, repeatable content sections without complex coding.

### 🚀 Key Features

* **Easy Integration**: Connect ACF repeater fields directly with Elementor widgets
* **Dynamic Content**: Automatically populate Elementor elements with ACF repeater data
* **Flexible Usage**: Works with columns, sections, and individual widgets
* **Special Support**: Built-in support for accordions and toggle elements
* **Legacy Compatible**: Maintains backward compatibility with existing implementations

### 🎯 Perfect For

* Dynamic testimonial sections
* Portfolio galleries
* FAQ accordions
* Team member listings
* Product showcases
* Any repeatable content structure

### 📋 Requirements

* WordPress 5.0 or higher
* Elementor (free version)
* Elementor Pro (Optional but recommended for full features)
* Advanced Custom Fields (ACF) Pro

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/acf-repeater-for-elementor/` directory
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Ensure you have Elementor, Elementor Pro, and ACF Pro installed and activated
4. Start using ACF repeater fields in your Elementor designs!

== How To Use ==

### Usage (Recommended)

1. **Create ACF Repeater Field**: Set up your repeater field in ACF with sub-fields
2. **Select the repeater**: In Elementor, widget, section or ACF Repeater loop set the ACF repeater field
   - You will be able to select the repeater field from 'Advanced' settings tab in the Elementor editor
3. **Insert Field Placeholders**: Use `#field_name` syntax within your content
   - Replace `field_name` with your ACF sub-field names
4. **Publish**: The plugin automatically replaces placeholders with repeater data

### Example Implementation

**ACF Repeater Setup:**
- Repeater name: `team_members`
- Sub-fields: `member_name`, `member_role`, `member_bio`

**Elementor Setup:**
- Select the field in the Advanced settings for a container: `team_members`
- Content: `#member_name`, `#member_role`, `#member_bio` as a Heading widget or Text Editor widget

### Special Features

**Accordion/Toggle Support:**
Add new Accordion widget to the page, set the ACF Repeater field in the widget's advanced settings, and use the sub-field names as placeholders as described above.

The plugin will automatically structure these for accordion/toggle widgets.

== Legacy Usage ==

For backward compatibility, the old class naming convention is still supported:
- Setup by old class name: `repeater_` for the widget or container
- Same placeholder syntax: `#field_name`

== Supported Field Types ==

* Text fields
* URL fields
* WYSIWYG Editor content
* Accordion/Toggle content (with specific field names)

**Note**: Currently optimized for Elementor native elements. Third-party widgets may require additional configuration.

== Frequently Asked Questions ==

= Does this work with the free version of Elementor? =

You need both Elementor free AND Elementor Pro, plus ACF Pro for full functionality.

= Can I use this with custom post types? =

Yes! The plugin works with any post type where you've assigned ACF repeater fields.

= What happens if my repeater field is empty? =

The element with the repeater class simply won't display any repeated content.

= Can I style the repeated elements? =

Absolutely! All standard Elementor styling options apply to your repeated elements.

= Is this compatible with Elementor templates? =

Yes, you can use ACF repeaters in Elementor templates, theme builder layouts, and popup templates.

== Screenshots ==

1. ACF Repeater field setup in WordPress admin
2. Elementor widget with repeater set
3. Widget repeater sub-fields in Elementor editor
4. Repeater field in the page editor
5. Frontend display of repeated content

== Changelog ==
= 2.0 =
(09/05/2025) now allows the user to select the ACF Repeater field in the Elementor widget or section settings, making it easier to integrate with Elementor's visual builder.
(09/05/2025) new widget - ACF repeater to loop, which allows you to use the ACF repeater field in a loop, making it easier to display repeated content in Elementor.

== Upgrade Notice ==
= 2.0 =
Major update to improve usability and compatibility with Elementor's latest versions. Now supports direct selection of ACF Repeater fields in Elementor widgets and sections, enhancing the dynamic content experience.


== Support ==

For support, feature requests, or bug reports, please visit our [support forum](https://wordpress.org/support/plugin/acf-repeater-for-elementor/) or contact us directly.

**Pro Tip**: Always test new repeater implementations on a staging site before deploying to production!

== Donate ==

Do you enjoy using ACF Repeater For Elementor? Consider supporting the development of this plugin with a donation.
Your contributions help us maintain and improve the plugin for everyone.

[Donate link](https://www.paypal.com/donate/?hosted_button_id=GD9PZHTB5PBR8)
