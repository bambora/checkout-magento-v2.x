<?php
namespace Bambora\Online\Observer;

class ManageInventoryObserver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\CatalogInventory\Api\StockManagementInterface
     */
    protected $stockManagement;

    /**
     * @var \Magento\CatalogInventory\Model\Indexer\Stock\Processor
     */
    protected $stockIndexerProcessor;

    /**
     * @var \Bambora\Online\Logger\BamboraLogger
     */
    protected $_bamboraLogger;

    /**
     * ManageInventoryObeserver constructor.
     *
     * @param \Magento\CatalogInventory\Api\StockManagementInterface $stockManagement
     * @param \Magento\CatalogInventory\Model\Indexer\Stock\Processor $stockIndexerProcessor
     * @param \Bambora\Online\Logger\BamboraLogger $bamboraLogger
     */
    public function __construct(
        \Magento\CatalogInventory\Api\StockManagementInterface $stockManagement,
        \Magento\CatalogInventory\Model\Indexer\Stock\Processor $stockIndexerProcessor,
        \Bambora\Online\Logger\BamboraLogger $bamboraLogger
    ) {
        $this->stockManagement = $stockManagement;
        $this->stockIndexerProcessor = $stockIndexerProcessor;
        $this->_bamboraLogger = $bamboraLogger;
    }

    /**
     * Subtract the un-cancled products from the store inventory.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $productQty = $observer->getEvent()->getProductQty();
        if ($order->getInventoryProcessed()) {
            return $this;
        }
        $itemsForReindex = $this->stockManagement->registerProductsSale(
            $productQty,
            $order->getStore()->getWebsiteId()
        );
        $productIds = [];
        foreach ($itemsForReindex as $item) {
            $item->save();
            $productIds[] = $item->getProductId();
        }
        if (!empty($productIds)) {
            $this->stockIndexerProcessor->reindexList($productIds);
        }
        $order->setInventoryProcessed(true);
        return $this;
    }
}
