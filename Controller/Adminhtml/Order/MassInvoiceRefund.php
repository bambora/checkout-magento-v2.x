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
     * @param \Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory $invoiceCollectionFactory
     * @param \Magento\Sales\Model\Order\CreditmemoFactory $creditmemoFactory
     * @param \Magento\Sales\Model\Service\CreditmemoService $creditmemoService
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Ui\Component\MassAction\Filter $filter,
        \Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory $invoiceCollectionFactory,
        \Magento\Sales\Model\Order\CreditmemoFactory $creditmemoFactory,
        \Magento\Sales\Model\Service\CreditmemoService $creditmemoService
    ) {
        parent::__construct($context, $filter);
        $this->collectionFactory = $invoiceCollectionFactory;
        $this->_creditmemoFactory = $creditmemoFactory;
        $this->_creditmemoService = $creditmemoService;

    }

    /**
     * Hold selected orders
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection $collection
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    protected function massAction(\Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection $collection)
    {
        $countRefundInvoices = 0;
        $refunded = array();
        $notRefunded = array();

        /** @var \Magento\Sales\Model\Order\Invoice $invoice */
        foreach($collection->getItems() as $invoice)
        {
            try
            {
                if(!$invoice->canRefund())
                {
                    $notRefunded[] = $invoice->getIncrementId(). '('.__("Creditmemo not available"). ')';
                    continue;
                }

                $creditMemo = $this->_creditmemoFactory->createByInvoice($invoice);
                if(!$creditMemo->canRefund())
                {
                    continue;
                }

                $this->_creditmemoService->refund($creditMemo);

                $countRefundInvoices++;
                $refunded[] = $invoice->getIncrementId();
            }
            catch(\Exception $ex)
            {
                $notRefunded[] = $invoice->getIncrementId(). '('.$ex->getMessage().')';
                continue;
            }
        }
        $countNonRefundInvoice = $collection->count() - $countRefundInvoices;

        if ($countNonRefundInvoice && $countRefundInvoices) {
            $this->messageManager->addError(__("%1 invoice(s) were not refunded.", $countNonRefundInvoice). ' (' .implode(" , ", $notRefunded) . ')');
        } elseif ($countNonRefundInvoice) {
            $this->messageManager->addError(__("No invoice(s) were refunded."). ' (' .implode(" , ", $notRefunded) . ')');
        }

        if ($countRefundInvoices) {
            $this->messageManager->addSuccess(__("You have refunded %1 invoice(s).", $countRefundInvoices). ' (' .implode(" , ", $refunded) . ')');
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath($this->getComponentRefererUrl());
        return $resultRedirect;
    }
}