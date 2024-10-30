<?php
 
class Jnt_Settings {

	public function __construct() {

		$this->define_hooks();
	}


	/**
	 * Define hooks
	 */
	protected function define_hooks() {

		add_filter( 'woocommerce_shipping_methods', array( $this, 'add_shipping_method' ) );

	}

	public function add_shipping_method ($methods) {

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-jnt-shipping.php';

		$methods['jnt'] = Jnt_Shipping::class;
		return $methods;
	}

}