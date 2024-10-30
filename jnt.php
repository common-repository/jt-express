<?php

/**
Plugin Name: J&T Express Malaysia
Description: WooCommerce integration for J&T Express Malaysia.
Author: woocs
Version: 2.0.16
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
 */

if (!defined('ABSPATH')) {
	die;
}

define('JNT_VERSION', '2.0.16');
define('JNT_PLUGIN_DIR', plugin_dir_path(__FILE__));

function activate_jnt()
{
	require_once JNT_PLUGIN_DIR . 'includes/class-jnt-activator.php';
	Jnt_Activator::activator();
}
register_activation_hook(__FILE__,  'activate_jnt');

function deactivate_jnt()
{
	require_once JNT_PLUGIN_DIR . 'includes/class-jnt-deactivate.php';
	Jnt_Deactivator::deactivate();
}
register_deactivation_hook(__FILE__, 'deactivate_jnt');

require JNT_PLUGIN_DIR . 'includes/class-jnt.php';

$plugin = Jnt::init();
$plugin->InitPlugin();
