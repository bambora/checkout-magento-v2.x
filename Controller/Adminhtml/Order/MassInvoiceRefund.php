<?php
namespace Bambora\Online\Controller\Adminhtml\Order;

class MassInvoiceRefund extends \Magento\Sales\Controller\Adminhtml\Order\AbstractMassAction
{
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory
     */
    protected $_invoiceCollectionFactory;

    /**
     * @var \Magento\Sales\Model\Order\CreditmemoFactory
     */
    protected $_creditmemoFactory;

    /**
     * @var \Magento\Sales\Model\Service\CreditmemoService
     */
    protected $_creditmemoService;

    /**
     * Mass Invoice Refund Action
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Ui\Component\MassAction\Filter $filter
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $collectionFactory
     * @param \Magento\Sales\Model\Order\CreditmemoFactory $creditmemoFactory
     * @param \Magento\Sales\Model\Service\CreditmemoService $creditmemoService
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Ui\Component\MassAction\Filter $filter,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $collectionFactory,
        \Magento\Sales\Model\Order\CreditmemoFactory $creditmemoFactory,
        \Magento\Sales\Model\Service\CreditmemoService $creditmemoService
    ) {
        parent::__construct($context, $filter);
        $this->collectionFactory = $collectionFactory;
        $this->_creditmemoFactory = $creditmemoFactory;
        $this->_creditmemoService = $creditmemoService;
    }

    /**
     * Hold selected orders
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection $collection
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    protected function massAction(
        \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection $collection
    ) {
        $countRefundInvoices = 0;
        $refunded = [];
        $notRefunded = [];

        $collectionItems = $collection->getItems();
        foreach ($collectionItems as $invoice) {
            try {
                if (!$invoice->canRefund()) {
                    $notRefunded[] = $invoice->getIncrementId() . '(' .
                    __('Creditmemo not available') . ')';
                    continue;
                }

                $creditMemo = $this->_creditmemoFactory->createByInvoice($invoice);
                if (!$creditMemo->canRefund()) {
                    continue;
                }

                $this->_creditmemoService->refund($creditMemo);

                $countRefundInvoices++;
                $refunded[] = $invoice->getIncrementId();
            } catch (\Exception $ex) {
                $notRefunded[] = $invoice->getIncrementId() . '(' . $ex->getMessage(
                ) . ')';
                continue;
            }
        }
        $countNonRefundInvoice = count($collectionItems) - $countRefundInvoices;

        if ($countNonRefundInvoice && $countRefundInvoices) {
            $this->messageManager->addError(
                __(
                    "%1 invoice(s) were not refunded.",
                    $countNonRefundInvoice
                ) . ' (' . implode(" , ", $notRefunded) . ')'
            );
        } elseif ($countNonRefundInvoice) {
            $this->messageManager->addError(
                __("No invoice(s) were refunded.") . ' (' . implode(
                    " , ",
                    $notRefunded
                ) . ')'
            );
        }

        if ($countRefundInvoices) {
            $this->messageManager->addSuccess(
                __(
                    "You have refunded %1 invoice(s).",
                    $countRefundInvoices
                ) . ' (' . implode(" , ", $refunded) . ')'
            );
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath($this->getComponentRefererUrl());
        return $resultRedirect;
    }
}
