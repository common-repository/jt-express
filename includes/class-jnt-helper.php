<?php

class Jnt_Helper
{

	public $jnt_api = null;

	public function __construct()
	{
		$this->jnt_api = new Jnt_Api();
	}

	public function process_order($ids, $insuranceFlag)
	{
		$merge = array();
		$setting = get_option('woocommerce_jnt_settings');
		foreach ($ids as $id) {
			$order = wc_get_order($id);

			$sender = array(
				'sender_name' => $setting['name'],
				'sender_phone' => $setting['phone'],
				'sender_addr' => implode(" ", array(
					get_option('woocommerce_store_address'),
					get_option('woocommerce_store_address_2'),
					get_option('woocommerce_store_city'),
					get_option('woocommerce_store_postcode')
				)),
				'sender_zip'  => get_option('woocommerce_store_postcode'),
				'cuscode'	  => $setting['vipcode'],
				'password'	  => $setting['apikey'],
			);

			$shipping_phone = (!empty($order->get_shipping_phone())) ? $order->get_shipping_phone() : $order->get_billing_phone();

			if (strpos($shipping_phone, '/') !== false) {
				$receiverphone = explode("/", $shipping_phone);
				$receiverphone = $receiverphone[0];
			} else {
				$receiverphone = $shipping_phone;
			}

			$receiver = array(
				'receiver_name' => $order->get_formatted_shipping_full_name(),
				'receiver_phone' => $receiverphone,
				'receiver_addr'	=> implode(" ", array(
					$order->shipping_address_1,
					$order->shipping_address_2,
					$order->shipping_city,
					$order->shipping_postcode
				)),
				'receiver_zip' 	=> $order->get_shipping_postcode(),
			);

			$weight_unit = get_option('woocommerce_weight_unit');
			$kg = 1000;
			$weight = 0;
			$item_name = '';

			if (sizeof($order->get_items()) > 0) {
				foreach ($order->get_items() as $item) {
					if ($item['product_id'] > 0) {
						$_product = $order->get_product_from_item($item);
						if (!$_product->is_virtual()) {
							if (is_numeric($_product->get_weight()) && is_numeric($item['qty'])) {
								$weight += ($_product->get_weight() * $item['qty']);
							}
							$item_name .= $item['qty'] . ' X ' . $item['name'] . ', ';
						}
					}
				}
			}

			if ($weight == '0') {
				$weight = 0.1;
			} else {
				if ($weight_unit == 'kg') {
					$weight = $weight;
				} else if ($weight_unit == 'g') {
					$weight = $weight / $kg;
					if ($weight <= 0.01) {
						$weight = 0.01;
					}
				}
			}

			$cod = 0;
			$subtotal = $order->get_subtotal();
			$total = $order->get_total();
			if ('cod' == $order->get_payment_method()) {
				$cod = $total;
			}

			$items = array(
				'id'	=> $id,
				'orderid'	=> date('ymdHi') . str_pad($id, 6, 0, STR_PAD_LEFT),
				'weight' => $weight,
				'item' 	 => substr($item_name, 0, -2),
				'item_value' => $subtotal,
				'qty'	 => $order->get_item_count(),
				'payType' => 'PP_PM',
				'goodsType'	=> 'PARCEL',
				'servicetype' => $setting['service'],
				'expresstype' => 'EZ',
				'goodsdesc' => '#' . $id . ' - ' . $order->customer_message,
				'cod' => $cod,
				'offerFeeFlag' => $insuranceFlag
			);

			array_push($merge, array_merge($sender, $receiver, $items));
		}

		return $this->jnt_api->order($merge);
	}

	public function process_print_thermal($ids)
	{
		$awbs = array();
		$setting = get_option('woocommerce_jnt_settings');
		$cuscode = $setting['vipcode'];
		foreach ($ids as $key => $id) {
			$order = wc_get_order($id);
			$awbs[] = $order->get_meta('jtawb');
		}
		$awb = implode(",", $awbs);

		$this->jnt_api->print($cuscode, $awb);
	}

	public function process_print($awbs)
	{

		$setting = get_option('woocommerce_jnt_settings');
		$cuscode = $setting['vipcode'];
		$awbs = implode(",", $awbs);

		$this->jnt_api->printA4($cuscode, $awbs);
	}

	public function cancel_order($ids)
	{

		$awbs = array();

		foreach ($ids as $key => $id) {

			$order = wc_get_order($id);

			$infos = array(
				'id'	=> $id,
				'awb'	=> $order->get_meta('jtawb'),
				'order' => $order->get_meta('jtorder')
			);

			array_push($awbs, $infos);
		}

		return $this->jnt_api->cancel($awbs);
	}

	public function shipping_rate($weight, $postcode)
	{

		$receiver_zip = $postcode;
		$sender_zip = get_option('woocommerce_store_postcode');

		$shipping = get_option('woocommerce_jnt_settings');
		$cuscode = $shipping['vipcode'];
		$pass = $shipping['apikey'];
		$markup = $shipping['markup'];

		$fee = $this->jnt_api->calculate($weight, $sender_zip, $receiver_zip, $cuscode, $pass);
		if ($markup) {
			$fee = $fee + $markup;
		}

		return $fee;
	}
}
