<?php

class Jnt_Admin
{

	public function __construct()
	{

		$this->jnt_helper = new Jnt_Helper();
		$this->define_hooks();
	}

	public function define_hooks()
	{

		add_action('plugins_loaded', [$this, 'check_woocommerce_activated']);
		add_action('admin_init', [$this, 'check_plugin_version']);
	}

	/**
	 * Check plugin version
	 */
	public function check_plugin_version()
	{
		$plugin_slug = 'jt-express/jnt.php';
		$plugins = get_plugins();
		$current_version = $plugins[$plugin_slug]['Version'];
		$latest_version = '2.0.16';

		if (version_compare($current_version, $latest_version, '<')) {
			add_action('admin_notices', [$this, 'display_update_notice']);
		}
	}

	public function display_update_notice()
	{
?>
		<div class="notice notice-warning is-dismissible">
			<p>Your J&T Express Malaysia plugin is outdated! Please update to the latest version.</p>
		</div>
	<?php
	}

	/**
	 * Check if Woocommerce installed
	 */
	public function check_woocommerce_activated()
	{
		if (defined('WC_VERSION')) {
			return;
		}

		add_action('admin_notices', [$this, 'notice_woocommerce_required']);
	}

	/**
	 * Admin error notifying user that Woocommerce is required
	 */
	public function notice_woocommerce_required()
	{
	?>
		<div class="notice notice-error">
			<p>Jnt requires WooCommerce to be installed and activated!</p>
		</div>
<?php
	}

	/**
	 * Add menu
	 */
}
