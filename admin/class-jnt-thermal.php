<?php

class Jnt_Thermal
{

	public $jnt_helper = null;

	public function __construct()
	{

		$this->jnt_helper = new Jnt_Helper();
		$this->define_hooks();
	}

	/**
	 * Define hooks
	 */
	protected function define_hooks()
	{

		add_filter('bulk_actions-woocommerce_page_wc-orders', [$this, 'bulk_actions_consignment_note_thermal'], 30);
		add_filter('handle_bulk_actions-woocommerce_page_wc-orders', [$this, 'handle_bulk_action_consignment_note_thermal'], 10, 3);

		add_filter('bulk_actions-edit-shop_order', [$this, 'bulk_actions_consignment_note_thermal'], 30);
		add_filter('handle_bulk_actions-edit-shop_order', [$this, 'handle_bulk_action_consignment_note_thermal'], 10, 3);
	}

	public function bulk_actions_consignment_note_thermal($actions)
	{

		$actions['jnt_consignment_note_thermal'] = 'Print J&T Consignment Note (Thermal)';

		return $actions;
	}

	public function handle_bulk_action_consignment_note_thermal($redirect_to, $action, $post_ids)
	{

		if ($action !== 'jnt_consignment_note_thermal') {
			return $redirect_to;
		}

		$processed_ids = array();
		$empty_awb = array();

		foreach ($post_ids as $post_id) {
			$order = wc_get_order($post_id);
			if (!$order->get_meta('jtawb')) {
			} else {
				$processed_ids[] = $post_id;
			}
		}

		if (!empty($processed_ids)) {
			$result = $this->jnt_helper->process_print_thermal($processed_ids);
		} else {

			$redirect_to = add_query_arg(array(
				'acti' => 'error',
				'msg' => 'Not yet Order',
			), $redirect_to);

			return $redirect_to;
		}
	}
}
