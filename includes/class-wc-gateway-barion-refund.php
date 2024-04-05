<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Gateway_Barion_Refund {
	 /**
     * @var BarionClient
     */
    private $barion_client;
    /**
     * @var WCGateway
     */
    private $gateway;
    
    /**
     * @var bool
     */
	 public $refund_succeeded;
    public function __construct($barion_client, $gateway) {
        $this->barion_client = $barion_client;
        $this->gateway = $gateway;
        $this->refund_succeeded = false;
    }

    public function refund_order($order, $amount = null, $reason = '') {
		$paymentId = $this->gateway->get_barion_payment_id($order);
        $transaction = new TransactionToRefundModel();
        $transaction->TransactionId = $order->get_transaction_id();
        $transaction->POSTransactionId = $order->get_id();
        $transaction->AmountToRefund = $amount;

        // Comment must be at most 640 character long
        $transaction->Comment = substr($reason, 0, 640);

        
        $refundRequest = new RefundRequestModel($paymentId);
        $refundRequest->AddTransaction($transaction);

        $refundResult = $this->barion_client->RefundPayment($refundRequest);

        if (!$refundResult->RequestSuccessful) {
            $this->refund_succeeded = false;
            WC_Gateway_Barion::log("Refund Failed for transactionId `$transaction->TransactionId`: " . json_encode($refundResult->Errors));

            return $refundResult;
        }

        $this->refund_succeeded = true;
        $this->refund_amount = $refundResult->RefundedTransactions[0]->Total;
        $this->refund_transaction_id = $refundResult->RefundedTransactions[0]->TransactionId;
    }
}
