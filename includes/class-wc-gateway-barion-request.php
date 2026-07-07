<?php

if (!defined('ABSPATH')) {
    exit;
}

use Barion\Models\Payment\PaymentTransactionModel;
use Barion\Models\Payment\PreparePaymentRequestModel;
use Barion\Models\Common\ItemModel;
use Barion\Models\ThreeDSecure\ShippingAddressModel;
use Barion\Models\ThreeDSecure\BillingAddressModel;
use Barion\Models\ThreeDSecure\PayerAccountInformationModel;
use Barion\Models\ThreeDSecure\PurchaseInformationModel;
use Barion\Enumerations\Currency;
use Barion\Enumerations\PaymentType;
use Barion\Enumerations\FundingSourceType;
use Barion\Enumerations\UILocale;
use Barion\Enumerations\ThreeDSecure\ChallengePreference;
use Barion\Enumerations\ThreeDSecure\PurchaseType;
use Barion\Enumerations\ThreeDSecure\AvailabilityIndicator;
use Barion\Enumerations\ThreeDSecure\AccountCreationIndicator;
use Barion\Enumerations\ThreeDSecure\AccountChangeIndicator;
use Barion\Enumerations\ThreeDSecure\PasswordChangeIndicator;
use Barion\Enumerations\ThreeDSecure\SuspiciousActivityIndicator;

class WC_Gateway_Barion_Request {
    /**
     * @var WC_Gateway_Barion_Profile_Monitor
     */
    private $profile_monitor;

    /**
     * @var BarionClient
     */
    private $barion_client;

    /**
     * @var WC_Gateway_Barion
     */
    private $gateway;

    /**
     * @var WC_Order
     */
    private $order;

    /**
     * @var \Barion\Models\Payment\PreparePaymentResponseModel
     */
    private $payment;

    public $is_prepared;

    public function __construct($barion_client, $gateway, $profile_monitor) {
        $this->is_prepared = false;
        $this->barion_client = $barion_client;
        $this->gateway = $gateway;
        $this->profile_monitor = $profile_monitor;
    }

    /**
     * @param WC_Order $order
     */
    public function prepare_payment($order) {
        $this->order = $order;
        $transaction = new PaymentTransactionModel();
        $transaction->POSTransactionId = $order->get_id();
        $transaction->Payee = $this->gateway->payee;
        $transaction->Total = $this->round($order->get_total(), $order->get_currency());
        $transaction->Comment = "";

        $this->prepare_items($order, $transaction);

        $paymentRequest = new PreparePaymentRequestModel();
        $paymentRequest->GuestCheckout = true;
        $paymentRequest->PaymentType = PaymentType::Immediate;
        $paymentRequest->FundingSources = array(FundingSourceType::All);
        $paymentRequest->PaymentRequestId = $order->get_id();
        $paymentRequest->PayerHint = $this->nullIfEmpty($order->get_billing_email());
        $paymentRequest->PayerPhoneNumber = $this->clean_phone_number($order->get_billing_phone());
        $paymentRequest->CardHolderNameHint = $this->nullIfEmpty($order->get_formatted_billing_full_name());
        $paymentRequest->Locale = $this->get_barion_locale();
        $paymentRequest->OrderNumber = $order->get_order_number();
        $this->set_shipping_address($order, $paymentRequest);
        $this->set_billing_address($order, $paymentRequest);
        $paymentRequest->RedirectUrl = add_query_arg('order-id', $order->get_id(), WC()->api_request_url('WC_Gateway_Barion_Return_From_Payment'));
        $paymentRequest->CallbackUrl = WC()->api_request_url('WC_Gateway_Barion');
        // v2.1.0: Currency is a backed enum, not a raw string. The gateway only
        // reaches this code for a supported currency (USD/EUR/HUF/CZK/PLN), all
        // of which exist as Currency enum cases.
        $paymentRequest->Currency = Currency::from($order->get_currency());
        $paymentRequest->AddTransaction($transaction);

        $this->set_payer_account_information($order, $paymentRequest);
        $this->set_purchase_information($order, $paymentRequest);
        $paymentRequest->ChallengePreference = ChallengePreference::NoPreference;

        $paymentRequest = apply_filters('woocommerce_barion_prepare_payment', $paymentRequest, $order);

        $this->payment = $this->barion_client->PreparePayment($paymentRequest);

        do_action('woocommerce_barion_prepare_payment_called', $this->payment, $order);

        if ($this->payment->RequestSuccessful) {
            $this->gateway->set_barion_payment_id($order, $this->payment->PaymentId);
            $this->is_prepared = true;
        } else {
            WC_Gateway_Barion::log('PreparePayment failed. Errors array: ' . json_encode($this->payment->Errors));
        }
    }

