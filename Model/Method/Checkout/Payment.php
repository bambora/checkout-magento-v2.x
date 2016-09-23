<?php
/**
 * Bambora Online
 *
 * @category    Online Payment Gatway
 * @package     Bambora_Online_Checkout
 * @author      Bambora
 * @copyright   Bambora (http://bambora.com)
 */
namespace Bambora\Online\Model\Method\Checkout;

use Magento\Framework\DataObject;
use Bambora\Online\Model\Api\CheckoutApi;
use Bambora\Online\Model\Api\CheckoutApiModels;
use \Magento\Sales\Model\Order\Payment\Transaction;

class Payment extends \Magento\Payment\Model\Method\AbstractMethod
{
    const METHOD_CODE = 'bambora_checkout';

    protected $_code = self::METHOD_CODE;

    protected $_infoBlockType = 'Bambora\Online\Block\Checkout\Info';

    /**
     * Payment Method feature
     */
    protected $_isGateway                   = true;
    protected $_canCapture                  = true;
    protected $_canCapturePartial           = true;
    protected $_canRefund                   = true;
    protected $_canRefundInvoicePartial     = true;
    protected $_canVoid                     = true;

    /**
     * @var \Bambora\Online\Helper\Data
     */
    protected $_bamboraHelper;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $_cart;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;

    /**
     * @var Transaction\BuilderInterface
     */
    protected $_transactionBuilder;

    /**
     * @var string
     */
    private $_apiKey;

    /**
     * @var \Magento\Sales\Model\Order
     */
    private $_order;


