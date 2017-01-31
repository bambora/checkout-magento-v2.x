<?php
/**
 * 888                             888
 * 888                             888
 * 88888b.   8888b.  88888b.d88b.  88888b.   .d88b.  888d888  8888b.
 * 888 "88b     "88b 888 "888 "88b 888 "88b d88""88b 888P"       "88b
 * 888  888 .d888888 888  888  888 888  888 888  888 888     .d888888
 * 888 d88P 888  888 888  888  888 888 d88P Y88..88P 888     888  888
 * 88888P"  "Y888888 888  888  888 88888P"   "Y88P"  888     "Y888888
 *
 * @category    Online Payment Gatway
 * @package     Bambora_Online
 * @author      Bambora Online
 * @copyright   Bambora (http://bambora.com)
 */
namespace Bambora\Online\Model\Method\Checkout;

use Magento\Framework\DataObject;
use Bambora\Online\Model\Api\CheckoutApi;
use Bambora\Online\Model\Api\CheckoutApiModels;
use \Magento\Sales\Model\Order\Payment\Transaction;
use Bambora\Online\Helper\BamboraConstants;

class Payment extends \Bambora\Online\Model\Method\AbstractPayment implements \Bambora\Online\Model\Method\IPayment
{
    const METHOD_CODE = 'bambora_checkout';
    const METHOD_REFERENCE = 'bamboraCheckoutReference';

    protected $_code = self::METHOD_CODE;

    protected $_infoBlockType = 'Bambora\Online\Block\Info\View';

    /**
     * Payment Method feature
     */
    protected $_isGateway                   = true;
    protected $_canCapture                  = true;
    protected $_canCapturePartial           = true;
    protected $_canRefund                   = true;
    protected $_canRefundInvoicePartial     = true;
    protected $_canVoid                     = true;
    protected $_canDelete                   = true;

    /**
     * @var string
     */
    private $_apiKey;

    /**
     * Retrieve an api key for the Bambora Api
     *
     * @return string
     */
    public function getApiKey()
    {
        if (!$this->_apiKey) {
            $storeId = $this->getStoreManager()->getStore()->getId();
            $this->_apiKey = $this->_bamboraHelper->generateCheckoutApiKey($storeId);
        }

        return $this->_apiKey;
    }

    /**
     * Retrieve allowed PaymentCardIds
     *
     * @param $currency
     * @param $amount
     * @return array
     */
    public function getPaymentCardIds($currency = null, $amount = null)
    {
        if (is_null($currency)) {
            $currency = $this->getQuote()->getBaseCurrencyCode();
        }

        if (is_null($amount)) {
            $amount = $this->getQuote()->getBaseGrandTotal();
        }

        $minorUnits = $this->_bamboraHelper->getCurrencyMinorunits($currency);
        $amountMinorunits = $this->_bamboraHelper->convertPriceToMinorUnits($amount, $minorUnits);

        /** @var \Bambora\Online\Model\Api\Checkout\Merchant */
        $merchantApi = $this->_bamboraHelper->getCheckoutApi(CheckoutApi::API_MERCHANT);

        $paymentTypeResponse = $merchantApi->getPaymentTypes($currency, $amountMinorunits, $this->getApiKey());

        $message = "";
        if ($this->_bamboraHelper->validateCheckoutApiResult($paymentTypeResponse, $this->getQuote()->getId(), false, $message)) {
            $paymentCardIdsArray = array();

            foreach ($paymentTypeResponse->paymentCollections as $payment) {
                foreach ($payment->paymentGroups as $group) {
                    $paymentCardIdsArray[] = $group->id;
                }
            }
            return $paymentCardIdsArray;
        } else {
            $this->_messageManager->addError(__("The allowed payment types could not be loaded").": ".$message);
            return null;
        }
    }

