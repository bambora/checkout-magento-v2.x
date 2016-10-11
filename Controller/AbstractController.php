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
namespace Bambora\Online\Controller;

use \Magento\Sales\Model\Order;

abstract class AbstractController extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Bambora\Online\Helper\Data
     */
    protected $_bamboraHelper;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * @var \Bambora\Online\Logger\BamboraLogger
     */
    protected $_bamboraLogger;

    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $_paymentHelper;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $_orderSender;

    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\InvoiceSender
     */
    protected $_invoiceSender;

    /**
     * @var \Magento\Sales\Model\Order
     */
    private $_order;

    /**
     * AbstractController constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Bambora\Online\Helper\Data $bamboraHelper
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Bambora\Online\Logger\BamboraLogger $bamboraLogger
     * @param \Magento\Payment\Helper\Data $paymentHelper
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
     * @param \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Bambora\Online\Helper\Data $bamboraHelper,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Bambora\Online\Logger\BamboraLogger $bamboraLogger,
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender
    ) {
        parent::__construct($context);
        $this->_orderFactory = $orderFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->_bamboraHelper = $bamboraHelper;
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_bamboraLogger = $bamboraLogger;
        $this->_paymentHelper = $paymentHelper;
        $this->_orderSender = $orderSender;
        $this->_invoiceSender = $invoiceSender;
    }

    /**
     * Get order object
     *
     * @return \Magento\Sales\Model\Order
     */
    protected function _getOrder()
    {
        if (!$this->_order)
        {
            $incrementId = $this->_checkoutSession->getLastRealOrderId();
            $this->_order = $this->_orderFactory->create()->loadByIncrementId($incrementId);
        }

        return $this->_order;
    }

    /**
     * Get order by IncrementId
     *
     * @param $incrementId
     * @return \Magento\Sales\Model\Order
     */
    protected function _getOrderByIncrementId($incrementId)
    {
        if (!$this->_order)
        {
            $this->_order = $this->_orderFactory->create()->loadByIncrementId($incrementId);
        }

        return $this->_order;
    }

    /**
     * Set the order details
     *
     * @param \Magento\Sales\Model\Order $order
     * @param string $paymentMethod
     * @param string $status
     */
    protected function setOrderDetails($order, $paymentMethod, $status)
    {
        $order->setPaymentMethod($paymentMethod);
        $order->setState(Order::STATE_PROCESSING);

        $order->setStatus($status);
        $message = __("Order placed and is now awaiting payment authorization");
        $order->addStatusHistoryComment($message,$status);
        $order->setIsNotified(false);
        $order->save();
    }


    protected function acceptOrder()
    {
        $posted = $this->getRequest()->getParams();
        if(array_key_exists('orderid',$posted))
        {
            $order = $this->_getOrderByIncrementId($posted['orderid']);

            $this->_checkoutSession->setLastOrderId($order->getId());
            $this->_checkoutSession->setLastRealOrderId($order->getIncrementId());
            $this->_checkoutSession->setLastQuoteId($order->getQuoteId());
            $this->_checkoutSession->setLastSuccessQuoteId($order->getQuoteId());
        }
        $this->_redirect('checkout/onepage/success');
    }

    /**
     * Cancel the order
     */
    protected function cancelOrder()
    {
        $this->cancelCurrentOrder();
        $this->restoreQuote();
        $this->_redirect('checkout/cart');
    }

    /**
     * Cancel last placed order with specified comment message
     *
     * @return bool
     */
    protected function cancelCurrentOrder()
    {
        $order = $this->_getOrder();
        if ($order->getId() && $order->getState() != Order::STATE_CANCELED) {
            $comment =  __("The order was canceled");
            $this->_bamboraLogger->addCheckoutInfo($order->getIncrementId(),$comment);
            $order->registerCancellation($comment)->save();

            return true;
        }

        return false;
    }

    /**
     * Restores quote
     *
     * @return bool
     */
    protected function restoreQuote()
    {
        return $this->_checkoutSession->restoreQuote();
    }

    /**
     * Get Payment method instance object
     *
     * @param string $method
     * @return {MethodInstance}
     */
    protected function _getPaymentMethodInstance($method)
    {
        return $this->_paymentHelper->getMethodInstance($method);
    }
}