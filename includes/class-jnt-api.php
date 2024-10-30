<?php

class Jnt_Api
{

	public function order($shipment_info)
	{
		$url = "https://ylstandard.jtexpress.my/blibli/order/createOrder";
		$sign = 'AKe62df84bJ3d8e4b1hea2R45j11klsb';
		$res = array();

		foreach ($shipment_info as $value) {
			$data = [
				'detail' => [
					[
						"username" => 'WORDPRESS',
						"api_key" => 'WORD12',
						"cuscode" => $value['cuscode'],
						'password' => $value['password'],
						"orderid" => $value['orderid'],
						"shipper_name" => $value['sender_name'],
						"shipper_addr" => $value['sender_addr'],
						"shipper_contact" => $value['sender_name'],
						"shipper_phone"	=> $value['sender_phone'],
						"sender_zip" => $value['sender_zip'],
						"receiver_name"	=> $value['receiver_name'],
						"receiver_addr"	=> $value['receiver_addr'],
						"receiver_phone" => $value['receiver_phone'],
						"receiver_zip" => $value['receiver_zip'],
						"qty" => $value['qty'],
						"weight" => $value['weight'],
						"servicetype" => $value['servicetype'],
						"item_name" => mb_substr($value['item'], 0, 200, 'UTF-8'),
						"goodsType" => $value['goodsType'],
						"goodsvalue" => $value['item_value'],
						"goodsdesc" => $value['goodsdesc'],
						"expressType" => $value['expresstype'],
						"payType" => $value['payType'],
						"cod" => $value['cod'],
						"offerFeeFlag" => $value['offerFeeFlag']
					]
				]
			];

			$json_data = wp_json_encode($data);
			$signature = base64_encode(md5($json_data . $sign));
			$post = array(
				'data_param' => $json_data,
				'data_sign'	=> $signature,
			);

			$res[] = array('id' => $value['id'], 'detail' => self::curl($post, $url));
		}

		return $res;
	}

	public function cancel($awbs)
	{

		$url = 'https://ylstandard.jtexpress.my/blibli/order/cancelOrder';

		$key = 'AKe62df84bJ3d8e4b1hea2R45j11klsb';

		$res = array();

		foreach ($awbs as $value) {
			$data = array(
				'username' => 'WORDPRESS',
				'api_key' => 'WORD12',
				'awb_no' => $value['awb'],
				'orderid' => $value['order'],
				'remark' => ''
			);

			$json_data = wp_json_encode($data);
			$signature = base64_encode(md5($json_data . $key));
			$post = array(
				'data_param' => $json_data,
				'data_sign'  => $signature
			);

			$res[] = array('id' => $value['id'], 'detail' => self::curl($post, $url));
		}

		return $res;
	}

	public static function curl($post, $url)
	{
		$r = wp_remote_post($url, array('sslverify' => false, 'body' => $post));

		return wp_remote_retrieve_body($r);
	}

	public function printA4($cuscode, $awbs)
	{
		$url = 'https://ylstandard.jtexpress.my/jandt_report_web/print/A4facelistAction!print.action';

		$data = array(
			'billcode' => $awbs,
			'account' =>  'WORDPRESS',
			'password' => 'WORD12',
			'customercode' => $cuscode,
		);

		$post = array('logistics_interface' => wp_json_encode($data), 'data_digest' => md5($awbs), 'msg_type' => '1');

		$result = wp_remote_post($url, array('body' => $post));
		$pdf_content = wp_remote_retrieve_body($result);

		header('Content-Type: application/pdf');
		header('Content-Disposition: attachment; filename="' . substr($awbs, 0, 100) . '.pdf"');
		header('Content-Length: ' . strlen($pdf_content));

		echo $pdf_content;
		exit;
	}

	public function print($cuscode, $awbs)
	{
		$url = 'https://ylstandard.jtexpress.my/jandt_report_web/print/facelistAction!print.action';

		$data = array(
			'billcode' => $awbs,
			'account' =>  'WORDPRESS',
			'password' => 'WORD12',
			'customercode' => $cuscode,
		);

		$post = array('logistics_interface' => wp_json_encode($data), 'data_digest' => md5($awbs), 'msg_type' => '1');

		$result = wp_remote_post($url, array('body' => $post));
		$pdf_content = wp_remote_retrieve_body($result);

		header('Content-Type: application/pdf');
		header('Content-Disposition: attachment; filename="' . substr($awbs, 0, 100) . '.pdf"');
		header('Content-Length: ' . strlen($pdf_content));
		ob_clean();
		flush();
		echo $pdf_content;
		exit;
	}

	public function calculate($weight, $sender_zip, $receiver_zip, $cuscode, $pass)
	{

		$url = 'https://ylstandard.jtexpress.my/open/api/express/getQuotedPriceByCustomer';
		$key = '0080fb7482c0e8f774f4ed8cb550c4ba56a5beed73595b52f62c531042f2ff27';

		$data = [
			'customerCode'	=> $cuscode,
			'password'		=> $pass,
			'expressType'  => 'EZ',
			'goodsType' => 'PARCEL',
			'pcs' => 1,
			'receiverPostcode'	=> $receiver_zip,
			'senderPostcode'	=> $sender_zip,
			'weight'	=> $weight,
		];

		$json_data = wp_json_encode($data);
		$signature = hash("sha256", ($json_data . $key));

		$header = array(
			'Content-Type' => 'application/json',
			'account' => 'WORDPRESS',
			'sign' => $signature
		);

		$response = wp_remote_post($url, array('sslverify' => false, 'headers' => $header, 'body' => $json_data));
		$res = wp_remote_retrieve_body($response);

		$res = json_decode($res, true);
		return $res['data']['shippingFee'] ?? 0;
	}
}
