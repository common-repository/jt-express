<?php

class Jnt
{

	private static $initiated;

	public static function init()
	{
		if (!isset(self::$initiated)) {
			self::$initiated = new self();
		}
		return self::$initiated;
	}

	public function InitPlugin()
	{

		require_once JNT_PLUGIN_DIR . 'admin/class-jnt-admin.php';
		require_once JNT_PLUGIN_DIR . 'admin/class-jnt-setting.php';
		require_once JNT_PLUGIN_DIR . 'admin/class-jnt-order.php';
		require_once JNT_PLUGIN_DIR . 'admin/class-jnt-consignment-note.php';
		require_once JNT_PLUGIN_DIR . 'admin/class-jnt-thermal.php';
		require_once JNT_PLUGIN_DIR . 'admin/class-jnt-my-account.php';
		require_once JNT_PLUGIN_DIR . 'admin/class-jnt-status.php';
		require_once JNT_PLUGIN_DIR . 'admin/class-jnt-cancel-order.php';
		require_once JNT_PLUGIN_DIR . 'includes/class-jnt-helper.php';
		require_once JNT_PLUGIN_DIR . 'includes/class-jnt-api.php';
		require_once JNT_PLUGIN_DIR . 'includes/class-jnt-callback.php';


		new Jnt_Admin();
		new Jnt_Settings();
		new Jnt_Shipment_Order();
		new Jnt_Consignment_Note();
		new Jnt_Thermal();
		new Jnt_My_Account();
		new Jnt_Status();
		new JNT_Cancel();
		new Jnt_Helper();
		new Jnt_Api();
		new Jnt_Callback();
	}
}
