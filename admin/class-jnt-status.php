<?php

class Jnt_Status
{

    function __construct()
    {
        $this->define_hooks();
    }

    public function define_hooks()
    {
        add_action('init', [$this, 'register_jnt_order_status']);
        add_action('wc_order_statuses', [$this, 'register_jnt_order_statuses']);

        add_action('woocommerce_order_status_changed', [$this, 'order_status_changed_notification'], 10, 3);
        add_action('woocommerce_email_before_order_table', [$this, 'custom_content_for_customer_shipping_email'], 10, 4);
    }

    public function register_jnt_order_status()
    {
        register_post_status('wc-jnt-pending', array(
            'label'                     => 'J&T Pending Pickup',
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop('J&T Pending Pickup (%s)', 'J&T Pending Pickup (%s)', 'jt-express')
        ));

        register_post_status('wc-jnt-pickup', array(
            'label'                     => 'J&T Pickup',
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop('J&T Pickup (%s)', 'J&T Pickup (%s)', 'jt-express')
        ));

        register_post_status('wc-jnt-in-transit', array(
            'label'                     => 'J&T In Transit',
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop('J&T In Transit (%s)', 'J&T In Transit (%s)', 'jt-express')
        ));

        register_post_status('wc-jnt-out-delivery', array(
            'label'                     => 'J&T Out For Deliveery',
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop('J&T Out For Delivery (%s)', 'J&T Out For Delivery (%s)', 'jt-express')
        ));

        register_post_status('wc-jnt-return', array(
            'label'                     => 'J&T Return',
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop('J&T Return (%s)', 'J&T Return (%s)', 'jt-express')
        ));
    }

    public function register_jnt_order_statuses($order_statuses)
    {
        $new_order_statuses = array();

        foreach ($order_statuses as $key => $status) {

            $new_order_statuses[$key] = $status;

            if ('wc-processing' === $key) {
                $new_order_statuses['wc-jnt-pending'] = 'J&T Pending Pickup';
                $new_order_statuses['wc-jnt-pickup'] = 'J&T Pickup';
                $new_order_statuses['wc-jnt-in-transit'] = 'J&T In Transit';
                $new_order_statuses['wc-jnt-out-delivery'] = 'J&T Out For Delivery';
                $new_order_statuses['wc-jnt-return'] = 'J&T Return';
            }
        }

        return $new_order_statuses;
    }

    public function order_status_changed_notification($order_id, $status_from, $status_to)
    {
        $mailer = WC()->mailer()->get_emails();

        if ($status_to == 'jnt-pending') {
            $subject = 'Your Order is Pending to Pickup';
            $heading = 'Your Order is Pending to Pickup';
        }

        if ($status_to == 'jnt-pickup') {
            $subject = 'Your Order has been Pickuped';
            $heading = 'Your Order has been Pickuped';
        }

        if ($status_to == 'jnt-out-delivery') {
            $subject = 'Your Order is out for Delivery';
            $heading = 'Your Order is out for Delivery';
        }

        $mailer['WC_Email_Customer_Processing_Order']->settings['subject'] = $subject;
        $mailer['WC_Email_Customer_Processing_Order']->settings['heading'] = $heading;
        $mailer['WC_Email_Customer_Processing_Order']->trigger($order_id);
    }

    public function custom_content_for_customer_shipping_email($order, $sent_to_admin, $plain_text, $email)
    {
        if ($email->id === 'customer_processing_order' && $order->has_status('jnt-pending')) {
            echo '<h4>Your order is waiting for the courier to pick up.</h4>';
        }

        if ($email->id === 'customer_processing_order' && $order->has_status('jnt-pickup')) {
            echo '<h4>Your order has been Pickuped by the courier.</h4>';
        }

        if ($email->id === 'customer_processing_order' && $order->has_status('jnt-out-delivery')) {
            echo '<h4>Your order is out for Delivery and will be Delivered shortly.</h4>';
        }
    }
}