    /**
     * Bambora Checkout constructor.
     *
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Bambora\Online\Helper\Data $bamboraHelper
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\App\Response\Http $response
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\UrlInterface $urlBuilder,
        \Bambora\Online\Helper\Data $bamboraHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\App\RequestInterface $request,
		\Magento\Checkout\Model\Cart $cart,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );

        $this->_urlBuilder = $urlBuilder;
        $this->_bamboraHelper = $bamboraHelper;
        $this->_storeManager = $storeManager;
        $this->_request = $request;
		$this->_cart = $cart;
        $this->_messageManager = $messageManager;
        $this->_transactionBuilder = $transactionBuilder;
    }

    /**
     * Retrieve the storemanager instance
     *
     * @return \Magento\Store\Model\StoreManagerInterface
     */
    public function getStoreManager()
    {
        return $this->_storeManager;
    }

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
            $this->_apiKey = $this->_bamboraHelper->generateApiKey($storeId);
        }

        return $this->_apiKey;
    }

    /**
     * Retrieve the Quote object
     *
     * @return \Magento\Quote\Model\Quote
     */
    public function getQuote()
    {
        return $this->_cart->getQuote();
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

        $merchantApi = $this->_bamboraHelper->getCheckoutApi(CheckoutApi::API_MERCHANT);

        $result = $merchantApi->getPaymentTypes($currency, $amountMinorunits, $this->getApiKey());
        $paymentCardIdsArray = array();
        $message = "";
        if($this->_bamboraHelper->validateCheckoutApiResult($result, $this->getQuote()->getId(),false, $message))
        {
            foreach($result['paymentcollections'] as $payment)
            {
                foreach($payment['paymentgroups'] as $card)
                {
                    $paymentCardIdsArray[] = $card['id'];
                }
            }
        }
        else
        {
            $this->_messageManager->addError($message);
            throw new \Magento\Framework\Exception\LocalizedException(__('Could not retrive allowed payment cards.'),new \Exception($message));
        }

        return $paymentCardIdsArray;
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
    public function getDeclineUrl()
    {
        return $this->_urlBuilder->getUrl('bambora/checkout/decline', ['_secure' => $this->_request->isSecure()]);
    }

    /**
     * Retrieve an url for the Bambora Checkout Icon
     *
     * @return string
     */
    public function getCheckoutIconUrl()
    {
        $assetsApi = $this->_bamboraHelper->getCheckoutApi('Assets');

        return $assetsApi->getCheckoutIconUrl();
    }

    /**
     * Retrieve value for a configurationType
     *
     * @return string
     */
    public function getCheckoutConfig($configType)
    {
        $value = $this->_bamboraHelper->getBamboraCheckoutConfigData($configType,$this->getStoreManager()->getStore()->getId());

        return $value;
    }

    /**
     * Retrieve order object
     *
     * @return false|\Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        if(!$this->_order)
        {
            $paymentInfo = $this->getInfoInstance();
            $this->_order = $paymentInfo->getOrder();
        }

        return $this->_order;
    }

    /**
     * Create the Bambora Checkout Request object
     *
     * @return \Bambora\Online\Model\Api\Checkout\Models\Checkoutrequest
     */
    public function createCheckoutRequest($order)
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
        $minorUnits = $this->_bamboraHelper->getCurrencyMinorUnits($order->getOrderCurrencyCode());
        $totalAmountMinorUnits = $this->_bamboraHelper->convertPriceToMinorUnits($order->getGrandTotal(), $minorUnits);

        $checkoutRequest = $this->_bamboraHelper->getCheckoutApiModel(CheckoutApiModels::MODEL_CHECKOUTREQUEST);
        $checkoutRequest->instantcaptureamount = $this->_bamboraHelper->getBamboraCheckoutConfigData('instantcapture', $storeId) == 0 ? 0 : $totalAmountMinorUnits;
        $checkoutRequest->language = $this->_bamboraHelper->getShopLocalCode();
        $checkoutRequest->paymentwindowid = $this->getConfigData('paymentwindow_id', $storeId);

        $bamboraCustomer = $this->_bamboraHelper->getCheckoutApiModel(CheckoutApiModels::MODEL_CUSTOMER);
        $bamboraCustomer->email = $email;

        $bamboraOrder = $this->_bamboraHelper->getCheckoutApiModel(CheckoutApiModels::MODEL_ORDER);
        $bamboraOrder->currency = $order->getOrderCurrencyCode();
        $bamboraOrder->ordernumber = $order->getIncrementId();
        $bamboraOrder->total = $this->_bamboraHelper->convertPriceToMinorUnits($order->getBaseTotalDue(), $minorUnits);
        $bamboraOrder->vatamount = $this->_bamboraHelper->convertPriceToMinorUnits($order->getBaseTaxAmount(), $minorUnits);

        $bamboraUrl = $this->_bamboraHelper->getCheckoutApiModel(CheckoutApiModels::MODEL_URL);
        $bamboraUrl->accept = $this->_urlBuilder->getUrl('bambora/checkout/accept', ['_secure' => $this->_request->isSecure()]);
        $bamboraUrl->decline =  $this->_urlBuilder->getUrl('bambora/checkout/decline', ['_secure' => $this->_request->isSecure()]);

        $bamboraCallback = $this->_bamboraHelper->getCheckoutApiModel(CheckoutApiModels::MODEL_CALLBACK);
        $bamboraCallback->url = $this->_urlBuilder->getUrl('bambora/checkout/callback', ['_secure' => $this->_request->isSecure()]);
        $bamboraUrl->callbacks = array();
        $bamboraUrl->callbacks[] = $bamboraCallback;
        $bamboraUrl->immediateredirecttoaccept = $this->getConfigData('immediateredirecttoaccept', $storeId);
        $checkoutRequest->url = $bamboraUrl;

        if($billingAddress)
        {
            $bamboraCustomer->phonenumber = $billingAddress->getTelephone();
            $bamboraCustomer->phonenumbercountrycode = $billingAddress->getCountryId();

            $bamboraBillingAddress = $this->_bamboraHelper->getCheckoutApiModel(CheckoutApiModels::MODEL_ADDRESS);
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
            $bamboraShippingAddress = $this->_bamboraHelper->getCheckoutApiModel(CheckoutApiModels::MODEL_ADDRESS);
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
                $item->getBaseRowTotalInclTax(),
                $item->getBaseTaxAmount(),
                floatval($item->getTaxPercent()),
                $order->getOrderCurrencyCode());

            $lineNumber++;
        }

        $shippingAmount = $order->getBaseShippingAmount();
        if($shippingAmount > 0)
        {
            $bamboraOrderLines[] = $this->createInvoiceLine(
                __("Shipping"),
                __("Shipping"),
                $lineNumber++,
                1,
                __("Shipping"),
                $shippingAmount,
                $order->getBaseShippingInclTax(),
                $order->getBaseShippingTaxAmount(),
                null,
                $order->getOrderCurrencyCode());
        }

        $bamboraOrder->lines = $bamboraOrderLines;
        $checkoutRequest->order = $bamboraOrder;

        return $checkoutRequest;
    }

    /**
     * Create Aditional Invoice Line
     * @param mixed $description
     * @param mixed $id
     * @param mixed $lineNumber
     * @param mixed $quantity
     * @param mixed $text
     * @param mixed $totalPrice
     * @param mixed $totalPriceInclVat
     * @param mixed $totalPriceVatAmount
     * @param int|null $vat
     * @param mixed $currencyCode
     * @return \Bambora\Online\Model\Api\Checkout\Models\Orderline
     */
    public function createInvoiceLine($description, $id, $lineNumber, $quantity, $text, $totalPrice, $totalPriceInclVat, $totalPriceVatAmount,$vat, $currencyCode)
    {
        $minorUnits = $this->_bamboraHelper->getCurrencyMinorunits($currencyCode);
        $line = $this->_bamboraHelper->getCheckoutApiModel('Orderline');
        $line->description = isset($description) ? $description : $text;
        $line->id = $id;
        $line->linenumber = $lineNumber;
        $line->quantity = $quantity;
        $line->text = $text;
        $line->totalprice = $this->_bamboraHelper->convertPriceToMinorUnits($totalPrice, $minorUnits);
        $line->totalpriceinclvat = $this->_bamboraHelper->convertPriceToMinorUnits($totalPriceInclVat, $minorUnits);
        $line->totalpricevatamount = $this->_bamboraHelper->convertPriceToMinorUnits($totalPriceVatAmount, $minorUnits);
        $line->unit = __("pcs.");
        $line->vat = isset($vat) ? $vat : $totalPriceVatAmount > 0 ? round( $totalPriceVatAmount / $totalPrice * 100) : 0;

        return $line;
    }

    /**
     * Set Checkout request
     *
     * @param \Magento\Sales\Model\Order
     * @return mixed
     */
    public function setCheckout($order)
    {
        if(!isset($order))
        {
            return null;
        }
        $setCheckoutRequest = $this->createCheckoutRequest($order);
        $checkoutProvider = $this->_bamboraHelper->getCheckoutApi(CheckoutApi::API_CHECKOUT);
        $setCheckoutResponse = $checkoutProvider->setCheckout($setCheckoutRequest, $this->getApiKey());
       
        return $setCheckoutResponse;
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
        parent::capture($payment, $amount);

        $transactionId = $payment->getAdditionalInformation('bamboraReference');
        $order = $payment->getOrder();

        $invoicelines = null;

        if($order->getGrandTotal() != $amount)
        {
            $invoice = $order->getInvoiceCollection()->getLastItem();
            $invoicelines = $this->getCaptureInvoiceLines($invoice, $order);
        }

        $currency = $order->getOrderCurrencyCode();
        $minorunits = $this->_bamboraHelper->getCurrencyMinorunits($currency);
        $amountMinorunits = $this->_bamboraHelper->convertPriceToMinorUnits($amount,$minorunits);
        $transactionProvider = $this->_bamboraHelper->getCheckoutApi(CheckoutApi::API_TRANSACTION);
        $captureResponse = $transactionProvider->capture($transactionId,$amountMinorunits,$currency,$invoicelines,$this->getApiKey());
        $message = "";
        if(!$this->_bamboraHelper->validateCheckoutApiResult($captureResponse, $order->getIncrementId(),true, $message))
        {
            $this->_messageManager->addError($message);
            throw new \Magento\Framework\Exception\LocalizedException(__('The capture action failed.'));
        }
        $transactionoperationId = "";
        foreach($captureResponse['transactionoperations'] as $transactionoperation)
        {
            $transactionoperationId = $transactionoperation['id'];
        }
        $payment->setTransactionId($transactionoperationId)
                ->setIsTransactionClosed(true)
                ->setParentTransactionId($transactionId);

        return $this;
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
        parent::refund($payment, $amount);
        $transactionId = $payment->getAdditionalInformation('bamboraReference');
        $order = $payment->getOrder();
        $creditMemo = $payment->getCreditmemo();

        $invoicelines = $this->getRefundInvoiceLines($creditMemo, $order);

        $currency = $order->getOrderCurrencyCode();
        $minorunits = $this->_bamboraHelper->getCurrencyMinorunits($currency);
        $amountMinorunits = $this->_bamboraHelper->convertPriceToMinorUnits($amount,$minorunits);
        $transactionProvider = $this->_bamboraHelper->getCheckoutApi(CheckoutApi::API_TRANSACTION);
        $creditResponse = $transactionProvider->credit($transactionId,$amountMinorunits,$currency,$invoicelines,$this->getApiKey());
        $message = "";
        if(!$this->_bamboraHelper->validateCheckoutApiResult($creditResponse, $order->getIncrementId(),true, $message))
        {
            $this->_messageManager->addError($message);
            throw new \Magento\Framework\Exception\LocalizedException(__('The refund action failed.'));
        }
        $transactionoperationId = "";
        foreach($creditResponse['transactionoperations'] as $transactionoperation)
        {
            $transactionoperationId = $transactionoperation['id'];
        }
        $payment->setTransactionId($transactionoperationId)
                ->setIsTransactionClosed(true)
                ->setParentTransactionId($transactionId);

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
        parent::void($payment);

        $transactionId = $payment->getAdditionalInformation('bamboraReference');
        $order = $payment->getOrder();
        $transactionProvider = $this->_bamboraHelper->getCheckoutApi(CheckoutApi::API_TRANSACTION);
        $deleteResponse = $transactionProvider->delete($transactionId,$this->getApiKey());
        $message = "";
        if(!$this->_bamboraHelper->validateCheckoutApiResult($deleteResponse, $order->getIncrementId(),true, $message))
        {
            $this->_messageManager->addError($message);
            throw new \Magento\Framework\Exception\LocalizedException(__('The void or cancel action failed.'));
        }
        $transactionoperationId = "";
        foreach($deleteResponse['transactionoperations'] as $transactionoperation)
        {
            $transactionoperationId = $transactionoperation['id'];
        }
        $payment->setTransactionId($transactionoperationId)
                ->setIsTransactionClosed(true)
                ->setParentTransactionId($transactionId);

        return $this;
    }

    /**
     * Cancel payment
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return $this
     */
    public function cancel(\Magento\Payment\Model\InfoInterface $payment)
    {
        parent::cancel($payment);
        if($this->canVoid())
        {
            $this->void($payment);
        }
        else
        {
            $this->_messageManager->addInfo(__('The payment is cancled but could not be voided'));
        }

        return $this;
    }

    /**
     * Get Bambora Checkout Transaction
     *
     * @param mixed $transactionId
     * @return mixed
     */
    public function getTransaction($transactionId)
    {
        $merchantProvider = $this->_bamboraHelper->getCheckoutApi(CheckoutApi::API_MERCHANT);
        $transactionResponse = $merchantProvider->getTransaction($transactionId,$this->getApiKey());
        $message = "";
        if(!$this->_bamboraHelper->validateCheckoutApiResult($transactionResponse, $transactionId,true, $message))
        {
            $this->_messageManager->addError($message);
            return null;
        }

        return $transactionResponse;
    }

    /**
     * Get Invoice Lines
     *
     * @param \Magento\Sales\Model\Order\Creditmemo\Item[]|\Magento\Sales\Model\Order\Invoice\Item[] $items
     * @param \Magento\Sales\Model\Order $order
     * @return \Bambora\Online\Model\Api\Checkout\Models\Orderline[]
     */
    public function getInvoiceLines($items,$order)
    {
        $invoiceLines = array();
        foreach($items as $item)
        {
            $invoiceLines[] = $this->createInvoiceLine(
                $item->getDescription(),
                $item->getSku(),
                array_search($item->getOrderItemId(),array_keys($order->getItems()))+1,
                floatval($item->getQty()),
                $item->getName(),
                $item->getBaseRowTotal(),
                $item->getBaseRowTotalInclTax(),
                $item->getBaseTaxAmount(),
                floatval($item->getTaxPercent()),
                $order->getOrderCurrencyCode());
        }

        return $invoiceLines;
    }



    /**
     * Get Refund Invoice Lines
     *
     * @param \Magento\Sales\Model\Order\Creditmemo $creditMemo
     * @param \Magento\Sales\Model\Order $order
     * @return \Bambora\Online\Model\Api\Checkout\Models\Orderline[]
     */
    public function getRefundInvoiceLines($creditMemo,$order)
    {
        $refundItems = $creditMemo->getItems();
        $lines = $this->getInvoiceLines($refundItems,$order);

        //Shipping
        $shippingName = __("Shipping");
        $lines[] = $this->createInvoiceLine($shippingName, $shippingName, count($lines) + 1, 1, $shippingName, $creditMemo->getBaseShippingAmount(), $creditMemo->getBaseShippingInclTax(), $creditMemo->getBaseShippingTaxAmount(),null, $creditMemo->getOrderCurrencyCode());

        //Adjustment refund
        $adjustmentRefundName = __("Adjustment refund");
        $lines[] = $this->createInvoiceLine($adjustmentRefundName, $adjustmentRefundName, count($lines) + 1, 1, $adjustmentRefundName, $creditMemo->getBaseAdjustment(), $creditMemo->getBaseAdjustment(), 0,null, $creditMemo->getOrderCurrencyCode());

        return $lines;
    }

    /**
     * Get Refund Invoice Lines
     *
     * @param \Magento\Sales\Model\Order\Invoice $creditMemo
     * @param \Magento\Sales\Model\Order $order
     * @return \Bambora\Online\Model\Api\Checkout\Models\Orderline[]
     */
    public function getCaptureInvoiceLines($invoice,$order)
    {
        $invoiceItems = $invoice->getItemsCollection()->getItems();
        $lines = $this->getInvoiceLines($invoiceItems,$order);

        //Shipping
        $shippingName = __("Shipping");
        $lines[] = $this->createInvoiceLine($shippingName, $shippingName, count($lines), 1, $shippingName, $invoice->getShippingAmount(), $invoice->getShippingInclTax(), $invoice->getShippingTaxAmount(),null, $invoice->getOrderCurrencyCode());

        return $lines;
    }
}