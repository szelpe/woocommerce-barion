<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Gateway_Barion_Return_From_Payment {
 /**
     * @var BarionClient
     */
    private $barion_client;
        /**
     * @var WCGateway
     */
    private $gateway;

    public function __construct($barion_client, $gateway) {
        $this->barion_client = $barion_client;
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
            //wp_redirect($order->get_cancel_order_url_raw());
			wp_redirect($order->get_cancel_order_url());
            exit;
        }

        // IPN callback wasn't received
        if($order->has_status('pending')) {
            $payment_details = $this->barion_client->GetPaymentState($_GET['paymentId']);

            if(!empty($payment_details->Errors)) {
                WC_Gateway_Barion::log('GetPaymentState returned errors. Payment details: ' . json_encode($payment_details));
                return;
            }

            if($payment_details->Status == PaymentStatus::Canceled) {
                //wp_redirect($order->get_cancel_order_url_raw());
				wp_redirect($order->get_cancel_order_url());
                exit;
            }
        }

        wp_redirect($this->gateway->get_return_url($order));
        exit;
    }
}