    /**
     * Get Bambora Checkout payment window
     *
     * @param \Magento\Sales\Model\Order
     * @return \Bambora\Online\Model\Api\Checkout\Response\Checkout
     */
    public function getPaymentWindow($order)
    {
        if (!isset($order)) {
            return null;
        }

        $paymentRequest = $this->createPaymentRequest($order);

        /** @var \Bambora\Online\Model\Api\Checkout\Checkout */
        $checkoutProvider = $this->_bamboraHelper->getCheckoutApi(CheckoutApi::API_CHECKOUT);
        $checkoutResponse = $checkoutProvider->setCheckout($paymentRequest, $this->getApiKey());

        $message = "";
        if (!$this->_bamboraHelper->validateCheckoutApiResult($checkoutResponse, $order->getIncrementId(), false, $message)) {
            $this->_messageManager->addError(__("The payment window could not be retrived"));
            $this->_messageManager->addError(__("Bambora Checkout error") . ': ' . $message);
            $checkoutResponse = null;
        }

        return $checkoutResponse;
    }

    /**
     * Create the Bambora Checkout Request object
     *
     * @param \Magento\Sales\Model\Order $order
     * @return \Bambora\Online\Model\Api\Checkout\Request\Checkout
     */
    public function createPaymentRequest($order)
    {
        $billingAddress  = $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress();
        if ($order->getBillingAddress()->getEmail()) {
            $email = $order->getBillingAddress()->getEmail();
        } else {
            $email = $order->getCustomerEmail();
        }

        $storeId = $order->getStoreId();
        $minorUnits = $this->_bamboraHelper->getCurrencyMinorUnits($order->getBaseCurrencyCode());
        $totalAmountMinorUnits = $this->_bamboraHelper->convertPriceToMinorUnits($order->getBaseTotalDue(), $minorUnits);

        /** @var \Bambora\Online\Model\Api\Checkout\Request\Checkout */
        $checkoutRequest = $this->_bamboraHelper->getCheckoutApiModel(CheckoutApiModels::REQUEST_CHECKOUT);
        $checkoutRequest->instantcaptureamount = $this->_bamboraHelper->getBamboraCheckoutConfigData(BamboraConstants::INSTANT_CAPTURE, $storeId) == 0 ? 0 : $totalAmountMinorUnits;
        $checkoutRequest->language = $this->_bamboraHelper->getShopLocalCode();
        $checkoutRequest->paymentwindowid = $this->getConfigData(BamboraConstants::PAYMENT_WINDOW_ID, $storeId);

        /** @var \Bambora\Online\Model\Api\Checkout\Request\Models\Customer */
        $bamboraCustomer = $this->_bamboraHelper->getCheckoutApiModel(CheckoutApiModels::REQUEST_MODEL_CUSTOMER);
        $bamboraCustomer->email = $email;

        /** @var \Bambora\Online\Model\Api\Checkout\Request\Models\Order */
        $bamboraOrder = $this->_bamboraHelper->getCheckoutApiModel(CheckoutApiModels::REQUEST_MODEL_ORDER);
        $bamboraOrder->currency = $order->getBaseCurrencyCode();
        $bamboraOrder->ordernumber = $order->getIncrementId();
        $bamboraOrder->total = $totalAmountMinorUnits;
        $bamboraOrder->vatamount = $this->_bamboraHelper->convertPriceToMinorUnits($order->getBaseTaxAmount(), $minorUnits);

        /** @var \Bambora\Online\Model\Api\Checkout\Request\Models\Url */
        $bamboraUrl = $this->_bamboraHelper->getCheckoutApiModel(CheckoutApiModels::REQUEST_MODEL_URL);
        $bamboraUrl->accept = $this->_urlBuilder->getUrl('bambora/checkout/accept', ['_secure' => $this->_request->isSecure()]);
        $bamboraUrl->decline =  $this->_urlBuilder->getUrl('bambora/checkout/cancel', ['_secure' => $this->_request->isSecure()]);

        /** @var \Bambora\Online\Model\Api\Checkout\Request\Models\Callback */
        $bamboraCallback = $this->_bamboraHelper->getCheckoutApiModel(CheckoutApiModels::REQUEST_MODEL_CALLBACK);
        $bamboraCallback->url = $this->_urlBuilder->getUrl('bambora/checkout/callback', ['_secure' => $this->_request->isSecure()]);
        $bamboraUrl->callbacks = array();
        $bamboraUrl->callbacks[] = $bamboraCallback;
        $bamboraUrl->immediateredirecttoaccept = $this->getConfigData(BamboraConstants::IMMEDIATEREDI_REDIRECT_TO_ACCEPT, $storeId);
        $checkoutRequest->url = $bamboraUrl;

        if ($billingAddress) {
            $bamboraCustomer->phonenumber = $billingAddress->getTelephone();
            $bamboraCustomer->phonenumbercountrycode = $billingAddress->getCountryId();

            $bamboraBillingAddress = $this->_bamboraHelper->getCheckoutApiModel(CheckoutApiModels::REQUEST_MODEL_ADDRESS);
            $bamboraBillingAddress->att = "";
            $bamboraBillingAddress->city = $billingAddress->getCity();
            $bamboraBillingAddress->country = $billingAddress->getCountryId();
            $bamboraBillingAddress->firstname = $billingAddress->getFirstname();
            $bamboraBillingAddress->lastname = $billingAddress->getLastname();
            $bamboraBillingAddress->street = $billingAddress->getStreet()[0];
            $bamboraBillingAddress->zip = $billingAddress->getPostcode();

            $bamboraOrder->billingaddress = $bamboraBillingAddress;
        }

        if ($shippingAddress) {
            $bamboraShippingAddress = $this->_bamboraHelper->getCheckoutApiModel(CheckoutApiModels::REQUEST_MODEL_ADDRESS);
            $bamboraShippingAddress->att = "";
            $bamboraShippingAddress->city = $shippingAddress->getCity();
            $bamboraShippingAddress->country = $shippingAddress->getCountryId();
            $bamboraShippingAddress->firstname = $shippingAddress->getFirstname();
            $bamboraShippingAddress->lastname = $shippingAddress->getLastname();
            $bamboraShippingAddress->street = $shippingAddress->getStreet()[0];
            $bamboraShippingAddress->zip = $shippingAddress->getPostcode();

            $bamboraOrder->shippingaddress = $bamboraShippingAddress;
        }

        $checkoutRequest->customer = $bamboraCustomer;

        $bamboraOrderLines = array();
        $items = $order->getAllVisibleItems();
        $lineNumber = 1;
        foreach ($items as $item) {
            $bamboraOrderLines[] = $this->createInvoiceLine(
                $item->getDescription(),
                $item->getSku(),
                $lineNumber,
                floatval($item->getQtyOrdered()),
                $item->getName(),
                $item->getBaseRowTotal(),
                $item->getBaseTaxAmount(),
                $order->getBaseCurrencyCode(),
                $item->getTaxPercent(),
                $item->getBaseDiscountAmount());

            $lineNumber++;
        }

        //Add shipping line
        $bamboraOrderLines[] = $this->createInvoiceLine(
           $order->getShippingDescription(),
            __("Shipping"),
            $lineNumber++,
            1,
            __("Shipping"),
             $order->getBaseShippingAmount(),
            $order->getBaseShippingTaxAmount(),
            $order->getBaseCurrencyCode(),
            $order->getBaseShippingDiscountAmount());

        $bamboraOrder->lines = $bamboraOrderLines;
        $checkoutRequest->order = $bamboraOrder;

        return $checkoutRequest;
    }

