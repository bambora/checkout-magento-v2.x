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
        if(!$this->_apiKey)
        {
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
    public function getPaymentCardIds($currency = null,$amount = null)
    {
        if(is_null($currency))
        {
            $currency = $this->getQuote()->getBaseCurrencyCode();
        }

        if(is_null($amount))
        {
            $amount = $this->getQuote()->getBaseGrandTotal();
        }

        $minorUnits = $this->_bamboraHelper->getCurrencyMinorunits($currency);
        $amountMinorunits = $this->_bamboraHelper->convertPriceToMinorUnits($amount, $minorUnits);

        /** @var \Bambora\Online\Model\Api\Checkout\Merchant */
        $merchantApi = $this->_bamboraHelper->getCheckoutApi(CheckoutApi::API_MERCHANT);

        $paymentTypeResponse = $merchantApi->getPaymentTypes($currency, $amountMinorunits, $this->getApiKey());

        $message = "";
        if($this->_bamboraHelper->validateCheckoutApiResult($paymentTypeResponse, $this->getQuote()->getId(), false, $message))
        {
            $paymentCardIdsArray = array();

            foreach($paymentTypeResponse->paymentCollections as $payment)
            {
                foreach($payment->paymentGroups as $group)
                {
                    $paymentCardIdsArray[] = $group->id;
                }
            }
            return $paymentCardIdsArray;
        }
        else
        {
            $this->_messageManager->addError(__("Bambora get allowed payment types error").": ".$message);
            return null;
        }
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
        if ($order->getBillingAddress()->getEmail())
        {
            $email = $order->getBillingAddress()->getEmail();
        }
        else
        {
            $email = $order->getCustomerEmail();
        }

        $storeId = $order->getStoreId();
        $minorUnits = $this->_bamboraHelper->getCurrencyMinorUnits($order->getBaseCurrencyCode());
        $totalAmountMinorUnits = $this->_bamboraHelper->convertPriceToMinorUnits($order->getGrandTotal(), $minorUnits);

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
        $bamboraOrder->total = $this->_bamboraHelper->convertPriceToMinorUnits($order->getBaseTotalDue(), $minorUnits);
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

        if($billingAddress)
        {
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

        if($shippingAddress)
        {
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
        foreach($items as $item)
        {
            $bamboraOrderLines[] = $this->createInvoiceLine(
                $item->getDescription(),
                $item->getSku(),
                $lineNumber,
                floatval($item->getQtyOrdered()),
                $item->getName(),
                $item->getBaseRowTotal(),
                $item->getBaseTaxAmount(),
                floatval($item->getTaxPercent()),
                $order->getBaseCurrencyCode(),
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
            null,
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
     * @param int|null $vat
     * @param mixed $currencyCode
     * @return \Bambora\Online\Model\Api\Checkout\Request\Models\Line
     */
    public function createInvoiceLine($description, $id, $lineNumber, $quantity, $text, $totalPrice, $totalPriceVatAmount, $vat, $currencyCode, $discountAmount = 0)
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
        if(!isset($vat))
        {
            $vat = $totalPriceVatAmount > 0 && $totalPrice > 0  ? round($totalPriceVatAmount / $totalPrice * 100) : 0;
        }
        $line->vat = $vat;

        return $line;
    }

    /**
     * Get Bambora Checkout payment window
     *
     * @param \Magento\Sales\Model\Order
     * @return \Bambora\Online\Model\Api\Checkout\Response\Checkout
     */
    public function getPaymentWindow($order)
    {
        if(!isset($order))
        {
            return null;
        }

        $paymentRequest = $this->createPaymentRequest($order);

        /** @var \Bambora\Online\Model\Api\Checkout\Checkout */
        $checkoutProvider = $this->_bamboraHelper->getCheckoutApi(CheckoutApi::API_CHECKOUT);
        $checkoutResponse = $checkoutProvider->setCheckout($paymentRequest, $this->getApiKey());

        $message = "";
        if(!$this->_bamboraHelper->validateCheckoutApiResult($checkoutResponse, $order->getIncrementId(),false, $message))
        {
            $this->_messageManager->addError(__('The payment window could not be retrived'));
            $this->_messageManager->addError(__('Bambora Checkout error') . ': ' . $message);
            $checkoutResponse = null;
        }

        return $checkoutResponse;
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
        try
        {
            parent::capture($payment, $amount);

            $transactionId = $payment->getAdditionalInformation($this::METHOD_REFERENCE);
            $order = $payment->getOrder();

            //For later implementation
            $invoicelines = null;

            $currency = $order->getBaseCurrencyCode();
            $minorunits = $this->_bamboraHelper->getCurrencyMinorunits($currency);
            $amountMinorunits = $this->_bamboraHelper->convertPriceToMinorUnits($amount,$minorunits);

            /** @var \Bambora\Online\Model\Api\Checkout\Transaction */
            $transactionProvider = $this->_bamboraHelper->getCheckoutApi(CheckoutApi::API_TRANSACTION);
            $captureResponse = $transactionProvider->capture($transactionId,$amountMinorunits,$currency,$invoicelines,$this->getApiKey());
            $message = "";
            if(!$this->_bamboraHelper->validateCheckoutApiResult($captureResponse, $order->getIncrementId(),true, $message))
            {
                throw new \Exception(__('The capture action failed.') . ' - '.$message);
            }
            $transactionoperationId = "";
            foreach($captureResponse->transactionOperations as $transactionoperation)
            {
                $transactionoperationId = $transactionoperation->id;
            }

            $payment->setTransactionId($transactionoperationId . '-' . Transaction::TYPE_CAPTURE)
                    ->setIsTransactionClosed(true)
                    ->setParentTransactionId($transactionId);

            return $this;
        }
        catch(\Exception $ex)
        {
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
        try
        {
            parent::refund($payment, $amount);
            $transactionId = $payment->getAdditionalInformation($this::METHOD_REFERENCE);
            $order = $payment->getOrder();

            //For later implementation
            $invoicelines = null;

            $currency = $order->getBaseCurrencyCode();
            $minorunits = $this->_bamboraHelper->getCurrencyMinorunits($currency);
            $amountMinorunits = $this->_bamboraHelper->convertPriceToMinorUnits($amount,$minorunits);
            $transactionProvider = $this->_bamboraHelper->getCheckoutApi(CheckoutApi::API_TRANSACTION);
            $creditResponse = $transactionProvider->credit($transactionId,$amountMinorunits,$currency,$invoicelines,$this->getApiKey());
            $message = "";
            if(!$this->_bamboraHelper->validateCheckoutApiResult($creditResponse, $order->getIncrementId(), true, $message))
            {
                throw new \Exception(__('The refund action failed.') . ' - '.$message);
            }
            $transactionoperationId = "";
            foreach($creditResponse->transactionOperations as $transactionoperation)
            {
                $transactionoperationId = $transactionoperation->id;
            }
            $payment->setTransactionId($transactionoperationId . '-' . Transaction::TYPE_REFUND)
                    ->setIsTransactionClosed(true)
                    ->setParentTransactionId($transactionId);

            return $this;
        }
        catch(\Exception $ex)
        {
            throw $ex;
        }
    }

    /**
     * Void payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return $this
     */
    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        try{
            parent::void($payment);

            $transactionId = $payment->getAdditionalInformation($this::METHOD_REFERENCE);
            $order = $payment->getOrder();
            $transactionProvider = $this->_bamboraHelper->getCheckoutApi(CheckoutApi::API_TRANSACTION);
            $deleteResponse = $transactionProvider->delete($transactionId,$this->getApiKey());
            $message = "";
            if(!$this->_bamboraHelper->validateCheckoutApiResult($deleteResponse, $order->getIncrementId(),true, $message))
            {
                throw new \Exception(__('The void action failed.') . ' - '.$message);
            }
            $transactionoperationId = "";
            foreach($deleteResponse->transactionOperations as $transactionoperation)
            {
                $transactionoperationId = $transactionoperation->id;
            }
            $payment->setTransactionId($transactionoperationId . '-' . Transaction::TYPE_VOID)
                    ->setIsTransactionClosed(true)
                    ->setParentTransactionId($transactionId);

            return $this;
        }
        catch(\Exception $ex)
        {
            throw $ex;
        }
    }

    private function _canVoid($txnId)
    {
        if(!$this->canVoid())
        {
            return false;
        }

        $transaction = $this->getTransaction($txnId);
        if(!isset($transaction) && !$transaction->canDelete)
        {
            return false;
        }

        return true;
    }

    /**
     * Cancel payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return $this
     */
    public function cancel(\Magento\Payment\Model\InfoInterface $payment)
    {
        try
        {
            /** @var \Magento\Sales\Model\Order */
            $order = $payment->getOrder();
            foreach($order->getItems() as $item)
            {
                if($item->getSku() === BamboraConstants::BAMBORA_SURCHARGE)
                {
                    $item->setQtyCanceled(1);
                }
            }
            if($this->_canVoid($payment->getAdditionalInformation($this::METHOD_REFERENCE)))
            {
                $this->void($payment);
            }
            else
            {
                $this->_messageManager->addNotice(__('The order: %1 is canceled but the payment could not be voided', $order->getId()));
            }
        }
        catch(\Exception $ex)
        {
            throw $ex;
        }

        return $this;
    }


    /**
     * Get Bambora Checkout Transaction
     *
     * @param string $transactionId
     * @return \Bambora\Online\Model\Api\Checkout\Response\Models\Transaction
     */
    public function getTransaction($transactionId)
    {
        try
        {
            /** @var \Bambora\Online\Model\Api\Checkout\Merchant */
            $merchantProvider = $this->_bamboraHelper->getCheckoutApi(CheckoutApi::API_MERCHANT);
            $transactionResponse = $merchantProvider->getTransaction($transactionId,$this->getApiKey());

            $message = "";
            if(!$this->_bamboraHelper->validateCheckoutApiResult($transactionResponse, $transactionId, true, $message))
            {
                return null;
            }

            return $transactionResponse->transaction;
        }
        catch(\Exception $ex)
        {
            return null;
        }
    }

    /**
     * Get Invoice Lines
     *
     * @param \Magento\Sales\Model\Order\Creditmemo\Item[]|\Magento\Sales\Model\Order\Invoice\Item[] $items
     * @param \Magento\Sales\Model\Order $order
     * @return \Bambora\Online\Model\Api\Checkout\Request\Models\Line[]
     */
    public function getInvoiceLines($items,$order)
    {
        $invoiceLines = array();
        foreach($items as $item)
        {
            $invoiceLines[] = $this->createInvoiceLine(
                $item->getDescription(),
                $item->getSku(),
                array_search($item->getOrderItemId(),array_keys($order->getItems())) + 1,
                floatval($item->getQty()),
                $item->getName(),
                $item->getBaseRowTotal(),
                $item->getBaseTaxAmount(),
                floatval($item->getTaxPercent()),
                $order->getBaseCurrencyCode(),
                $item->getBaseDiscountAmount());
        }

        return $invoiceLines;
    }
}