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
namespace Bambora\Online\Controller\Adminhtml\Order;

use \Bambora\Online\Helper\BamboraConstants;
use \Bambora\Online\Model\Method\Checkout\Payment as CheckoutPayment;
use \Bambora\Online\Model\Method\Epay\Payment as EpayPayment;

class MassInvoiceCapture extends \Magento\Sales\Controller\Adminhtml\Order\AbstractMassAction
{
    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\InvoiceSender
     */
    protected $_invoiceSender;

    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $_paymentHelper;

    /**
     * @var \Bambora\Online\Helper\Data
     */
    protected $_bamboraHelper;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Ui\Component\MassAction\Filter $filter
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $collectionFactory
     * @param \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender
     * @param \Magento\Payment\Helper\Data $paymentHelper
     * @param \Bambora\Online\Logger\BamboraLogger $bamboraLogger
     * @param \Bambora\Online\Helper\Data $bamboraHelper
     * @param \Magento\Sales\Model\Order\CreditmemoFactory $creditmemoFactory
     * @param \Magento\Sales\Model\Service\CreditmemoService $creditmemoService
     * @param \Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory $invoiceCollectionFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Ui\Component\MassAction\Filter $filter,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $collectionFactory,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Payment\Helper\Data $paymentHelper,
        \Bambora\Online\Helper\Data $bamboraHelper
    ) {
        parent::__construct($context, $filter);
        $this->collectionFactory = $collectionFactory;
        $this->_invoiceSender = $invoiceSender;
        $this->_paymentHelper = $paymentHelper;
        $this->_bamboraHelper = $bamboraHelper;
    }

    /**
     * Hold selected orders
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection $collection
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    protected function massAction(\Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection $collection)
    {
        $countInvoicedOrder = 0;
        $invoiced = array();
        $notInvoiced = array();

        $collectionItems = $collection->getItems();
        /** @var \Magento\Sales\Model\Order $order */
        foreach ($collectionItems as $order) {
            try {
                if (!$order->canInvoice()) {
                    $notInvoiced[] = $order->getIncrementId(). '('.__("Invoice not available"). ')';
                    continue;
                }

                /** @var \Magento\Sales\Model\Order\Invoice */
                $invoice = $order->prepareInvoice();
                $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
                $invoice->register();
                $invoice->save();

                $transactionSave = $this->_objectManager->create('Magento\Framework\DB\Transaction')
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder());
                $transactionSave->save();

                $payment = $order->getPayment();
                $paymentMethod = $payment->getMethod();
                if ($paymentMethod === CheckoutPayment::METHOD_CODE || $paymentMethod === EpayPayment::METHOD_CODE) {
                    $methodInstance = $this->_paymentHelper->getMethodInstance($paymentMethod);
                    if ($methodInstance->getConfigData(BamboraConstants::MASS_CAPTURE_INVOICE_MAIL, $order->getStoreId()) == 1) {
                        $invoice->setEmailSent(1);
                        $this->_invoiceSender->send($invoice);
                        $order->addStatusHistoryComment(__("Notified customer about invoice #%1", $invoice->getIncrementId()))
                            ->setIsCustomerNotified(1)
                            ->save();
                    }
                }

                $countInvoicedOrder++;
                $invoiced[] = $order->getIncrementId();
            } catch (\Exception $ex) {
                $notInvoiced[] = $order->getIncrementId(). '('.$ex->getMessage().')';
                continue;
            }
        }
        $countNonInvoicedOrder = count($collectionItems) - $countInvoicedOrder;

        if ($countNonInvoicedOrder && $countInvoicedOrder) {
            $this->messageManager->addError(__("%1 order(s) cannot be Invoiced and Captured.", $countNonInvoicedOrder). ' (' .implode(" , ", $notInvoiced) . ')');
        } elseif ($countNonInvoicedOrder) {
            $this->messageManager->addError(__("You cannot Invoice and Capture the order(s)."). ' (' .implode(" , ", $notInvoiced) . ')');
        }

        if ($countInvoicedOrder) {
            $this->messageManager->addSuccess(__("You Invoiced and Captured %1 order(s).", $countInvoicedOrder). ' (' .implode(" , ", $invoiced) . ')');
        }
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath($this->getComponentRefererUrl());
        return $resultRedirect;
    }
}
