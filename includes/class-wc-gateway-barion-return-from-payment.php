<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Gateway_Barion_Return_From_Payment {

    public function __construct($gateway) {
        $this->gateway = $gateway;
        add_action('woocommerce_api_wc_gateway_barion_return_from_payment', array($this, 'redirect_to_order_received'));
    }

    public function redirect_to_order_received() {
        $order = new WC_Order($_GET['order-id']);

        if(empty($order)) {
            WC_Gateway_Barion::log('Invalid Order Id: `' . $_GET['order-id'] . '`');

            return;
        }

        if($order->has_status('cancelled')) {
            wp_redirect($order->get_cancel_order_url_raw());
            exit;
        }

        wp_redirect($this->gateway->get_return_url($order));
        exit;
    }
}
