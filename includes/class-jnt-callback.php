<?php

class Jnt_Callback
{
    function __construct()
    {
        add_action('wp_loaded', [$this, 'listen_callback']);
    }

    public function listen_callback()
    {
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
            $url = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $url = esc_url_raw($url);

            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                return;
            }

            $webhook = wp_parse_url($url);
            if (!isset($webhook['path'])) {
                return;
            }

            $route = '/' . basename($webhook['path']);

            if ($route == '/jnt-webhook') {
                $headers = getallheaders();

                if (isset($headers['apiAccount'])) {
                    $request = file_get_contents('php://input');
                    parse_str($request, $data);
                    $bizContent = $data['bizContent'] ?? [];

                    if (!$bizContent) {
                        $response = [
                            'code' => "0",
                            'message' => "fail",
                            'data' => "Invalid Request!"
                        ];

                        return wp_send_json($response);
                    } else {

                        return $this->callback_webhook($bizContent);
                    }
                } else {
                    $response = [
                        'code' => "0",
                        'message' => "fail",
                        'data' => "Invalid Account!"
                    ];

                    return wp_send_json($response);
                }
            }
        }
    }

    public function callback_webhook($request)
    {
        $bizContent = json_decode($request, true);
        $tracking_number = sanitize_text_field($bizContent['billCode']);
        $jnt_status_code = sanitize_text_field($bizContent['details'][0]['scanTypeCode']);

        return $this->jnt_process_webhook($tracking_number, $jnt_status_code);
    }

    public function jnt_process_webhook($tracking_number, $jnt_status_code)
    {
        if ($tracking_number == "630002864925") {
            $response = [
                'code' => "1",
                'message' => "success",
                'data' => "SUCCESS"
            ];
            return wp_send_json($response);
        }

        $args = [
            'meta_key' => 'jtawb',
            'meta_value' => $tracking_number,
            'meta_compare' => '=',
            'return' => 'ids'
        ];

        $orders = wc_get_orders($args);
        if ($orders) {
            $order = wc_get_order($orders[0]);

            switch ($jnt_status_code) {
                case 10:
                    $note = 'J&T: Order has been Pickuped';
                    $order->update_status('jnt-pickup');
                    $order->add_order_note($note);
                    break;

                case 20:
                case 30:
                    $note = 'J&T: Order is In Transit';
                    $order->update_status('jnt-in-transit');
                    $order->add_order_note($note);
                    break;

                case 94:
                    $note = 'J&T: Order is out for Delivery and will be Delivered shortly';
                    $order->update_status('jnt-out-delivery');
                    $order->add_order_note($note);
                    break;

                case 100:
                    $note = 'J&T: Order has been Delivered';
                    $order->update_status('completed');
                    $order->add_order_note($note);
                    break;

                case 172:
                    $note = 'J&T: Return Order';
                    $order->update_status('jnt-return');
                    $order->add_order_note($note);
                    break;

                case 173:
                    $note = 'J&T: Order has been Returned';
                    $order->update_status('completed');
                    $order->add_order_note($note);
                    break;

                case 200:
                    $note = 'J&T: Order has been Collected';
                    $order->update_status('jnt-pickup');
                    $order->add_order_note($note);
                    break;

                default:
                    break;
            }

            $response = [
                'code' => '1',
                'message' => 'success',
                'data' => 'SUCCESS',
            ];
            return wp_send_json($response);
        } else {
            $response = [
                'code' => "0",
                'message' => "fail",
                'data' => "Order Not Found!"
            ];
            return wp_send_json($response);
        }
    }
}
