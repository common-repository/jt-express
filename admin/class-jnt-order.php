<?php

use PHPMailer\PHPMailer\PHPMailer;

class Jnt_Shipment_Order
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
		add_filter('bulk_actions-woocommerce_page_wc-orders', [$this, 'bulk_actions_create_order'], 30);
		add_filter('handle_bulk_actions-woocommerce_page_wc-orders', [$this, 'handle_bulk_action_create_order'], 10, 3);

		add_filter('manage_woocommerce_page_wc-orders_columns', [$this, 'table_order_number_column_header']);
		add_action('manage_woocommerce_page_wc-orders_custom_column', [$this, 'wc_table_order_number_column_content'], 10, 2);

		add_filter('bulk_actions-edit-shop_order', [$this, 'bulk_actions_create_order'], 30);
		add_filter('handle_bulk_actions-edit-shop_order', [$this, 'handle_bulk_action_create_order'], 10, 3);

		add_filter('manage_edit-shop_order_columns', [$this, 'table_order_number_column_header']);
		add_action('manage_shop_order_posts_custom_column', [$this, 'table_order_number_column_content'], 10, 2);

		add_filter('woocommerce_shop_order_search_fields', [$this, 'waybill_searchable_field'], 10, 1);

		add_action('admin_notices', [$this, 'admin_notices']);
	}

	public function bulk_actions_create_order($actions)
	{

		$actions['jnt_create_order'] = 'Order to J&T';
		$setting = get_option('woocommerce_jnt_settings');

		if (isset($setting['insurance']) && $setting['insurance'] == 'yes') {
			$actions['jnt_create_order_insurance'] = 'Order to J&T with Insurance';
		}

		return $actions;
	}

	public function handle_bulk_action_create_order($redirect_to, $action, $post_ids)
	{
		if ($action !== 'jnt_create_order' && $action !== 'jnt_create_order_insurance') {
			return $redirect_to;
		}

		$processed_ids = array();
		$empty_awb = array();
		$reasons = array();
		$stt = array();
		$result = array();
		$print_ids = array();

		foreach ($post_ids as $post_id) {
			$order = wc_get_order($post_id);
			if (!$order->get_meta('jtawb')) {
				$processed_ids[] = $post_id;
			} else {
				$empty_awb[] = $post_id;
			}
		}

		$nonce = wp_create_nonce('action');

		if (!empty($processed_ids)) {
			if ($action == 'jnt_create_order') {
				$result = $this->jnt_helper->process_order($processed_ids, 0);
			} else if ($action == 'jnt_create_order_insurance') {
				$result = $this->jnt_helper->process_order($processed_ids, 1);
			} else {
				$result = [];
			}

			foreach ($result as $details) {

				$id = $details['id'];
				$awb = "";
				$orderid = "";
				$status = "";
				$code = "";
				$reason = "";

				$detail = json_decode($details['detail']);
				foreach ($detail as $d) {

					$awb = $d[0]->awb_no;
					$orderid = $d[0]->orderid;
					$status = $d[0]->status;
					$code = $d[0]->data->code;
					$reason = $d[0]->reason;
				}

				if ($awb) {
					$print_ids[] = $id;
					$order = wc_get_order($id);
					$order->add_order_note("Tracking number: " . $awb);

					$order->update_meta_data('jtawb', $awb);
					$order->update_meta_data('jtorder', $orderid);
					$order->update_meta_data('jtcode', $code);
					$order->save();

					$order->update_status('jnt-pending');
				} else {
					array_push($reasons, array('id' => $id, 'reason' => $reason));
				}
				array_push($stt, $status);
			}

			if ($print_ids) {
				$this->jnt_helper->process_print_thermal($print_ids);
			}

			$redirect_to = add_query_arg(array(
				'my_nonce_field' => $nonce,
				'acti'	=> 'order',
				'status' => $stt,
				'reasons' => $reasons,
			), $redirect_to);

			return $redirect_to;
		} else {
			$redirect_to = add_query_arg(array(
				'my_nonce_field' => $nonce,
				'acti'	=> 'error',
				'msg'	=> 'Already Order'
			), $redirect_to);

			return $redirect_to;
		}
	}

	public function table_order_number_column_header($columns)
	{
		$columns['waybill'] = 'J&T Waybill';
		return $columns;
	}

	public function table_order_number_column_content($columns, $post_id)
	{

		switch ($columns) {
			case 'waybill':
				$waybill = get_post_meta($post_id, 'jtawb', true);
				echo esc_html($waybill);
				break;

			case 'order':
				$order = get_post_meta($post_id, 'jtorder', true);
				echo esc_html($order);
				break;

			case 'cancel':
				$cancel = get_post_meta($post_id, 'cancel', true);
				if ($cancel) {
					foreach ($cancel as $key => $value) {
						echo esc_html($value) . "<br/>";
					}
				}
				break;
		}
	}

	public function wc_table_order_number_column_content($columns, $order)
	{
		if ($columns === 'waybill') {
			$jtawb = $order->get_meta('jtawb');

			if ($jtawb) {
				echo '<a target="_blank" href="' . esc_url('https://www.jtexpress.my/tracking/' . $jtawb) . '">' . esc_html($jtawb) . '</a>';
			}
		}
	}

	public function waybill_searchable_field($meta_keys)
	{
		$meta_keys[] = 'jtawb';
		return $meta_keys;
	}

	public function admin_notices()
	{
		if (!isset($_GET['acti'])) {
			return;
		}

		if (isset($_GET['my_nonce_field'])) {
			$nonce = sanitize_text_field(wp_unslash($_GET['my_nonce_field']));
			if (!wp_verify_nonce($nonce, 'action')) {
				wp_die('Security check failed.');
			}
		} else {
			return;
		}

		if ($_GET['acti'] === 'order' && isset($_GET['reasons'])) {
			$reasons = wp_unslash($_GET['reasons']);

			$reason_messages = [
				'S10' => 'Duplicate Order Number',
				'S11' => 'Duplicate Waybill Number',
				'S12' => "Order Already Pick Up Can't Cancel",
				'S13' => 'API Key Wrong',
				'S14' => "Order Number can't Empty",
				'S15' => "Waybill Number can't Empty",
				'S17' => 'Number does not meet our rules',
				'S18' => "Sender Address can't Empty",
				'S19' => "Receiver Address can't Empty",
				'S29' => "Sender Postcode can't Empty",
				'S30' => "Receiver Postcode can't Empty",
				'S31' => 'Sender Postcode not Exist',
				'S32' => 'Receiver Postcode not Exist',
				'S34' => 'Customer/Vip Code not Exist',
				'S35' => "Sender Name can't Empty",
				'S36' => "Sender Phone can't Empty",
				'S37' => "Receiver Name can't Empty",
				'S38' => "Receiver Phone can't Empty",
				'S40' => "Weight can't Empty",
				'S41' => "Payment Type can't Empty",
				'S42' => 'Wrong Payment Type',
				'S43' => "Service Type can't Empty",
			];

			foreach ($reasons as $value) {
				$reason_code = sanitize_text_field($value['reason']);
				$res = $reason_messages[$reason_code] ?? esc_html($reason_code);

				echo '<div class="notice notice-warning is-dismissible">';
				echo '<p>' . esc_html('#' . sanitize_text_field($value['id']) . ' ' . $res) . '</p>';
				echo '</div>';
			}
		} elseif (isset($_GET['msg'])) {
			$message = sanitize_text_field(wp_unslash($_GET['msg']));
			echo '<div id="message" class="updated fade">';
			echo '<p>' . esc_html($message) . '</p>';
			echo '</div>';
		}
	}
}
