<?php

class Jnt_My_Account
{

    public function __construct()
    {
        $this->define_hooks();
    }

    protected function define_hooks()
    {
        add_filter('woocommerce_account_orders_columns', [$this, 'add_account_orders_column'], 10, 1);
        add_action('woocommerce_my_account_my_orders_column_custom-column', [$this, 'add_account_orders_column_rows']);
        add_filter('woocommerce_get_order_item_totals', [$this, 'display_jt_fields_on_order_item_totals'], 1000, 3);
    }

    public function add_account_orders_column($columns)
    {
        $order_actions  = $columns['order-actions'];
        unset($columns['order-actions']);

        $columns['custom-column'] = 'J&T Tracking Number';

        $columns['order-actions'] = $order_actions;

        return $columns;
    }

    public function add_account_orders_column_rows($order)
    {
        if ($value = $order->get_meta('jtawb')) {
            echo '<a target="_blank" href="' . esc_url('https://www.jtexpress.my/tracking/' . $value) . '">' . esc_html($value) . '</a>';
        }
    }

    public function display_jt_fields_on_order_item_totals($total_rows, $order, $tax_display)
    {
        $tracking_label = 'J&T Tracking Number :';
        $tracking_value = $order->get_meta('jtawb');

        $new_total_rows  = array();

        foreach ($total_rows as $key => $values) {
            $new_total_rows[$key] = $values;
            if ($key === 'shipping') {
                $new_total_rows['tracking_parcel'] = array(
                    'label' => $tracking_label,
                    'value' => '<a target="_blank" href="https://www.jtexpress.my/tracking/' . $tracking_value . '">' . $tracking_value . '</a>',
                );
            }
        }

        return $new_total_rows;
    }
}
