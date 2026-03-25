<?php

/**
 * Plugin Name: Form Quick Edit
 * Description: Aggiunge un'icona 'Quick Edit' ai form nel frontend. Permette agli amministratori di accedere istantaneamente all'editor del form con un solo click.
 * Version: 1.0.0
 * Author: Mahmoud
 * Text Domain: form-quick-edit
 * License: GPL-2.0-or-later
 */

if (! defined('ABSPATH')) {
	exit;
}

define('FQE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FQE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FQE_VERSION', '1.0.0');

final class Form_Quick_Edit
{

	private static $instance = null;

	public static function instance()
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct()
	{
		add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
		add_filter('wpcf7_form_elements', array($this, 'wrap_cf7_form'), 100);
		add_filter('wpcf7_contact_form_properties', array($this, 'capture_cf7_id'), 10, 2);
		add_action('fluentform/before_form_render', array($this, 'before_fluentform_render'));
		add_action('fluentform/after_form_render', array($this, 'after_fluentform_render'));
		add_filter('forminator_render_form_markup', array($this, 'forminator_form_markup'), 10, 2);
		add_action('ninja_forms_before_container', array($this, 'before_ninja_form'), 10, 3);
		add_action('ninja_forms_after_container', array($this, 'after_ninja_form'), 10, 3);
		add_action('wpforms_frontend_output_before', array($this, 'before_wpforms'), 10, 2);
		add_action('wpforms_frontend_output_after', array($this, 'after_wpforms'), 10, 2);
		add_filter('render_block', array($this, 'sureforms_render_block'), 10, 2);
	}

	/**
	 * Get the capability required to edit forms for each plugin.
	 *
	 * @param string $plugin Plugin identifier.
	 * @return string WordPress capability.
	 */
	private function get_capability($plugin)
	{
		switch ($plugin) {
			case 'cf7':
				return 'wpcf7_edit_contact_forms';
			case 'fluentform':
				return 'fluentform_forms_manager';
			case 'forminator':
				return 'manage_forminator';
			case 'ninja_forms':
				return apply_filters('ninja_forms_admin_menu_capabilities', 'manage_options');
			case 'wpforms':
				return apply_filters('wpforms_manage_cap', 'manage_options');
			case 'sureforms':
				return 'edit_posts';
			default:
				return 'manage_options';
		}
	}

