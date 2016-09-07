<?php
/**
 * Bambora Online
 *
 * @category    Online Payment Gatway
 * @package     Bambora_Online_Checkout
 * @author      Bambora
 * @copyright   Bambora (http://bambora.com)
 */
namespace Bambora\Online\Model\Method;

use Magento\Framework\DataObject;
use Bambora\Online\Model\Api\CheckoutApi;
use Bambora\Online\Model\Api\CheckoutApiModels;

class Checkout extends \Magento\Payment\Model\Method\AbstractMethod
{
    const MODULE_INFO = "Magento 2 - Bambora Checkout";
    const MODULE_VERSION = "v0.1.0";
    const METHOD_CODE = 'bambora_checkout';

    protected $_code = self::METHOD_CODE;

    protected $_infoBlockType = 'Bambora\Online\Block\Info\Checkout';

    /**
     * Payment Method feature
     */
    protected $_isGateway 				= true;
    protected $_canAuthorize 			= false; // NO! Authorization is not done by webservices! (PCI)
    protected $_canCapture 				= false;
    protected $_canCapturePartial 		= false;
    protected $_canRefund 				= false;
    protected $_canOrder 				= true;
    protected $_canRefundInvoicePartial = false;
    protected $_canVoid 				= false;
    protected $_canUseInternal 			= true;	// If an internal order is created (phone / mail order) payment must be done using webpay and not an internal checkout method!
    protected $_canUseCheckout 			= true;
    protected $_canUseForMultishipping 	= true;
    protected $_canSaveCc 				= false; // NO CC is never saved. (PCI)

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
     * @var string
     */
    private $_apiKey;

    /**
     * @var \Magento\Sales\Model\Order
     */
    private $_order;


    /**
     * Bambora Checkout constructor.
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
    }


    /**
     * @desc Retrieve the storemanager instance
     * @return \Magento\Store\Model\StoreManagerInterface
     */
    public function getStoreManager()
    {
        return $this->_storeManager;
    }

    /**
     * @desc Retrieve an api key for the Bambora Api
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
     * @desc Retrieve the Quote object
     * @return \Magento\Quote\Model\Quote
     */
    public function getQuote()
    {
        return $this->_cart->getQuote();
    }

    /**
     * @desc Retrieve allowed PaymentCardIds
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

        if($this->_bamboraHelper->validateCheckoutApiResult($result, $this->getQuote()->getId()))
        {
            foreach($result['paymentcollections'] as $payment)
            {
                foreach($payment['paymentgroups'] as $card)
                {
                    $paymentCardIdsArray[] = $card['id'];
                }
            }
        }

        return $paymentCardIdsArray;
    }

    /**
     * @desc Retrieve an url for the Bambora Checkout action
     * @return string
     */
    public function getCheckoutUrl()
    {
        return $this->_urlBuilder->getUrl('bambora/checkout/checkout', ['_secure' => $this->_request->isSecure()]);
    }

    /**
     * @desc Retrieve an url for the Bambora Assets action
     * @return string
     */
    public function getAssetsUrl()
    {
        return $this->_urlBuilder->getUrl('bambora/checkout/assets', ['_secure' => $this->_request->isSecure()]);
    }

    /**
     * @desc Retrieve an url for the Bambora Decline action
     * @return string
     */
    public function getDeclineUrl()
    {
        return $this->_urlBuilder->getUrl('bambora/checkout/decline', ['_secure' => $this->_request->isSecure()]);
    }

    /**
     * @desc Retrieve an url for the Bambora Checkout Icon
     * @return string
     */
    public function getCheckoutIconUrl()
    {
        $assetsApi = $this->_bamboraHelper->getCheckoutApi('Assets');

        return $assetsApi->getCheckoutIconUrl();
    }

    /**
     * @desc Retrieve value for a configurationType
     * @return string
     */
    public function getCheckoutConfig($configType)
    {
        $value = $this->_bamboraHelper->getBamboraCheckoutConfigData($configType,$this->getStoreManager()->getStore()->getId());

        return $value;
    }

    /**
     * Retrieve order object
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
     * @desc Create the Bambora Checkout Request object
     * @return \Bambora\Online\Model\Api\Checkout\Models\Checkoutrequest
     */
    public function createBamboraCheckoutRequest($order = null)
    {
        if(!isset($order))
        {
            $order = $this->getOrder();
        }

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
        $checkoutRequest->capturemulti = true;
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
            $line = $this->_bamboraHelper->getCheckoutApiModel(CheckoutApiModels::MODEL_ORDERLINE);
            $description = $item->getDescription();
            $line->description = isset($description) ? $description : $item->getName();
            $line->id = $item->getSku();
            $line->linenumber = $lineNumber;
            $line->quantity = floatval($item->getQtyOrdered());
            $line->text = $item->getName();
            $line->totalprice =  $this->_bamboraHelper->convertPriceToMinorUnits($item->getBaseRowTotal(),$minorUnits);
            $line->totalpriceinclvat = $this->_bamboraHelper->convertPriceToMinorUnits($item->getBaseRowTotalInclTax(),$minorUnits);
            $line->totalpricevatamount = $this->_bamboraHelper->convertPriceToMinorUnits($item->getBaseTaxAmount(),$minorUnits);
            $line->unit = __("pcs.");
            $line->vat = floatval($item->getTaxPercent());

            $bamboraOrderLines[] = $line;
            $lineNumber++;
        }

        //Add shipping as an orderline
        $shippingAmount = $order->getShippingAmount();
        if($shippingAmount > 0)
        {
            $shippingOrderline = $this->_bamboraHelper->getCheckoutApiModel('Orderline');
            $shippingOrderline->description = __("Shipping");
            $shippingOrderline->id = __("Shipping");
            $shippingOrderline->linenumber = $lineNumber++;
            $shippingOrderline->quantity = 1;
            $shippingOrderline->text = __("Shipping");
            $shippingOrderline->totalprice = $this->_bamboraHelper->convertPriceToMinorUnits($shippingAmount, $minorUnits);
            $shippingOrderline->totalpriceinclvat = $this->_bamboraHelper->convertPriceToMinorUnits($order->getShippingInclTax(), $minorUnits);
            $shippingTaxAmount = $order->getShippingTaxAmount();
            $shippingOrderline->totalpricevatamount = $this->_bamboraHelper->convertPriceToMinorUnits($shippingTaxAmount, $minorUnits);
            $shippingOrderline->unit = __("pcs.");
            $shippingOrderline->vat = round( $shippingTaxAmount / $shippingAmount * 100);
            $bamboraOrderLines[] = $shippingOrderline;
        }

        $bamboraOrder->lines = $bamboraOrderLines;
        $checkoutRequest->order = $bamboraOrder;

        return $checkoutRequest;
    }

    /**
     * @desc Set Checkout
     * @return false|\Magento\Sales\Model\Order
     */
    public function setCheckout($setCheckoutRequest)
    {
        $checkoutProvider = $this->_bamboraHelper->getCheckoutApi(CheckoutApi::API_CHECKOUT);

        return $checkoutProvider->setCheckout($setCheckoutRequest, $this->getApiKey());
    }
}