    protected function prepare_items($order, $transaction) {
        $calculated_total = 0;

        foreach ($order->get_items(array('line_item', 'fee', 'shipping', 'coupon')) as $item_id => $item) {
            $itemModel = new ItemModel();
            // Read order-item fields through getters instead of ArrayAccess: the
            // WC_Order_Item::offsetGet legacy array access is deprecated since
            // WooCommerce 4.4.0 and raises a notice on every read (e.g. coupon
            // lines). The getters return the same values, so amounts are unchanged.
            $itemModel->Name = $item->get_name();
            $itemModel->Description = $itemModel->Name;
$translated_text = __('piece', 'pay-via-barion-for-woocommerce');
$itemModel->Unit = mb_substr(empty($translated_text) ? 'piece' : (string)$translated_text, 0, 50, 'UTF-8');
            $quantity = $item->get_quantity();
            $itemModel->Quantity = empty($quantity) ? 1 : $quantity;

            // ItemModel exposes UnitPrice (not Price) in the Barion SDK; the old
            // `->Price` was an undeclared dynamic property that never reached the
            // API and is a fatal-worthy deprecation on the v2.1.0 typed models.
            $itemModel->UnitPrice = $order->get_item_subtotal($item, true);
            $itemModel->ItemTotal = $order->get_line_subtotal($item, true);

            if ('coupon' === $item->get_type()) {
                $itemModel->Name = __('Coupon', 'woocommerce') . ' (' . $item->get_name() . ')';

                $discount_amount = wc_get_order_item_meta($item_id, 'discount_amount');
                $discount_amount_tax = wc_get_order_item_meta($item_id, 'discount_amount_tax');

                if (!empty($discount_amount_tax)) {
                    $discount_amount += $discount_amount_tax;
                }

                $itemModel->UnitPrice = -1 * $discount_amount;
                $itemModel->ItemTotal = -1 * $discount_amount;
                $itemModel->SKU = '';
            } elseif ('shipping' === $item->get_type()) {
                $shipping_cost = (float) wc_get_order_item_meta($item_id, 'cost');
                $shipping_taxes = wc_get_order_item_meta($item_id, 'taxes');
                
                // Handle shipping taxes safely
                $tax_amount = 0;
                if (!empty($shipping_taxes) && is_array($shipping_taxes)) {
                    // Check if it's a multidimensional array with 'total' key
                    if (isset($shipping_taxes['total']) && is_array($shipping_taxes['total'])) {
                        // Sum the 'total' taxes
                        foreach ($shipping_taxes['total'] as $tax) {
                            $tax_amount += (float) $tax;
                        }
                    } elseif (!isset($shipping_taxes['total']) && !isset($shipping_taxes['subtotal'])) {
                        // It's a simple array, sum directly
                        foreach ($shipping_taxes as $tax) {
                            if (is_numeric($tax)) {
                                $tax_amount += (float) $tax;
                            }
                        }
                    }
                }
                
                $shipping_cost = floatval($shipping_cost) + floatval($tax_amount);
                $itemModel->UnitPrice = $shipping_cost;
                $itemModel->ItemTotal = $shipping_cost;
                $itemModel->SKU = '';
            } elseif ('fee' === $item->get_type()) {
                $itemModel->UnitPrice = $order->get_item_total($item, true);
                $itemModel->ItemTotal = $order->get_line_total($item, true);
                $itemModel->SKU = '';
            } else {
                $product = $item->get_product();

                if ($product) {
                    if ($product->is_type('variable')) {
                        $itemModel->Name .= ' (' . $product->get_formatted_variation_attributes(true) . ')';
                    }

                    $itemModel->SKU = $product->get_sku();
                }
            }

            $transaction->AddItem($itemModel);
        }
    }

    function get_barion_locale() {
        switch (get_locale()) {
            case "hu_HU":
                return UILocale::HU;
            case "de_DE":
                return UILocale::DE;
            case "sl_SI":
                return UILocale::SL;
            case "sk_SK":
                return UILocale::SK;
            case "fr_FR":
                return UILocale::FR;
            case "cs_CZ":
                return UILocale::CZ;
            case "el_GR":
                return UILocale::GR;
            default:
                return UILocale::EN;
        }
    }

