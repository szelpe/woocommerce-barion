<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Barion\Enumerations\PaymentStatus;

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

        // The IPN (server-to-server) callback may be delayed, blocked or missing.
        // When the customer returns from a successful payment we verify the state
        // directly here and finalize the order as a fallback, so it is not left
        // unpaid if the IPN never arrives.
        if($order->has_status(array('pending', 'on-hold'))) {
            $payment_details = $this->barion_client->GetPaymentState($_GET['paymentId']);

            if(!empty($payment_details->Errors)) {
                WC_Gateway_Barion::log('GetPaymentState returned errors. Payment details: ' . json_encode($payment_details));
                return;
            }

            // Safeguard 1: the returned paymentId must match the one stored for
            // this order, otherwise an order could be finalized with a foreign
            // paymentId passed in the query string.
            if($_GET['paymentId'] != $this->gateway->get_barion_payment_id($order)) {
                WC_Gateway_Barion::log('Return blocked, Barion paymentId does not match. Order: ' . $order->get_id());

                wp_redirect($this->gateway->get_return_url($order));
                exit;
            }

            if($payment_details->Status == PaymentStatus::Canceled) {
                //wp_redirect($order->get_cancel_order_url_raw());
				wp_redirect($order->get_cancel_order_url());
                exit;
            }

            if($payment_details->Status == PaymentStatus::Succeeded) {
                // Safeguard 2: idempotency. Skip if the payment was already
                // finalized (e.g. the IPN won the race), so payment_complete()
                // does not run twice for the same order.
                if(!$order->has_status(array('processing', 'completed'))
                    && !$order->meta_exists('_barion_payment_close')) {
                    $order->add_order_note(__('Payment succeeded via Barion (confirmed on return).', 'pay-via-barion-for-woocommerce'));
                    $order->update_meta_data('_barion_payment_close', 1);
                    $order->save();

                    $this->gateway->payment_complete($order, $this->find_transaction_id($payment_details, $order));
                }
            }
        }

        wp_redirect($this->gateway->get_return_url($order));
        exit;
    }

    function find_transaction_id($payment_details, $order) {
        foreach($payment_details->Transactions as $transaction) {
            if($transaction->POSTransactionId == $order->get_id()) {
                return $transaction->TransactionId;
            }
        }

        return '';
    }
}