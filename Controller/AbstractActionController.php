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
use \Magento\Sales\Model\Order\Payment\Transaction;
use \Bambora\Online\Helper\BamboraConstants;
use \Magento\Framework\Webapi\Exception;
use \Magento\Framework\Webapi\Response;
use \Bambora\Online\Model\Method\Checkout\Payment as CheckoutPayment;
use \Bambora\Online\Model\Method\Epay\Payment as EpayPayment;

abstract class AbstractActionController extends \Magento\Framework\App\Action\Action
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
     * AbstractActionController constructor.
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
        $incrementId = $this->_checkoutSession->getLastRealOrderId();
        return $this->getOrder($incrementId);
    }

    /**
     * Get order by IncrementId
     *
     * @param $incrementId
     * @return \Magento\Sales\Model\Order
     */
    protected function _getOrderByIncrementId($incrementId)
    {
        return $this->getOrder($incrementId);
    }

    /**
     * Get order object
     * @param mixed $incrementId
     * @return \Magento\Sales\Model\Order
     */
    private function getOrder($incrementId)
    {
        return $this->_orderFactory->create()->loadByIncrementId($incrementId);
    }

    /**
     * Set the order details
     *
     * @param \Magento\Sales\Model\Order $order
     */
    protected function setOrderDetails($order)
    {
        $message = __("Order placed and is now awaiting payment authorization");
        $order->addStatusHistoryComment($message);
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

    /**
     * Process the callback data
     *
     * @param \Magento\Sales\Model\Order $order $order
     * @param \Bambora\Online\Model\Method\AbstractPayment $paymentMethodInstance
     * @param string $txnId
     * @param string $methodReference
     * @param string $ccType
     * @param string $ccNumber
     * @param mixed $feeAmountInMinorUnits
     * @param mixed $minorUnits
     * @param mixed $status
     * @param \Magento\Sales\Model\Order\Payment $payment
     * @return void
     */
    protected function _processCallbackData($order, $paymentMethodInstance, $txnId, $methodReference, $ccType, $ccNumber, $feeAmountInMinorUnits, $minorUnits, $status, $payment = null, $fraudStatus = 0)
    {
        try
        {
            if(!isset($payment))
            {
                $payment = $order->getPayment();
            }
            $storeId = $order->getStoreId();
            $this->updatePaymentData($order, $txnId, $methodReference, $ccType, $ccNumber, $paymentMethodInstance, $status, $fraudStatus);

            if($paymentMethodInstance->getConfigData(BamboraConstants::ADD_SURCHARGE_TO_PAYMENT, $storeId) == 1 && $feeAmountInMinorUnits > 0)
            {
                $this->addSurchargeItemToOrder($order, $feeAmountInMinorUnits, $minorUnits, $ccType);
            }

            if (!$order->getEmailSent() && $paymentMethodInstance->getConfigData(BamboraConstants::SEND_MAIL_ORDER_CONFIRMATION, $storeId) == 1)
            {
                $this->sendOrderEmail($order);
            }

            if($paymentMethodInstance->getConfigData(BamboraConstants::INSTANT_INVOICE, $storeId) == 1)
            {
                if($paymentMethodInstance->getConfigData(BamboraConstants::REMOTE_INTERFACE, $storeId) == 1 || $paymentMethodInstance->getConfigData(BamboraConstants::INSTANT_CAPTURE, $storeId) == 1)
                {
                    $this->createInvoice($order, $paymentMethodInstance, $txnId);
                }
                else
                {
                    $order->addStatusHistoryComment(__("Could not use instant invoice.") . ' - ' . __("Please enable remote payment processing from the module configuration"));
                    $order->save();
                }
            }
        }
        catch(\Exception $ex)
        {
            throw $ex;
        }
    }

    /**
     * Update the order and payment informations
     *
     * @param \Magento\Sales\Model\Order $order
     * @param string $txnId
     * @param string $methodReference
     * @param string $ccType
     * @param string $ccNumber
     * @param \Bambora\Online\Model\Method\AbstractPayment $paymentMethodInstance
     * @param mixed $status
     * @param mixed $fraudStatus
     * @return void
     */
    private function updatePaymentData($order, $txnId, $methodReference, $ccType, $ccNumber, $paymentMethodInstance, $status, $fraudStatus)
    {
        /** @var \Magento\Sales\Model\Order\Payment */
        $payment = $order->getPayment();
        $payment->setTransactionId($txnId);
        $payment->setIsTransactionClosed(false);
        $payment->setAdditionalInformation(array($methodReference => $txnId));
        $transactionComment = __("Payment authorization was a success.");
        if($fraudStatus == 1)
        {
            $payment->setIsFraudDetected(true);
            $order->setStatus(Order::STATUS_FRAUD);
            $order->setState(Order::STATE_PAYMENT_REVIEW);
            $transactionComment = __("Fraud was detected on the payment");
        }
        else
        {
            $order->setStatus($status);
            $order->setState(Order::STATE_PROCESSING);
        }

        $transaction = $payment->addTransaction(Transaction::TYPE_AUTH);
        $payment->addTransactionCommentsToOrder($transaction, $transactionComment);
        $payment->setCcType($ccType);
        $payment->setCcNumberEnc($ccNumber);

        $isInstantCapture = intval($paymentMethodInstance->getConfigData(BamboraConstants::INSTANT_CAPTURE, $order->getStoreId())) === 1 ? true : false;
        $payment->setAdditionalInformation(BamboraConstants::INSTANT_CAPTURE, $isInstantCapture);
        $payment->save();

        $order->save();
    }

    /**
     * Add Surcharge item to the order as a order line
     *
     * @param \Magento\Sales\Model\Order $order
     * @param mixed $feeAmountInMinorUnits
     * @param mixed $minorUnits
     * @param mixed $ccType
     * @return void
     */
    private function addSurchargeItemToOrder($order, $feeAmountInMinorUnits, $minorUnits, $ccType)
    {
        foreach($order->getAllItems() as $item)
        {
            if($item->getSku() === BamboraConstants::BAMBORA_SURCHARGE)
            {
                return;
            }
        }

        $baseFeeAmount = floatval($this->_bamboraHelper->convertPriceFromMinorUnits($feeAmountInMinorUnits, $minorUnits));
        $feeAmount = $order->getStore()->getBaseCurrency()->convert($baseFeeAmount, $order->getOrderCurrencyCode());

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var \Magento\Sales\Model\Order\Item */
        $feeItem = $objectManager->create('\Magento\Sales\Model\Order\Item');
        $feeItem->setSku(BamboraConstants::BAMBORA_SURCHARGE);

        $text = $ccType . ' - ' . __("Surcharge fee");
        $feeItem->setName($text);
        $feeItem->setBaseCost($baseFeeAmount);
        $feeItem->setBasePrice($baseFeeAmount);
        $feeItem->setBasePriceInclTax($baseFeeAmount);
        $feeItem->setBaseOriginalPrice($baseFeeAmount);
        $feeItem->setBaseRowTotal($baseFeeAmount);
        $feeItem->setBaseRowTotalInclTax($baseFeeAmount);
        $feeItem->setCost($feeAmount);
        $feeItem->setPrice($feeAmount);
        $feeItem->setPriceInclTax($feeAmount);
        $feeItem->setOriginalPrice($feeAmount);
        $feeItem->setRowTotal($feeAmount);
        $feeItem->setRowTotalInclTax($feeAmount);
        $feeItem->setProductType(\Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL);
        $feeItem->setIsVirtual(1);
        $feeItem->setQtyOrdered(1);
        $feeItem->setStoreId($order->getStoreId());
        $feeItem->setOrderId($order->getId());

        $order->addItem($feeItem);

        $order->setBaseGrandTotal($order->getBaseGrandTotal() + $baseFeeAmount);
        $order->setBaseSubtotal($order->getBaseSubtotal() + $baseFeeAmount);
        $order->setGrandTotal($order->getGrandTotal() + $feeAmount);
        $order->setSubtotal($order->getSubtotal() + $feeAmount);


        $feeMessage = $text . ' ' .__("added to order");
        $order->addStatusHistoryComment($feeMessage);
        $order->save();
    }

    /**
     * Send the orderconfirmation mail to the customer
     *
     * @param \Magento\Sales\Model\Order $order
     * @return void
     */
    private function sendOrderEmail($order)
    {
        try
        {
            $this->_orderSender->send($order);
            $order->addStatusHistoryComment(__("Notified customer about order #%1", $order->getId()))
                        ->setIsCustomerNotified(1)
                        ->save();
        }
        catch(\Exception $ex)
        {
            $order->addStatusHistoryComment(__("Could not send order confirmation for order #%1", $order->getId()))
                        ->setIsCustomerNotified(0)
                        ->save();
        }

    }

    /**
     * Create an invoice and capture it
     *
     * @param \Magento\Sales\Model\Order $order
     * @param \Bambora\Online\Model\Method\AbstractPayment $paymentMethodInstance
     */
    private function createInvoice($order, $paymentMethodInstance, $txnId)
    {
        if($order->canInvoice())
        {
            /** @var \Magento\Sales\Model\Order\Invoice */
            $invoice = $order->prepareInvoice();
            $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
            $invoice->register();
            $invoice->save();
            $transactionSave = $this->_objectManager->create('Magento\Framework\DB\Transaction')
                ->addObject($invoice)
                ->addObject($invoice->getOrder());
            $transactionSave->save();

            if($paymentMethodInstance->getConfigData(BamboraConstants::INSTANT_INVOICE_MAIL, $order->getStoreId()) == 1)
            {
                $invoice->setEmailSent(1);
                $this->_invoiceSender->send($invoice);
                $order->addStatusHistoryComment(__("Notified customer about invoice #%1", $invoice->getId()))
                    ->setIsCustomerNotified(1)
                    ->save();
            }
        }
    }

    /**
     * Set Callback Response
     *
     * @param mixed $statusCode
     * @param mixed $message
     * @param mixed $id
     * @param string $paymentMethod
     * @return mixed
     */
    protected function _getResult($statusCode, $message, $id, $paymentMethod = null)
    {
        $result = $this->_resultJsonFactory->create();
        $result->setHttpResponseCode($statusCode);

        $result->setData(
            ['id'=>$id,
            'message'=>$message]);

        if($statusCode === Response::HTTP_OK)
        {
            if($paymentMethod === CheckoutPayment::METHOD_CODE)
            {
                $this->_bamboraLogger->addCheckoutInfo($id,$message);
            }
            elseif($paymentMethod === EpayPayment::METHOD_CODE)
            {
                $this->_bamboraLogger->addEpayInfo($id,$message);
            }
            else
            {
                $this->_bamboraLogger->addInfo($message);
            }
        }
        else
        {
            if($paymentMethod === CheckoutPayment::METHOD_CODE)
            {
                $this->_bamboraLogger->addCheckoutError($id,$message);
            }
            elseif($paymentMethod === EpayPayment::METHOD_CODE)
            {
                $this->_bamboraLogger->addEpayError($id,$message);
            }
            else
            {
                $this->_bamboraLogger->addError($message);
            }
        }

        return $result;
    }
}