    public function get_redirect_url() {
        if (!$this->is_prepared)
            throw new Exception('`prepare_payment` should have been called before `get_redirect_url`.');

        return apply_filters('woocommerce_barion_get_redirect_url', $this->payment->PaymentRedirectUrl, $this->order, $this->payment);
    }

    /**
     * Round prices.
     * @param string $price
     * @param string $currency
     * @return string
     */
    protected function round($price, $currency) {
        $precision = 2;
        if (!$this->currency_has_decimals($currency)) {
            $precision = 0;
        }

        // Emit a plain numeric string (no thousands separator) so it coerces
        // cleanly into the SDK's typed `float $Total`. number_format's default
        // grouping (e.g. "9,990") is a non-numeric string and now throws a
        // TypeError on the v2.1.0 typed models.
        return number_format(round($price, $precision), $precision, '.', '');
    }

    /**
     * Check if currency has decimals.
     * @param string $currency
     * @return bool
     */
    protected function currency_has_decimals($currency) {
        if (in_array($currency, array('HUF'))) {
            return false;
        }

        return true;
    }

    /**
     * @param WC_Order $order
     * @param PreparePaymentRequestModel $paymentRequest
     */
    public function set_shipping_address($order, $paymentRequest) {
        $shipping_country = $order->get_shipping_country();

        if (empty($shipping_country)) {
            $paymentRequest->ShippingAddress = null;
            return;
        }

        $shippingAddress = new ShippingAddressModel();

        $shippingAddress->Country = $shipping_country;

        // Region code is not compatible with WooCommerce
        $shippingAddress->Region = null;
        $shippingAddress->City = $order->get_shipping_city();
        $shippingAddress->Zip = $order->get_shipping_postcode();
        $shippingAddress->Street = mb_substr($order->get_shipping_address_1(), 0, 50, 'UTF-8');
        $shippingAddress->Street2 = mb_substr($order->get_shipping_address_2(), 0, 50, 'UTF-8');
        $shippingAddress->Street3 = "";
        $shippingAddress->FullName = $order->get_formatted_shipping_full_name();

        $paymentRequest->ShippingAddress = $shippingAddress;
    }

    /**
     * @param WC_Order $order
     * @param PreparePaymentRequestModel $paymentRequest
     */
    public function set_billing_address($order, $paymentRequest) {
        $billing_country = $order->get_billing_country();

        if (empty($billing_country)) {
            $paymentRequest->BillingAddress = null;
            return;
        }

        $billingAddress = new BillingAddressModel();

        $billingAddress->Country = $billing_country;

        // Region code is not compatible with WooCommerce
        $billingAddress->Region = null;
        $billingAddress->City = $order->get_billing_city();
        $billingAddress->Zip = $order->get_billing_postcode();
        $billingAddress->Street = mb_substr($order->get_billing_address_1(), 0, 50, 'UTF-8');
        $billingAddress->Street2 = mb_substr($order->get_billing_address_2(), 0, 50, 'UTF-8');
        $billingAddress->Street3 = "";

        $paymentRequest->BillingAddress = $billingAddress;
    }

    private function set_payer_account_information($order, $paymentRequest) {
        $payerAccountInfo = new PayerAccountInformationModel();
        $paymentRequest->PayerAccountInformation = $payerAccountInfo;

        $user = wp_get_current_user();
        if (!$user->exists()) {
            $payerAccountInfo->AccountCreationIndicator = AccountCreationIndicator::NoAccount;

            return;
        }

        $customer = new WC_Customer($user->ID);

        $payerAccountInfo->AccountId = $customer->get_id();
        $payerAccountInfo->AccountCreated = $customer->get_date_created()->format(DateTime::ISO8601);
        $payerAccountInfo->AccountCreationIndicator = $this->get_account_creation_indicator($customer);

        $payerAccountInfo->AccountLastChanged = $customer->get_date_modified()->format(DateTime::ISO8601);
        $payerAccountInfo->AccountChangeIndicator = $this->get_account_change_indicator($customer);
        $this->set_password_changed_indicator($payerAccountInfo);

//        -- Hard to get
//        $payerAccountInfo->PurchasesInTheLastSixMonths = 6;
//        $payerAccountInfo->ShippingAddressAdded = $now;
//        $payerAccountInfo->ShippingAddressUsageIndicator = ShippingAddressUsageIndicator::ThisTransaction;
//        $payerAccountInfo->TransactionalActivityPerDay = 1;
//        $payerAccountInfo->TransactionalActivityPerYear = 100;
//
//        -- Not applicable
//        $payerAccountInfo->PaymentMethodAdded = $now;
//        $payerAccountInfo->PaymentMethodIndicator = PaymentMethodIndicator::ThisTransaction;
//        $payerAccountInfo->ProvisionAttempts = 1;

        $payerAccountInfo->SuspiciousActivityIndicator = SuspiciousActivityIndicator::NoSuspiciousActivityObserved;
    }