    /**
     * Create Invoice Line
     *
     * @param mixed $description
     * @param mixed $id
     * @param mixed $lineNumber
     * @param mixed $quantity
     * @param mixed $text
     * @param mixed $totalPrice
     * @param mixed $totalPriceVatAmount
     * @param mixed $currencyCode
     * @return \Bambora\Online\Model\Api\Checkout\Request\Models\Line
     */
    public function createInvoiceLine($description, $id, $lineNumber, $quantity, $text, $totalPrice, $totalPriceVatAmount, $currencyCode, $taxPercent = null, $discountAmount = 0)
    {
        $minorUnits = $this->_bamboraHelper->getCurrencyMinorunits($currencyCode);

        /** @var \Bambora\Online\Model\Api\Checkout\Request\Models\Line */
        $line = $this->_bamboraHelper->getCheckoutApiModel(CheckoutApiModels::REQUEST_MODEL_LINE);
        $line->description = isset($description) ? $description : $text;
        $line->id = $id;
        $line->linenumber = $lineNumber;
        $line->quantity = $quantity;
        $line->text = $text;
        $line->totalprice = $this->_bamboraHelper->convertPriceToMinorUnits(($totalPrice - $discountAmount), $minorUnits);
        $line->totalpriceinclvat = $this->_bamboraHelper->convertPriceToMinorUnits((($totalPrice + $totalPriceVatAmount) - $discountAmount), $minorUnits);
        $line->totalpricevatamount = $this->_bamboraHelper->convertPriceToMinorUnits($totalPriceVatAmount, $minorUnits);
        $line->unit = __("pcs.");
        if (!isset($taxPercent)) {
            $vat = $totalPriceVatAmount > 0 && $totalPrice > 0  ? floatval($totalPriceVatAmount / $totalPrice * 100) : 0;
            $line->vat = $vat;
        } else {
            $line->vat = floatval($taxPercent);
        }

        return $line;
    }

