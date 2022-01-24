<?php
/**
 * Copyright (c) 2019. All rights reserved Bambora Online.
 *
 * This program is free software. You are allowed to use the software but NOT allowed to modify the software.
 * It is also not legal to do any changes to the software and distribute it in your own name / brand.
 *
 * All use of the payment modules happens at your own risk. We offer a free test account that you can use to test the module.
 *
 * @author    Bambora Online
 * @copyright Bambora Online (https://bambora.com)
 * @license   Bambora Online
 */
namespace Bambora\Online\Controller;

use Bambora\Online\Helper\BamboraConstants;
use Bambora\Online\Model\Method\Checkout\Payment as CheckoutPayment;
use Bambora\Online\Model\Method\Epay\Payment as EpayPayment;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;

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
     * Application Event Dispatcher
     *
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $_eventManager;

    /**
     * AbstractActionController constructor.
     *
     * @param \Magento\Framework\App\Action\Context                 $context
     * @param \Magento\Sales\Model\OrderFactory                     $orderFactory
     * @param \Magento\Checkout\Model\Session                       $checkoutSession
     * @param \Bambora\Online\Helper\Data                           $bamboraHelper
     * @param \Magento\Framework\Controller\Result\JsonFactory      $resultJsonFactory
     * @param \Bambora\Online\Logger\BamboraLogger                  $bamboraLogger
     * @param \Magento\Payment\Helper\Data                          $paymentHelper
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender   $orderSender
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

        $this->_eventManager = $context->getEventManager();
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
     * @param  $incrementId
     * @return \Magento\Sales\Model\Order
     */
    protected function _getOrderByIncrementId($incrementId)
    {
        return $this->getOrder($incrementId);
    }

    /**
     * Get order object
     *
     * @param  mixed $incrementId
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

    protected function acceptOrder($methodReference)
    {
        $posted = $this->getRequest()->getParams();
        if (array_key_exists('orderid', $posted)) {
            $order = $this->_getOrderByIncrementId($posted['orderid']);

            $this->_checkoutSession->setLastOrderId($order->getId());
            $this->_checkoutSession->setLastRealOrderId($order->getIncrementId());
            $this->_checkoutSession->setLastQuoteId($order->getQuoteId());
            $this->_checkoutSession->setLastSuccessQuoteId($order->getQuoteId());

            $payment = $order->getPayment();
            if(isset($payment))
            {
                $payment->setAdditionalInformation(BamboraConstants::PAYMENT_STATUS_ACCEPTED, true);
                $payment->save();
            }
        }
        $this->_redirect('checkout/onepage/success');
    }

    /**
     * Cancel the order
     */
    protected function cancelOrder()
    {
        $order = $this->_getOrder();
        if(isset($order) && $order->getId() && $order->getState() != Order::STATE_CANCELED) {
            $payment = $order->getPayment();
            if(isset($payment)) {
                $epayReference = $payment->getAdditionalInformation(EpayPayment::METHOD_REFERENCE);
                $checkoutReference = $payment->getAdditionalInformation(CheckoutPayment::METHOD_REFERENCE);
                $paymentStatusAccepted = $payment->getAdditionalInformation(BamboraConstants::PAYMENT_STATUS_ACCEPTED);
                if(empty($epayReference) && empty($checkoutReference) && $paymentStatusAccepted != true) {
                    $comment =  __("The order was canceled through the payment window");
                    $orderIncrementId = $order->getIncrementId();
                    $this->_bamboraLogger->addCheckoutInfo($orderIncrementId, $comment);
                    $order->addStatusHistoryComment($comment);
                    $order->cancel();

                    //Restore Quote
                    $this->_checkoutSession->restoreQuote();
                } else {
                    $comment = __("Order cancelling attempt avoided");
                    $order->addStatusHistoryComment($comment);
                }
                $order->save();
            }
        }

        $this->_redirect('checkout/cart');
    }

    /**
     * Get Payment method instance object
     *
     * @param  string $method
     * @return {MethodInstance}
     */
    protected function _getPaymentMethodInstance($method)
    {
        return $this->_paymentHelper->getMethodInstance($method);
    }

    /**
     * Process the callback data
     *
     * @param  \Magento\Sales\Model\Order                   $order                 $order
     * @param  \Bambora\Online\Model\Method\AbstractPayment $paymentMethodInstance
     * @param  string                                       $txnId
     * @param  string                                       $methodReference
     * @param  string                                       $ccType
     * @param  string                                       $ccNumber
     * @param  mixed                                        $feeAmountInMinorUnits
     * @param  mixed                                        $minorUnits
     * @param  mixed                                        $status
     * @param  boolean                                      $isInstantCapture
     * @param  \Magento\Sales\Model\Order\Payment           $payment
     * @return void
     */
    protected function _processCallbackData($order, $paymentMethodInstance, $txnId, $methodReference, $ccType, $ccNumber, $feeAmountInMinorUnits, $minorUnits, $status, $isInstantCapture, $payment = null, $fraudStatus = 0)
    {
        try {
            if (!isset($payment)) {
                $payment = $order->getPayment();
            }
            $storeId = $order->getStoreId();
            $this->updatePaymentData($order, $txnId, $methodReference, $ccType, $ccNumber, $paymentMethodInstance, $status, $isInstantCapture, $fraudStatus);

            if ($paymentMethodInstance->getConfigData(BamboraConstants::ADD_SURCHARGE_TO_PAYMENT, $storeId) == 1 && $feeAmountInMinorUnits > 0) {
                $this->addSurchargeToOrder($order, $feeAmountInMinorUnits, $minorUnits, $ccType, $paymentMethodInstance);
            }

            if (!$order->getEmailSent() && $paymentMethodInstance->getConfigData(BamboraConstants::SEND_MAIL_ORDER_CONFIRMATION, $storeId) == 1) {
                $this->sendOrderEmail($order);
            }
            if ($isInstantCapture) {
                $this->createInvoice($order, $paymentMethodInstance, false);
            }
            if (!$isInstantCapture && $paymentMethodInstance->getConfigData(BamboraConstants::INSTANT_INVOICE, $storeId) == 1) {
                $this->createInvoice($order, $paymentMethodInstance, true) ;
            }
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Update the order and payment informations
     *
     * @param  \Magento\Sales\Model\Order                   $order
     * @param  string                                       $txnId
     * @param  string                                       $methodReference
     * @param  string                                       $ccType
     * @param  string                                       $ccNumber
     * @param  \Bambora\Online\Model\Method\AbstractPayment $paymentMethodInstance
     * @param  mixed                                        $status
     * @param  boolean                                      $isInstantCapture
     * @param  mixed                                        $fraudStatus
     * @return void
     */
    public function updatePaymentData($order, $txnId, $methodReference, $ccType, $ccNumber, $paymentMethodInstance, $status, $isInstantCapture, $fraudStatus)
    {
        try {
            $payment = $order->getPayment();
            $payment->setTransactionId($txnId);
            $payment->setIsTransactionClosed(false);
            $payment->setAdditionalInformation([$methodReference => $txnId]);
            $transactionComment = __("Payment authorization was a success.");
            if ($fraudStatus == 1) {
                $payment->setIsFraudDetected(true);
                $order->setStatus(Order::STATUS_FRAUD);
                $transactionComment = __("Fraud was detected on the payment");
            } else {
                $order->setStatus($status);
            }
            $storeId = $order->getStoreId();
            $orderCurrentState = $order->getState();
            if ($orderCurrentState === Order::STATE_CANCELED && $paymentMethodInstance->getConfigData(BamboraConstants::UNCANCEL_ORDER_LINES, $storeId) == 1) {
                $this->unCancelOrderItems($order);
            }

            $order->setState(Order::STATE_PROCESSING);
            $transaction = $payment->addTransaction(Transaction::TYPE_AUTH);
            $payment->addTransactionCommentsToOrder($transaction, $transactionComment);

            if ($order->getPayment()->getMethod() === \Bambora\Online\Model\Method\Epay\Payment::METHOD_CODE) {
                $ccType = $this->_bamboraHelper->calcCardtype($ccType);
            }

            $payment->setCcType($ccType);
            $payment->setCcNumberEnc($ccNumber);

            $payment->setAdditionalInformation(BamboraConstants::INSTANT_CAPTURE, $isInstantCapture);
            $payment->save();

            $order->save();
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    /**
     * Un-Cancel order lines
     *
     * @param \Magento\Sales\Model\Order $order
     */
    public function unCancelOrderItems($order)
    {
        try {
            $productStockQty = [];
            foreach ($order->getAllVisibleItems() as $item) {
                $productStockQty[$item->getProductId()] = $item->getQtyCanceled();
                foreach ($item->getChildrenItems() as $child) {
                    $productStockQty[$child->getProductId()] = $item->getQtyCanceled();
                    $child->setQtyCanceled(0);
                    $child->setTaxCanceled(0);
                    $child->setDiscountTaxCompensationCanceled(0);
                }
                $item->setQtyCanceled(0);
                $item->setTaxCanceled(0);
                $item->setDiscountTaxCompensationCanceled(0);
            }
            $this->_eventManager->dispatch(
                'sales_order_manage_inventory',
                [
                    'order' => $order,
                    'product_qty' => $productStockQty
                ]
            );
            $order->setSubtotalCanceled(0);
            $order->setBaseSubtotalCanceled(0);
            $order->setTaxCanceled(0);
            $order->setBaseTaxCanceled(0);
            $order->setShippingCanceled(0);
            $order->setBaseShippingCanceled(0);
            $order->setDiscountCanceled(0);
            $order->setBaseDiscountCanceled(0);
            $order->setTotalCanceled(0);
            $order->setBaseTotalCanceled(0);
            $comment = __("The order was un-canceled by the Bambora Checkout Callback");
            $order->addStatusHistoryComment($comment, false);
            $order->save();
            $this->_bamboraLogger->addCheckoutInfo($order->getId(), $comment);
        } catch (\Exception $ex) {
            $comment = __("The order could not be un-canceled - Reason:") .$ex->getMessage();
            $order->addStatusHistoryComment($comment, false);
            $order->save();
            $this->_bamboraLogger->addCheckoutInfo($order->getId(), $comment);
        }
    }

    /**
     * Add Surcharge to the order
     *
     * @param  \Magento\Sales\Model\Order                   $order
     * @param  mixed                                        $feeAmountInMinorunits
     * @param  mixed                                        $minorunits
     * @param  mixed                                        $ccType
     * @param  \Bambora\Online\Model\Method\AbstractPayment $paymentMethodInstance
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
     * @param  \Magento\Sales\Model\Order $order
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
     * @param \Magento\Sales\Model\Order                   $order
     * @param \Bambora\Online\Model\Method\AbstractPayment $paymentMethodInstance
     * @param boolean
     */
    public function createInvoice($order, $paymentMethodInstance, $isOnlineCapture = true)
    {
        try {
            if ($order->canInvoice()) {
                $invoice = $order->prepareInvoice();
                $storeId = $order->getStoreId();

                if ($isOnlineCapture) {
                    $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
                } else {
                    $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
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
            $order->addStatusHistoryComment(__("Could not create or Capture the Invoice for order #%1 - Reason: %2", $order->getId(), $ex->getMessage()))
                ->setIsCustomerNotified(0)
                ->save();
        }
    }

    /**
     * Log Error
     *
     * @param string $paymentMethod
     * @param mixed  $id
     * @param mixed  $errorMessage
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
     * @param  mixed $statusCode
     * @param  mixed $message
     * @param  mixed $id
     * @return mixed
     */
    protected function _createCallbackResult($statusCode, $message, $id)
    {
        $result = $this->_resultJsonFactory->create();
        $result->setHttpResponseCode($statusCode);

        $result->setData(
            ['id'=>$id,
            'message'=>$message]
        );

        return $result;
    }
}
