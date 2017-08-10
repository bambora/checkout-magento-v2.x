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

class MassDelete extends \Magento\Sales\Controller\Adminhtml\Order\AbstractMassAction
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
        $countDeleteOrder = 0;
        $deleted = array();
        $notDeleted = array();

        $collectionItems = $collection->getItems();
        /** @var \Magento\Sales\Model\Order $order */
        foreach ($collectionItems as $order) {
            try {
                $order->delete();
                $countDeleteOrder++;
                $deleted[] = $order->getIncrementId();
            } catch (\Exception $ex) {
                $notDeleted[] = $order->getIncrementId(). '('.$ex->getMessage().')';
                ;
                continue;
            }
        }

        $countNonDeleteOrder = count($collectionItems) - $countDeleteOrder;

        if ($countNonDeleteOrder && $countDeleteOrder) {
            $this->messageManager->addError(__("%1 order(s) were not deleted.", $countNonDeleteOrder). ' (' .implode(" , ", $notDeleted) . ')');
        } elseif ($countNonDeleteOrder) {
            $this->messageManager->addError(__("No order(s) were deleted."). ' (' .implode(" , ", $notDeleted) . ')');
        }

        if ($countDeleteOrder) {
            $this->messageManager->addSuccess(__("You have deleted %1 order(s).", $countDeleteOrder). ' (' .implode(" , ", $deleted) . ')');
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath($this->getComponentRefererUrl());
        return $resultRedirect;
    }
}