    /**
     * @param $order WC_Order
     * @param $paymentRequest
     */
    function set_purchase_information($order, $paymentRequest) {
        $user = wp_get_current_user();

        $purchaseInfo = new PurchaseInformationModel();
        $purchaseInfo->DeliveryEmailAddress = $user->user_email;
        $this->set_availability_indicator($purchaseInfo, $order);
        $purchaseInfo->PurchaseType = PurchaseType::GoodsAndServicePurchase;

        // Not applicable. The enum-typed fields (ShippingAddressIndicator,
        // DeliveryTimeframe, ReOrderIndicator) stay at their Unspecified default
        // set by the model constructor; assigning null to them would be a
        // TypeError on the v2.1.0 non-nullable enum properties.
        $purchaseInfo->PreOrderDate = null;
        $purchaseInfo->RecurringExpiry = null;
        $purchaseInfo->RecurringFrequency = 0;
        $purchaseInfo->GiftCardPurchase = null;

        $paymentRequest->PurchaseInformation = $purchaseInfo;
    }

    /**
     * @param $customer WC_Customer
     * @return string
     * @throws Exception
     */
    private function get_account_creation_indicator($customer) {
        if ($this->profile_monitor->was_profile_just_created()) {
            return AccountCreationIndicator::CreatedDuringThisTransaction;
        }

        $date = $customer->get_date_created();
        return AccountCreationIndicator::from($this->get_indicator($date));
    }

    /**
     * @param $customer WC_Customer
     * @return string
     * @throws Exception
     */
    private function get_account_change_indicator($customer) {
        if ($this->profile_monitor->was_profile_just_updated()) {
            return AccountChangeIndicator::ChangedDuringThisTransaction;
        }

        $date = $customer->get_date_modified();
        return AccountChangeIndicator::from($this->get_indicator($date));
    }

    /**
     * @param PayerAccountInformationModel $payerAccountInfo
     * @throws Exception
     */
    private function set_password_changed_indicator(PayerAccountInformationModel $payerAccountInfo) {
        $date_password_changed = $this->profile_monitor->get_date_password_changed();

        if (empty($date_password_changed)) {
            $payerAccountInfo->PasswordChangeIndicator = PasswordChangeIndicator::NoChange;
            return;
        }

        $payerAccountInfo->PasswordLastChanged = $date_password_changed->format(DateTime::ISO8601);
        $payerAccountInfo->PasswordChangeIndicator = PasswordChangeIndicator::from($this->get_indicator($date_password_changed));
    }

    /**
     * Returns the shared 3DS "time since" bucket as a backing-string value.
     * Callers convert it to their specific enum (AccountCreationIndicator /
     * AccountChangeIndicator / PasswordChangeIndicator) via ::from(), because the
     * v2.1.0 SDK types each of those properties with a distinct enum while they
     * share the same case values.
     *
     * @param DateTime $date
     * @return string
     * @throws Exception
     */
    private function get_indicator($date) {
        if ($date < new DateTime('@' . strtotime('-60 day'))) {
            return 'MoreThan60Days';
        }

        if ($date < new DateTime('@' . strtotime('-30 day'))) {
            return 'Between30And60Days';
        }

        return 'LessThan30Days';
    }

    private function clean_phone_number($phone_number) {
        $phone_number = str_replace(['+', ' ', '-'], '', $phone_number);

        if(empty($phone_number)) {
            return null;
        }

        return $phone_number;
    }

    /**
     * @param PurchaseInformationModel $purchaseInfo
     * @param WC_Order $order
     */
    private function set_availability_indicator($purchaseInfo, $order) {
        $purchaseInfo->AvailabilityIndicator = AvailabilityIndicator::MerchandiseAvailable;

        /**
         * @var $item WC_Order_Item_Product
         */
        foreach ($order->get_items(array('line_item')) as $item_id => $item) {
            /**
             * @var $product WC_Product
             */
            $product = $item->get_product();

            if (!$product) {
                continue;
            }

            if ($product->is_on_backorder()) {
                $purchaseInfo->AvailabilityIndicator = AvailabilityIndicator::FutureAvailability;
            }
        }
    }

    private function nullIfEmpty($value) {
        if (empty($value)) {
            return null;
        }

        return $value;
    }
}
