<?php
namespace Bambora\Online\Model\Method;

use Bambora\Online\Helper\BamboraConstants;

abstract class AbstractPayment extends \Magento\Payment\Model\Method\AbstractMethod
{
    /**
     * @var \Bambora\Online\Helper\Data
     */
    protected $_bamboraHelper;

    /**
     * @var \Magento\Tax\Helper\Data
     */
    protected $_taxHelper;

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
     * @var \Magento\Sales\Model\Order
     */
    protected $_order;

    /**
     * Bambora Checkout constructor.
     *
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Bambora\Online\Helper\Data $bamboraHelper
     * @param \Magento\Tax\Helper\Data $taxHelper
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Payment\Model\Method\Logger $logger
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\UrlInterface $urlBuilder,
        \Bambora\Online\Helper\Data $bamboraHelper,
        \Magento\Tax\Helper\Data $taxHelper,
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
        \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection = null,
        array $data = []
    ) {
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
        $this->_taxHelper = $taxHelper;
        $this->_storeManager = $storeManager;
        $this->_request = $request;
        $this->_cart = $cart;
        $this->_messageManager = $messageManager;
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
     * Retrieve the Quote object
     *
     * @return \Magento\Quote\Model\Quote
     */
    public function getQuote()
    {
        return $this->_cart->getQuote();
    }

    /**
     * Retrieve order object
     *
     * @return false|\Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        if (!$this->_order) {
            $paymentInfo = $this->getInfoInstance();
            $this->_order = $paymentInfo->getOrder();
        }

        return $this->_order;
    }

    /**
     * Can be edit order (renew order)
     *
     * @return bool
     */
    public function canEdit()
    {
        return false;
    }

    /**
     * Can do online action
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @return bool
     */
    protected function canOnlineAction($payment)
    {
        $storeId = $payment->getOrder()->getStoreId();
        return (int)$this->getConfigData(BamboraConstants::REMOTE_INTERFACE, $storeId) === 1;
    }

    /**
     * Can do action
     *
     * @param mixed $reference
     * @return boolean
     */
    protected function canAction($reference)
    {
        $infoInstance = $this->getInfoInstance();
        $payment = $infoInstance->getOrder()->getPayment();
        $transactionId = $payment->getAdditionalInformation($reference);
        if (!empty($transactionId)) {
            return true;
        }

        return false;
    }

    /**
     * Cancel the surcharge fee item
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     */
    protected function cancelSurchargeFeeItem($payment)
    {
        $order = $payment->getOrder();
        foreach ($order->getItems() as $item) {
            if ($item->getSku() === BamboraConstants::BAMBORA_SURCHARGE) {
                $item->setQtyCanceled(1);
            }
        }
    }
}
