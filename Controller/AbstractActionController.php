<?php
/**
 * Copyright (c) 2017. All rights reserved Bambora Online.
 *
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software.
 * It is also not legal to do any changes to the software and distribute it in your own name / brand.
 *
 * All use of the payment modules happens at your own risk. We offer a free test account that you can use to test the module.
 *
 * @author    Bambora Online
 * @copyright Bambora Online (http://bambora.com)
 * @license   Bambora Online
 *
 */
namespace Bambora\Online\Controller;

use \Magento\Sales\Model\Order;
use \Magento\Sales\Model\Order\Payment\Transaction;
use \Bambora\Online\Helper\BamboraConstants;
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
    public function getOrder($incrementId)
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
        if (array_key_exists('orderid', $posted)) {
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
            $this->_bamboraLogger->addCheckoutInfo($order->getIncrementId(), $comment);
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
        try {
            if (!isset($payment)) {
                $payment = $order->getPayment();
            }
            $storeId = $order->getStoreId();
            $this->updatePaymentData($order, $txnId, $methodReference, $ccType, $ccNumber, $paymentMethodInstance, $status, $fraudStatus);

            if ($paymentMethodInstance->getConfigData(BamboraConstants::ADD_SURCHARGE_TO_PAYMENT, $storeId) == 1 && $feeAmountInMinorUnits > 0) {
                $this->addSurchargeToOrder($order, $feeAmountInMinorUnits, $minorUnits, $ccType, $paymentMethodInstance);
            }

            if (!$order->getEmailSent() && $paymentMethodInstance->getConfigData(BamboraConstants::SEND_MAIL_ORDER_CONFIRMATION, $storeId) == 1) {
                $this->sendOrderEmail($order);
            }

            if ($paymentMethodInstance->getConfigData(BamboraConstants::INSTANT_INVOICE, $storeId) == 1) {
                $this->createInvoice($order, $paymentMethodInstance);
            }
        } catch (\Exception $ex) {
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
    public function updatePaymentData($order, $txnId, $methodReference, $ccType, $ccNumber, $paymentMethodInstance, $status, $fraudStatus)
    {
        try {
            /** @var \Magento\Sales\Model\Order\Payment */
            $payment = $order->getPayment();
            $payment->setTransactionId($txnId);
            $payment->setIsTransactionClosed(false);
            $payment->setAdditionalInformation(array($methodReference => $txnId));
            $transactionComment = __("Payment authorization was a success.");
            if ($fraudStatus == 1) {
                $payment->setIsFraudDetected(true);
                $order->setStatus(Order::STATUS_FRAUD);
                $transactionComment = __("Fraud was detected on the payment");
            } else {
                $order->setStatus($status);
            }

            $order->setState(Order::STATE_PROCESSING);
            $transaction = $payment->addTransaction(Transaction::TYPE_AUTH);
            $payment->addTransactionCommentsToOrder($transaction, $transactionComment);

            if ($order->getPayment()->getMethod() === \Bambora\Online\Model\Method\Epay\Payment::METHOD_CODE) {
                $ccType = $this->_bamboraHelper->calcCardtype($ccType);
            }

            $payment->setCcType($ccType);
            $payment->setCcNumberEnc($ccNumber);

            $isInstantCapture = intval($paymentMethodInstance->getConfigData(BamboraConstants::INSTANT_CAPTURE, $order->getStoreId())) === 1 ? true : false;
            $payment->setAdditionalInformation(BamboraConstants::INSTANT_CAPTURE, $isInstantCapture);
            $payment->save();

            $order->save();
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Add Surcharge to the order
     *
     * @param \Magento\Sales\Model\Order $order
     * @param mixed $feeAmountInMinorunits
     * @param mixed $minorunits
     * @param mixed $ccType
     * @param \Bambora\Online\Model\Method\AbstractPayment $paymentMethodInstance
     * @return void
     */
    public function addSurchargeToOrder($order, $feeAmountInMinorunits, $minorunits, $ccType, $paymentMethodInstance)
    {
        try {
            foreach ($order->getAllItems() as $item) {
                if ($item->getSku() === BamboraConstants::BAMBORA_SURCHARGE) {
                    return;
                }
            }

            $baseFeeAmount = $this->_bamboraHelper->convertPriceFromMinorunits($feeAmountInMinorunits, $minorunits);
            $feeAmount = $order->getStore()->getBaseCurrency()->convert($baseFeeAmount, $order->getOrderCurrencyCode());
            $text = $ccType . ' - ' . __("Surcharge fee");
            $storeId = $order->getStoreId();

            if ($paymentMethodInstance->getConfigData(BamboraConstants::SURCHARGE_MODE, $storeId) === BamboraConstants::SURCHARGE_ORDER_LINE) {
                $feeItem = $this->_bamboraHelper->createSurchargeItem($baseFeeAmount, $feeAmount, $storeId, $order->getId(), $text);
                $order->addItem($feeItem);
                $order->setBaseSubtotal($order->getBaseSubtotal() + $baseFeeAmount);
                $order->setBaseSubtotalInclTax($order->getBaseSubtotalInclTax() + $baseFeeAmount);
                $order->setSubtotal($order->getSubtotal() + $feeAmount);
                $order->setSubtotalInclTax($order->getSubtotalInclTax() + $feeAmount);
            } else {
                //Add fee to shipment
                $order->setBaseShippingAmount($order->getBaseShippingAmount() + $baseFeeAmount);
                $order->setBaseShippingInclTax($order->getBaseShippingInclTax() + $baseFeeAmount);
                $order->setShippingAmount($order->getShippingAmount() + $feeAmount);
                $order->setShippingInclTax($order->getShippingInclTax() + $feeAmount);
            }

            $order->setBaseGrandTotal($order->getBaseGrandTotal() + $baseFeeAmount);
            $order->setGrandTotal($order->getGrandTotal() + $feeAmount);

            $feeMessage = $text . ' ' .__("added to order");
            $order->addStatusHistoryComment($feeMessage);
            $order->save();
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Send the orderconfirmation mail to the customer
     *
     * @param \Magento\Sales\Model\Order $order
     * @return void
     */
    public function sendOrderEmail($order)
    {
        try {
            $this->_orderSender->send($order);
            $order->addStatusHistoryComment(__("Notified customer about order #%1", $order->getId()))
                        ->setIsCustomerNotified(1)
                        ->save();
        } catch (\Exception $ex) {
            $order->addStatusHistoryComment(__("Could not send order confirmation for order #%1", $order->getId()))
                        ->setIsCustomerNotified(0)
                        ->save();
        }
    }

    /**
     * Create an invoice
     *
     * @param \Magento\Sales\Model\Order $order
     * @param \Bambora\Online\Model\Method\AbstractPayment $paymentMethodInstance
     */
    public function createInvoice($order, $paymentMethodInstance)
    {
        try {
            if ($order->canInvoice()) {
                /** @var \Magento\Sales\Model\Order\Invoice */
                $invoice = $order->prepareInvoice();
                $storeId = $order->getStoreId();

                if ((int)$paymentMethodInstance->getConfigData(BamboraConstants::INSTANT_CAPTURE, $storeId) === 1) {
                    $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
                }

                $invoice->register();
                $invoice->save();
                $transactionSave = $this->_objectManager->create('Magento\Framework\DB\Transaction')
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder());
                $transactionSave->save();

                if ($paymentMethodInstance->getConfigData(BamboraConstants::INSTANT_INVOICE_MAIL, $order->getStoreId()) == 1) {
                    $invoice->setEmailSent(1);
                    $this->_invoiceSender->send($invoice);
                    $order->addStatusHistoryComment(__("Notified customer about invoice #%1", $invoice->getId()))
                        ->setIsCustomerNotified(1)
                        ->save();
                }
            }
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Log Error
     *
     * @param string $paymentMethod
     * @param mixed $id
     * @param mixed $errorMessage
     */
    protected function _logError($paymentMethod, $id, $errorMessage)
    {
        if ($paymentMethod === CheckoutPayment::METHOD_CODE) {
            $this->_bamboraLogger->addCheckoutError($id, $errorMessage);
        } elseif ($paymentMethod === EpayPayment::METHOD_CODE) {
            $this->_bamboraLogger->addEpayError($id, $errorMessage);
        } else {
            $this->_bamboraLogger->addError($errorMessage);
        }
    }

    /**
     * Get Callback Response
     *
     * @param mixed $statusCode
     * @param mixed $message
     * @param mixed $id
     * @return mixed
     */
    protected function _createCallbackResult($statusCode, $message, $id)
    {
        $result = $this->_resultJsonFactory->create();
        $result->setHttpResponseCode($statusCode);

        $result->setData(
            ['id'=>$id,
            'message'=>$message]);

        return $result;
    }
}
