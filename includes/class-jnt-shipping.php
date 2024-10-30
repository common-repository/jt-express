<?php

class Jnt_Shipping extends WC_Shipping_Method
{

    public $jnt_helper = null;

    public function __construct()
    {

        $this->jnt_helper = new Jnt_Helper();

        $this->id                 = 'jnt';
        $this->method_title       = 'J&T Express';
        $this->method_description = 'To start order to J&T, please fill in your info.';

        $this->availability = 'including';
        $this->countries = array('MY');

        $this->init();

        $this->enabled = isset($this->settings['enabled']) ? $this->settings['enabled'] : 'yes';
        $this->title = isset($this->settings['title']) ? $this->settings['title'] : 'J&T Express';
    }

    public function init()
    {

        $this->init_form_fields();
        $this->init_settings();

        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
    }

    public function init_form_fields()
    {

        $this->form_fields = array(

            'enabled' => array(
                'title' => 'Enable',
                'type' => 'checkbox',
                'description' => 'Enable to display the J&T shipping method in cart.',
                'default' => 'yes'
            ),

            'title' => array(
                'title' => 'Title',
                'type' => 'text',
                'default' => 'J&T Express',
                'custom_attributes' => array('readonly' => 'readonly'),
            ),

            'vipcode' => array(
                'title' => 'VIP Code',
                'type' => 'text',
                'description' => 'Go to J&T Express get your VIP Code.',
            ),

            'apikey' => array(
                'title' => 'API Key',
                'type' => 'password',
                'description' => 'Provided by J&T Express',
            ),

            'name' => array(
                'title' => 'Sender Name',
                'type' => 'text',
                'custom_attributes' => array('required' => 'required'),
            ),

            'phone' => array(
                'title' => 'Sender Phone Number',
                'type' => 'tel',
                'custom_attributes' => array('required' => 'required'),
            ),

            'service' => array(
                'title' => 'Service Type',
                'type' => 'select',
                'options' => array(
                    '1' => 'PICKUP',
                    '6' => 'DROPOFF'
                )
            ),

            'insurance' => array(
                'title' => 'Insurance',
                'type' => 'checkbox',
                'description' => 'Tick this to allow order with insurance option.',
            ),

            'markup' => array(
                'title' => 'Markup',
                'type' => 'number',
                'description' => 'Insert value to markup the shipping rates.'
            )

        );
    }

    public function calculate_shipping($package = array())
    {

        $weight = 0;
        $cost = 0;
        $country = $package["destination"]["country"];
        $postcode = $package["destination"]["postcode"];

        foreach ($package['contents'] as $item_id => $values) {
            $_product = $values['data'];
            $weight = (float)$weight + (float)$_product->get_weight() * (int)$values['quantity'];
        }

        $weight = wc_get_weight($weight, 'kg');

        $cost = $this->jnt_helper->shipping_rate($weight, $postcode);

        $rate = array(
            'id' => $this->id,
            'label' => $this->title,
            'cost' => $cost
        );

        $this->add_rate($rate);
    }
}
