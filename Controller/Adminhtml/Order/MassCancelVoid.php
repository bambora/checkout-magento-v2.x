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

        $collectionItems = $collection->getItems();
        /** @var \Magento\Sales\Model\Order $order */
        foreach ($collectionItems as $order) {
            try {
                if (!$order->canCancel()) {
                    $notCanceled[] = $order->getIncrementId(). '('.__("Cancel not available"). ')';
                    continue;
                }

                $order->cancel();
                $order->save();
                $countCanceledOrder++;
                $canceled[] = $order->getIncrementId();
            } catch (\Exception $ex) {
                $notCanceled[] = $order->getIncrementId(). '('.$ex->getMessage().')';
                continue;
            }
        }

        $countNonCanceledOrder = count($collectionItems) - $countCanceledOrder;

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
