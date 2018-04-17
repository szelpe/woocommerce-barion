<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Gateway_Barion_IPN_Handler {
    public function __construct($barion_client, $gateway) {
        $this->barion_client = $barion_client;
        $this->gateway = $gateway;
        add_action('woocommerce_api_wc_gateway_barion', array($this, 'check_barion_ipn'));
    }

    public function check_barion_ipn() {
        if(empty($_GET) || empty($_GET['paymentId']))
            return;


        $payment_details = $this->barion_client->GetPaymentState($_GET['paymentId']);

        if(!empty($payment_details->Errors)) {
            WC_Gateway_Barion::log('GetPaymentState returned errors. Payment details: ' . json_encode($payment_details));

            return;
        }

        $order = new WC_Order($payment_details->PaymentRequestId);

        if(empty($order)) {
            WC_Gateway_Barion::log('Invalid PaymentRequestId: ' . $_GET['paymentId'] . '. Payment details: ' . json_encode($payment_details));

            return;
        }

        $order->add_order_note(__('Barion callback received.', 'pay-via-barion-for-woocommerce') . ' paymentId: "' . $_GET['paymentId'] . '"');

        if($order->has_status(array('processing', 'completed'))) {
            $order->add_order_note(__('Barion callback ignored as the payment was already completed.', 'pay-via-barion-for-woocommerce'));
            return;
        }

        if($payment_details->Status == PaymentStatus::Succeeded) {
            if($order->has_status('completed')) {
                return;
            }

            $order->add_order_note(__('Payment succeeded via Barion.', 'pay-via-barion-for-woocommerce'));
            $this->gateway->payment_complete($order, $this->find_transaction_id($payment_details, $order));

            return;
        }

        if($payment_details->Status == PaymentStatus::Canceled) {
            $order->update_status('cancelled', __('Payment canceled via Barion.', 'pay-via-barion-for-woocommerce'));

            return;
        }

        if($payment_details->Status == PaymentStatus::Expired) {
            $order->update_status('cancelled', __('Payment is expired (customer progressed to Barion, but then left the page without paying).', 'pay-via-barion-for-woocommerce'));

            return;
        }

        $order->update_status('failed', __('Payment failed via Barion.', 'pay-via-barion-for-woocommerce'));
        WC_Gateway_Barion::log('Payment failed. Payment details: ' . json_encode($payment_details));
    }

    function find_transaction_id($payment_details, $order) {
        foreach($payment_details->Transactions as $transaction) {
            if($transaction->POSTransactionId == $order->get_id()) {
                return $transaction->TransactionId;
            }
        }
    }
}
