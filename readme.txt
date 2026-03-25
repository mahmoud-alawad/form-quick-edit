=== Form Quick Edit ===
Contributors: mahmoud
Donate link: https://buymeacoffee.com/mahmoudalawad
Tags: forms, quick edit, contact form 7, wpforms, frontend editor
Requires at least: 5.6
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds a Quick Edit icon to forms on the frontend. Allows administrators to instantly access the form editor with a single click.

== Description ==

**Form Quick Edit** adds a convenient edit button overlay to your forms on the frontend. When an administrator hovers over any supported form, a small pencil icon appears, linking directly to the form's editor in the admin panel.

No more hunting through the dashboard to find the right form — just hover and click.

= Supported Form Plugins =

* **Contact Form 7**
* **Fluent Forms**
* **Forminator**
* **Ninja Forms**
* **WPForms / WPForms Lite**
* **SureForms**

= Features =

* Edit button appears only for users with the right capability per plugin
* Opens the form editor in a new tab
* Subtle dashed outline on hover to identify the form area
* Lightweight — no jQuery dependency, minimal CSS and JS
* Zero configuration required — just activate and go

== Installation ==

1. Upload the `form-quick-edit` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Visit any page containing a form while logged in as an administrator

== Frequently Asked Questions ==

= Who can see the edit button? =

Users who have the capability to edit forms for the specific plugin. For example, CF7 requires `wpcf7_edit_contact_forms`, Fluent Forms requires `fluentform_forms_manager`, and so on. Administrators can see all edit buttons.

= Does it affect the frontend for regular visitors? =

No. The CSS and JS assets are only loaded for administrators. Regular visitors see no changes.

= Can I add support for other form plugins? =

The plugin is developer-friendly. You can extend it by hooking into the appropriate form plugin's render filters and using the same wrapper pattern.

== Screenshots ==

1. Quick Edit button appearing on hover over a Contact Form 7 form.

== Upgrade Notice ==

= 1.0.0 =
Initial release.

== Changelog ==

= 1.0.0 =
* Initial release
* Support for Contact Form 7, Fluent Forms, Forminator, Ninja Forms, WPForms, and SureForms
