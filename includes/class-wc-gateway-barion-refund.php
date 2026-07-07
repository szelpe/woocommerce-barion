<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Barion\Models\Payment\TransactionToRefundModel;
use Barion\Models\Refund\RefundRequestModel;

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

    /**
     * @var float
     */
    public $refund_amount;

    /**
     * @var string
     */
    public $refund_transaction_id;

    public function __construct($barion_client, $gateway) {
        $this->barion_client = $barion_client;
        $this->gateway = $gateway;
        $this->refund_succeeded = false;
    }

    public function refund_order($order, $amount = null, $reason = '') {
		$paymentId = $this->gateway->get_barion_payment_id($order);
        // v2.1.0: TransactionToRefundModel has a required constructor
        // (transactionId, posTransactionId, amountToRefund, comment); the old
        // no-arg `new TransactionToRefundModel()` is now an ArgumentCountError.
        // Comment must be at most 640 characters long.
        $transaction = new TransactionToRefundModel(
            $order->get_transaction_id(),
            $order->get_id(),
            (float) $amount,
            mb_substr($reason, 0, 640, 'UTF-8')
        );

        
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
