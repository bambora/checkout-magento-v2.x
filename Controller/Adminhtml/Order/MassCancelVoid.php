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

class MassCancelVoid extends \Magento\Sales\Controller\Adminhtml\Order\AbstractMassAction
{
    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Ui\Component\MassAction\Filter $filter
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $collectionFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Ui\Component\MassAction\Filter $filter,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $collectionFactory
    ) {
        parent::__construct($context, $filter);
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Hold selected orders
     *
     * @param \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection $collection
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    protected function massAction(\Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection $collection)
    {
        $countCanceledOrder = 0;
        $canceled = array();
        $notCanceled = array();

        /** @var \Magento\Sales\Model\Order $order */
        foreach ($collection->getItems() as $order)
        {
            try
            {
                if(!$order->canCancel())
                {
                    $notCanceled[] = $order->getIncrementId(). '('.__("Cancel not available"). ')';
                    continue;
                }

                $order->cancel();
                $order->save();
                $countCanceledOrder++;
                $canceled[] = $order->getIncrementId();
            }
            catch(\Exception $ex)
            {
                $notCanceled[] = $order->getIncrementId(). '('.$ex->getMessage().')';;
                continue;
            }
        }

        $countNonCanceledOrder = $collection->count() - $countCanceledOrder;

        if ($countNonCanceledOrder && $countCanceledOrder) {
            $this->messageManager->addError(__("%1 order(s) were not canceled or voided.", $countNonCanceledOrder). ' (' .implode(" , ", $notCanceled) . ')');
        } elseif ($countNonCanceledOrder) {
            $this->messageManager->addError(__("No order(s) were canceled or voided."). ' (' .implode(" , ", $notCanceled) . ')');
        }

        if ($countCanceledOrder) {
            $this->messageManager->addSuccess(__("You have canceled and voided %1 order(s).", $countCanceledOrder). ' (' .implode(" , ", $canceled) . ')');
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath($this->getComponentRefererUrl());
        return $resultRedirect;
    }
}