    /**
     * Capture payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        /** @var \Magento\Sales\Model\Order */
        $order = $payment->getOrder();

        try {
            $transactionId = $payment->getAdditionalInformation($this::METHOD_REFERENCE);

            $isInstantCapure = $payment->getAdditionalInformation(BamboraConstants::INSTANT_CAPTURE);

            if ($isInstantCapure === true) {
                $payment->setTransactionId($transactionId . '-' . BamboraConstants::INSTANT_CAPTURE)
                    ->setIsTransactionClosed(true)
                    ->setParentTransactionId($transactionId);

                return $this;
            }

            if (!$this->canOnlineAction($payment)) {
                throw new \Exception(__("The capture action could not, be processed online. Please enable remote payment processing from the module configuration"));
            }

            $currency = $order->getBaseCurrencyCode();
            $minorunits = $this->_bamboraHelper->getCurrencyMinorunits($currency);

            /** @var \Bambora\Online\Model\Api\Checkout\Request\Capture */
            $captureRequest = $this->_bamboraHelper->getCheckoutApiModel(CheckoutApiModels::REQUEST_CAPTURE);
            $captureRequest->amount = $this->_bamboraHelper->convertPriceToMinorUnits($amount, $minorunits);
            $captureRequest->currency = $currency;

            //Only add invoice lines if it is a full capture
            $invoiceLines = null;
            if (floatval($amount) === floatval($order->getBaseTotalDue())) {
                $invoiceLines = $this->getCaptureInvoiceLines($order);
            }

            $captureRequest->invoicelines = $invoiceLines;

            /** @var \Bambora\Online\Model\Api\Checkout\Transaction */
            $transactionProvider = $this->_bamboraHelper->getCheckoutApi(CheckoutApi::API_TRANSACTION);
            $captureResponse = $transactionProvider->capture($transactionId, $captureRequest, $this->getApiKey());
            $message = "";
            if (!$this->_bamboraHelper->validateCheckoutApiResult($captureResponse, $transactionId, true, $message)) {
                throw new \Exception(__("The capture action failed.") . ' - '.$message);
            }
            $transactionoperationId = "";
            foreach ($captureResponse->transactionOperations as $transactionoperation) {
                $transactionoperationId = $transactionoperation->id;
            }

            $payment->setTransactionId($transactionoperationId . '-' . Transaction::TYPE_CAPTURE)
                    ->setIsTransactionClosed(true)
                    ->setParentTransactionId($transactionId);

            return $this;
        } catch (\Exception $ex) {
            $errorMessage = "({$order->getIncrementId()}) " . $ex->getMessage();
            $this->_messageManager->addError($errorMessage);
            throw $ex;
        }
    }

    /**
     * Refund payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        /** @var \Magento\Sales\Model\Order */
        $order = $payment->getOrder();
        $id = $order->getIncrementId();
        try {
            $creditMemo = $payment->getCreditmemo();
            $id = $creditMemo->getInvoice()->getIncrementId();

            if (!$this->canOnlineAction($payment)) {
                throw new \Exception(__("The refund action could not, be processed online. Please enable remote payment processing from the module configuration"));
            }

            $transactionId = $payment->getAdditionalInformation($this::METHOD_REFERENCE);

            $currency = $order->getBaseCurrencyCode();
            $minorunits = $this->_bamboraHelper->getCurrencyMinorunits($currency);

            /** @var \Bambora\Online\Model\Api\Checkout\Request\Credit */
            $creditRequest = $this->_bamboraHelper->getCheckoutApiModel(CheckoutApiModels::REQUEST_CREDIT);
            $creditRequest->amount = $this->_bamboraHelper->convertPriceToMinorUnits($amount, $minorunits);
            $creditRequest->currency = $currency;
            $creditRequest->invoicelines = $this->getRefundInvoiceLines($creditMemo, $order);

            $transactionProvider = $this->_bamboraHelper->getCheckoutApi(CheckoutApi::API_TRANSACTION);
            $creditResponse = $transactionProvider->credit($transactionId, $creditRequest, $this->getApiKey());
            $message = "";
            if (!$this->_bamboraHelper->validateCheckoutApiResult($creditResponse, $transactionId, true, $message)) {
                throw new \Exception(__('The refund action failed.') . ' - '.$message);
            }
            $transactionoperationId = "";
            foreach ($creditResponse->transactionOperations as $transactionoperation) {
                $transactionoperationId = $transactionoperation->id;
            }
            $payment->setTransactionId($transactionoperationId . '-' . Transaction::TYPE_REFUND)
                    ->setIsTransactionClosed(true)
                    ->setParentTransactionId($transactionId);

            return $this;
        } catch (\Exception $ex) {
            $errorMessage = "({$id}) " . $ex->getMessage();
            $this->_messageManager->addError($errorMessage);
            throw $ex;
        }
    }

    /**
     * Cancel payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return $this
     */
    public function cancel(\Magento\Payment\Model\InfoInterface $payment)
    {
        try {
            $this->void($payment);
            $this->_messageManager->addSuccess(__("The payment have been voided").' ('.$payment->getOrder()->getIncrementId().')');
        } catch (\Exception $ex) {
            $this->_messageManager->addError($ex->getMessage());
        }

        return $this;
    }

    /**
     * Void payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return $this
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        /** @var \Magento\Sales\Model\Order */
        $order = $payment->getOrder();
        try {
            if (!$this->canOnlineAction($payment)) {
                throw new \Exception(__("The void action could not, be processed online. Please enable remote payment processing from the module configuration"));
            }

            $transactionId = $payment->getAdditionalInformation($this::METHOD_REFERENCE);

            $transactionProvider = $this->_bamboraHelper->getCheckoutApi(CheckoutApi::API_TRANSACTION);
            $deleteResponse = $transactionProvider->delete($transactionId, $this->getApiKey());
            $message = "";
            if (!$this->_bamboraHelper->validateCheckoutApiResult($deleteResponse, $transactionId, true, $message)) {
                throw new \Exception(__("The void action failed.") . ' - '.$message);
            }
            $transactionoperationId = "";
            foreach ($deleteResponse->transactionOperations as $transactionoperation) {
                $transactionoperationId = $transactionoperation->id;
            }
            $payment->setTransactionId($transactionoperationId . '-' . Transaction::TYPE_VOID)
                    ->setIsTransactionClosed(true)
                    ->setParentTransactionId($transactionId);

            $this->cancelSurchargeFeeItem($payment);

            return $this;
        } catch (\Exception $ex) {
            $errorMessage = "({$order->getIncrementId()}) " . $ex->getMessage();
            $this->_messageManager->addError($errorMessage);
            throw $ex;
        }
    }

    /**
     * Get Bambora Checkout Transaction
     *
     * @param mixed $transactionId
     * @param string &$message
     * @return \Bambora\Online\Model\Api\Checkout\Response\Models\Transaction
     */
    public function getTransaction($transactionId, &$message)
    {
        try {
            if (!$this->getConfigData(BamboraConstants::REMOTE_INTERFACE)) {
                return null;
            }

            /** @var \Bambora\Online\Model\Api\Checkout\Merchant */
            $merchantProvider = $this->_bamboraHelper->getCheckoutApi(CheckoutApi::API_MERCHANT);
            $transactionResponse = $merchantProvider->getTransaction($transactionId, $this->getApiKey());

            if (!$this->_bamboraHelper->validateCheckoutApiResult($transactionResponse, $transactionId, true, $message)) {
                return null;
            }

            return $transactionResponse->transaction;
        } catch (\Exception $ex) {
            return null;
        }
    }

    /**
     * Get Refund Invoice Lines
     *
     * @param \Magento\Sales\Model\Order\Invoice $creditMemo
     * @param \Magento\Sales\Model\Order $order
     * @return \Bambora\Online\Model\Api\Checkout\Request\Models\Line[]
     */
    private function getCaptureInvoiceLines($order)
    {
        $invoice = $order->getInvoiceCollection()->getLastItem();
        $invoiceItems = $order->getAllVisibleItems();

        $lines = array();
        $feeItem = null;
        foreach ($invoiceItems as $item) {
            if ($item->getSku() === BamboraConstants::BAMBORA_SURCHARGE) {
                $feeItem = $this->createInvoiceLineFromInvoice($item, $order);
                continue;
            }
            $lines[] = $this->createInvoiceLineFromInvoice($item, $order);
        }

        //Shipping discount handling
        $shippingAmount = $invoice->getBaseShippingAmount();
        if ($order->getBaseShippingDiscountAmount() > 0) {
            $invoiceShipmentAmount = $invoice->getBaseShippingAmount();
            $shipmentDiscount = $order->getBaseShippingDiscountAmount();

            if (($invoiceShipmentAmount - $shipmentDiscount) < 0) {
                $shippingAmount = 0;
            } else {
                $shippingAmount = $invoiceShipmentAmount - $shipmentDiscount;
            }
        }

        //Shipping
        $shippingName = __("Shipping");
        $lines[] = $this->createInvoiceLine($shippingName, $shippingName, count($lines), 1, $shippingName, $shippingAmount, $invoice->getBaseShippingTaxAmount(), $invoice->getBaseCurrencyCode());

        //Add fee item
        if (isset($feeItem)) {
            $lines[] = $feeItem;
        }

        return $lines;
    }

    /**
     * Get Refund Invoice Lines
     *
     * @param \Magento\Sales\Model\Order\Creditmemo $creditMemo
     * @param \Magento\Sales\Model\Order $order
     * @return \Bambora\Online\Model\Api\Checkout\Request\Models\Line[]
     */
    private function getRefundInvoiceLines($creditMemo, $order)
    {
        $lines = array();
        //Fee item must be after shipment to keep the orginal authorize order of items
        $feeItem = null;
        $items = $this->filterVisibleItemsOnly($creditMemo->getAllItems());
        foreach ($items as $item) {
            if ($item->getSku() === BamboraConstants::BAMBORA_SURCHARGE) {
                $feeItem = $this->createInvoiceLineFromInvoice($item, $order);
                continue;
            }
            $lines[] = $this->createInvoiceLineFromInvoice($item, $order);
        }

        $shippingAmount = $creditMemo->getBaseShippingAmount();
        //Shipping discount handling
        if ($order->getBaseShippingDiscountAmount() > 0) {
            $creditShipmentAmount = $creditMemo->getBaseShippingAmount();
            $shipmentDiscount = $order->getBaseShippingDiscountAmount();

            if (($creditShipmentAmount - $shipmentDiscount) < 0) {
                $shippingAmount = 0;
            } else {
                $shippingAmount = $creditShipmentAmount - $shipmentDiscount;
            }
        }

        //Shipping
        if ($shippingAmount > 0) {
            $shippingName = __("Shipping");
            $lines[] = $this->createInvoiceLine($shippingName, $shippingName, count($lines) + 1, 1, $shippingName, $shippingAmount, $creditMemo->getBaseShippingTaxAmount(), $creditMemo->getBaseCurrencyCode());
        }

        if (isset($feeItem)) {
            $lines[] = $feeItem;
        }

        //Adjustment refund
        if ($creditMemo->getBaseAdjustment() > 0) {
            $adjustmentRefundName = __("Adjustment refund");
            $lines[] = $this->createInvoiceLine($adjustmentRefundName, $adjustmentRefundName, count($lines) + 1, 1, $adjustmentRefundName, $creditMemo->getBaseAdjustment(), 0, $creditMemo->getBaseCurrencyCode());
        }
        return $lines;
    }

    /**
     * Filter an itemcollection and only return the visible items
     *
     * @param \Magento\Sales\Model\Order\Creditmemo\Item[]|\Magento\Sales\Model\Order\Invoice\Item[] $itemCollection
     * @return array
     */
    private function filterVisibleItemsOnly($itemCollection)
    {
        $items = array();
        foreach ($itemCollection as $orgItem) {
            $item = $orgItem->getOrderItem();
            if (!$item->isDeleted() && !$item->getParentItemId()) {
                $items[] =  $item;
            }
        }
        return $items;
    }

    /**
     * Get Invoice Lines
     *
     * @param \Magento\Sales\Model\Order\Item $item
     * @param \Magento\Sales\Model\Order $order
     * @return \Bambora\Online\Model\Api\Checkout\Request\Models\Line
     */
    private function createInvoiceLineFromInvoice($item, $order)
    {
        $invoiceLine = $this->createInvoiceLine(
            $item->getDescription(),
            $item->getSku(),
            array_search($item->getOrderItemId(), array_keys($order->getItems())) + 1,
            floatval($item->getQty()),
            $item->getName(),
            $item->getBaseRowTotal(),
            $item->getBaseTaxAmount(),
            $order->getBaseCurrencyCode(),
            $item->getTaxPercent(),
            $item->getBaseDiscountAmount());

        return $invoiceLine;
    }

    /**{@inheritDoc}*/
    public function canCapture()
    {
        if ($this->_canCapture && $this->canAction($this::METHOD_REFERENCE)) {
            return true;
        }

        return false;
    }

    /**{@inheritDoc}*/
    public function canRefund()
    {
        if ($this->_canRefund && $this->canAction($this::METHOD_REFERENCE)) {
            return true;
        }

        return false;
    }

    /**{@inheritDoc}*/
    public function canVoid()
    {
        if ($this->_canVoid && $this->canAction($this::METHOD_REFERENCE)) {
            return true;
        }

        return false;
    }

    /**
     * Retrieve an url for the Bambora Checkout action
     *
     * @return string
     */
    public function getCheckoutUrl()
    {
        return $this->_urlBuilder->getUrl('bambora/checkout/checkout', ['_secure' => $this->_request->isSecure()]);
    }

    /**
     * Retrieve an url for the Bambora Assets action
     *
     * @return string
     */
    public function getAssetsUrl()
    {
        return $this->_urlBuilder->getUrl('bambora/checkout/assets', ['_secure' => $this->_request->isSecure()]);
    }

    /**
     * Retrieve an url for the Bambora Decline action
     *
     * @return string
     */
    public function getCancelUrl()
    {
        return $this->_urlBuilder->getUrl('bambora/checkout/cancel', ['_secure' => $this->_request->isSecure()]);
    }

    /**
     * Retrieve an url for the Bambora Checkout Icon
     *
     * @return string
     */
    public function getCheckoutIconUrl()
    {
        $assetsApi = $this->_bamboraHelper->getCheckoutApi(CheckoutApi::API_ASSETS);

        return $assetsApi->getCheckoutIconUrl();
    }
}
