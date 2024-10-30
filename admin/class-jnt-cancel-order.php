<?php

class Jnt_Cancel
{

	public $jnt_helper = null;

	public function __construct()
	{

		$this->jnt_helper = new Jnt_Helper();
		$this->define_hooks();
	}

	protected function define_hooks()
	{

		add_filter('bulk_actions-woocommerce_page_wc-orders', [$this, 'bulk_actions_cancel_order'], 30);
		add_filter('handle_bulk_actions-woocommerce_page_wc-orders', [$this, 'handle_bulk_action_cancel_order'], 10, 3);

		add_filter('bulk_actions-edit-shop_order', [$this, 'bulk_actions_cancel_order'], 30);
		add_filter('handle_bulk_actions-edit-shop_order', [$this, 'handle_bulk_action_cancel_order'], 10, 3);
	}

	public function bulk_actions_cancel_order($actions)
	{
		$actions['jnt_cancel_order'] = 'Cancel J&T Order';

		return $actions;
	}

	public function handle_bulk_action_cancel_order($redirect_to, $action, $post_ids)
	{
		if ($action !== 'jnt_cancel_order') {
			return $redirect_to;
		}

		$processed_ids = array();
		$reasons = array();

		foreach ($post_ids as $post_id) {
			$order = wc_get_order($post_id);
			if (!$order->get_meta('jtawb')) {
			} else {
				$processed_ids[] = $post_id;
			}
		}

		$nonce = wp_create_nonce('action');

		if (!empty($processed_ids)) {
			$result = $this->jnt_helper->cancel_order($processed_ids);

			foreach ($result as $details) {

				$id = $details['id'];

				$detail = json_decode($details['detail'], true);

				$awb_no = $detail['details'][0]['awb_no'];
				$status = $detail['details'][0]['status'];
				$reason = $detail['details'][0]['reason'];


				if ($status == 'success') {
					$order = wc_get_order($id);
					$order->delete_meta_data('jtawb');
					$order->delete_meta_data('jtorder');
					$order->delete_meta_data('jtcode');
					$order->save();

					$order->update_status('cancelled');
				} else {
					array_push($reasons, array('id' => $id, 'reason' => $reason));
				}
			}

			$redirect_to = add_query_arg(array(
				'my_nonce_field' => $nonce,
				'acti' => 'cancel',
				'msg' => $status,
				'reasons' => $reasons,
			), $redirect_to);

			return $redirect_to;
		} else {
			$redirect_to = add_query_arg(array(
				'my_nonce_field' => $nonce,
				'acti' => 'error',
				'msg' => 'Not yet Order',
			), $redirect_to);

			return $redirect_to;
		}
	}
}