	/**
	 * Check if the current user can edit forms for at least one supported plugin.
	 *
	 * @return bool
	 */
	private function user_can_edit_any_form()
	{
		$capabilities = array(
			'wpcf7_edit_contact_forms',
			'fluentform_forms_manager',
			'manage_forminator',
			'manage_options',
			'edit_posts',
		);

		foreach ($capabilities as $cap) {
			if (current_user_can($cap)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Enqueue assets on frontend for users who can edit at least one form plugin.
	 */
	public function enqueue_assets()
	{
		if (! $this->user_can_edit_any_form()) {
			return;
		}

		wp_enqueue_style(
			'form-quick-edit',
			FQE_PLUGIN_URL . 'assets/css/main.css',
			array(),
			FQE_VERSION
		);

		wp_enqueue_script(
			'form-quick-edit',
			FQE_PLUGIN_URL . 'assets/js/main.js',
			array(),
			FQE_VERSION,
			true
		);
	}

	/**
	 * Generate the quick edit button HTML.
	 *
	 * @param string $edit_url     Admin URL to the form editor.
	 * @param string $plugin_label Human-readable plugin name.
	 * @param string $capability   Required capability to show the button.
	 * @return string
	 */
	private function get_edit_button($edit_url, $plugin_label, $capability = 'manage_options')
	{
		if (! current_user_can($capability)) {
			return '';
		}

		return sprintf(
			'<a href="%s" class="fqe-quick-edit" target="_blank" rel="noopener noreferrer" title="%s">'
				. '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="currentColor">'
				. '<path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34a.9959.9959 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>'
				. '</svg>'
				. '<span class="fqe-label">%s</span>'
				. '</a>',
			esc_url($edit_url),
			esc_attr(sprintf(__('Edit %s Form', 'form-quick-edit'), $plugin_label)),
			esc_html(__('Edit', 'form-quick-edit'))
		);
	}


	private $current_cf7_id = 0;

	/**
	 * Capture the current CF7 form ID before rendering.
	 *
	 * @param array              $properties   Form properties.
	 * @param WPCF7_ContactForm  $contact_form CF7 contact form instance.
	 * @return array
	 */
	public function capture_cf7_id($properties, $contact_form)
	{
		$this->current_cf7_id = $contact_form->id();
		return $properties;
	}

	/**
	 * Wrap CF7 form output with the quick edit button.
	 *
	 * @param string $output Form HTML output.
	 * @return string
	 */
	public function wrap_cf7_form($output)
	{
		$cap = $this->get_capability('cf7');
		if (! current_user_can($cap) || ! $this->current_cf7_id) {
			return $output;
		}

		$edit_url = admin_url('admin.php?page=wpcf7&post=' . intval($this->current_cf7_id) . '&action=edit');
		$button   = $this->get_edit_button($edit_url, 'CF7', $cap);

		return '<div class="fqe-wrapper">' . $button . $output . '</div>';
	}


	private $current_fluentform_id = 0;

	/**
	 * Output opening wrapper and edit button before Fluent Forms render.
	 *
	 * @param object $form Fluent Forms form object.
	 * @return void
	 */
	public function before_fluentform_render($form)
	{
		$cap = $this->get_capability('fluentform');
		if (! current_user_can($cap)) {
			return;
		}
		$this->current_fluentform_id = $form->id;
		$edit_url = admin_url('admin.php?page=fluent_forms&form_id=' . intval($form->id) . '&route=editor');
		echo '<div class="fqe-wrapper">' . $this->get_edit_button($edit_url, 'Fluent Forms', $cap);
	}

	/**
	 * Close the wrapper div after Fluent Forms render.
	 *
	 * @param object $form Fluent Forms form object.
	 * @return void
	 */
	public function after_fluentform_render($form)
	{
		$cap = $this->get_capability('fluentform');
		if (! current_user_can($cap)) {
			return;
		}
		echo '</div>';
	}

	/**
	 * Wrap Forminator form markup with the quick edit button.
	 *
	 * @param string $html    Form HTML markup.
	 * @param int    $form_id Forminator form ID.
	 * @return string
	 */
	public function forminator_form_markup($html, $form_id)
	{
		$cap = $this->get_capability('forminator');
		if (! current_user_can($cap)) {
			return $html;
		}

		$edit_url = admin_url('admin.php?page=forminator-cform-wizard&id=' . intval($form_id));
		$button   = $this->get_edit_button($edit_url, 'Forminator', $cap);

		return '<div class="fqe-wrapper">' . $button . $html . '</div>';
	}

	private $current_nf_id = 0;

	/**
	 * Output opening wrapper and edit button before Ninja Forms container.
	 *
	 * @param int   $form_id       Ninja Forms form ID.
	 * @param array $form_settings Form settings.
	 * @param array $form_fields   Form fields.
	 * @return void
	 */
	public function before_ninja_form($form_id, $form_settings, $form_fields)
	{
		$cap = $this->get_capability('ninja_forms');
		if (! current_user_can($cap)) {
			return;
		}
		$this->current_nf_id = $form_id;
		$edit_url = admin_url('admin.php?page=ninja-forms&form_id=' . intval($form_id));
		echo '<div class="fqe-wrapper">' . $this->get_edit_button($edit_url, 'Ninja Forms', $cap);
	}

	/**
	 * Close the wrapper div after Ninja Forms container.
	 *
	 * @param int   $form_id       Ninja Forms form ID.
	 * @param array $form_settings Form settings.
	 * @param array $form_fields   Form fields.
	 * @return void
	 */
	public function after_ninja_form($form_id, $form_settings, $form_fields)
	{
		$cap = $this->get_capability('ninja_forms');
		if (! current_user_can($cap)) {
			return;
		}
		echo '</div>';
	}

	/**
	 * Output opening wrapper and edit button before WPForms output.
	 *
	 * @param array  $form_data WPForms form data array.
	 * @param object $form      WPForms form object.
	 * @return void
	 */
	public function before_wpforms($form_data, $form)
	{
		$cap = $this->get_capability('wpforms');
		if (! current_user_can($cap)) {
			return;
		}
		$form_id  = $form_data['id'];
		$edit_url = admin_url('admin.php?page=wpforms-builder&view=fields&form_id=' . intval($form_id));
		echo '<div class="fqe-wrapper">' . $this->get_edit_button($edit_url, 'WPForms', $cap);
	}

	/**
	 * Close the wrapper div after WPForms output.
	 *
	 * @param array  $form_data WPForms form data array.
	 * @param object $form      WPForms form object.
	 * @return void
	 */
	public function after_wpforms($form_data, $form)
	{
		$cap = $this->get_capability('wpforms');
		if (! current_user_can($cap)) {
			return;
		}
		echo '</div>';
	}


	/**
	 * Wrap SureForms block output with the quick edit button.
	 *
	 * @param string $block_content Rendered block HTML.
	 * @param array  $block         Block data including name and attributes.
	 * @return string
	 */
	public function sureforms_render_block($block_content, $block)
	{
		$cap = $this->get_capability('sureforms');
		if (! current_user_can($cap)) {
			return $block_content;
		}

		if ('srfm/form' !== ($block['blockName'] ?? '') && 'sureforms/form' !== ($block['blockName'] ?? '')) {
			return $block_content;
		}

		$form_id = $block['attrs']['id'] ?? ($block['attrs']['formId'] ?? 0);
		if (! $form_id) {
			return $block_content;
		}

		$edit_url = admin_url('post.php?post=' . intval($form_id) . '&action=edit');
		$button   = $this->get_edit_button($edit_url, 'SureForms', $cap);

		return '<div class="fqe-wrapper">' . $button . $block_content . '</div>';
	}
}

Form_Quick_Edit::instance();
