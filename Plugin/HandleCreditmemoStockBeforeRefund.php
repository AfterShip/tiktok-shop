<?php

namespace AfterShip\TikTokShop\Plugin;

use AfterShip\TikTokShop\Constants;
use Magento\Sales\Api\CreditmemoManagementInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Model\Service\CreditmemoService;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Observer\ItemsForReindex;
use Magento\CatalogInventory\Model\Spi\StockRegistryProviderInterface;
use Magento\CatalogInventory\Model\StockManagement;
use Psr\Log\LoggerInterface;

class HandleCreditmemoStockBeforeRefund
{

    /**
     * LoggerInterface Instance.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var ModuleManager
     */
    protected $moduleManager;

    /**
     * @var ItemsForReindex
     */
    protected $itemsForReindex;

    /**
     * @var StockManagement
     */
    protected $stockManagement;

    /**
     * @var StockConfigurationInterface
     */
    protected $stockConfiguration;

    /**
     * @var StockRegistryProviderInterface
     */
    protected $stockRegistryProvider;

    public function __construct(
        LoggerInterface  $logger,
        RequestInterface $request,
        ModuleManager $moduleManager,
        ItemsForReindex $itemsForReindex,
        StockManagement $stockManagement,
        StockConfigurationInterface $stockConfiguration,
        StockRegistryProviderInterface $stockRegistryProvider
    ) {
        $this->logger = $logger;
        $this->request = $request;
        $this->moduleManager = $moduleManager;
        $this->itemsForReindex = $itemsForReindex;
        $this->stockManagement = $stockManagement;
        $this->stockConfiguration = $stockConfiguration;
        $this->stockRegistryProvider = $stockRegistryProvider;
    }

    /**
     * Before plugin for refund method
     *
     * @param CreditmemoManagementInterface $subject
     * @param CreditmemoInterface $creditmemo
     * @param bool $offlineRequested
     * @return array
     */
    public function beforeRefund(
        CreditmemoManagementInterface $subject,
        CreditmemoInterface $creditmemo,
        $offlineRequested = false
    ) {
        // todo@gerald debug log
        $this->logger->info(
            sprintf('[AfterShip TikTokShop HandleCreditmemoStockBeforeRefund] handle stock before refund'));

        $actions = $this->request->getHeader(Constants::HEADER_INVENTORY_BEHAVIOUR, '');
        $actions = explode(',', $actions);
        if (in_array(Constants::HEADER_INVENTORY_BEHAVIOUR_VALUE_INCREMENT, $actions)&&($this->isMSIEnabled())){
            // todo@gerald debug log
            $this->logger->info(
                sprintf('[AfterShip TikTokShop HandleCreditmemoStockBeforeRefund] begin to set back to stock'));

            foreach ($creditmemo->getAllItems() as $creditmemoItem){
                $creditmemoItem->setBackToStock(true);
            }
        }

        // 返回参数数组
        return [$creditmemo, $offlineRequested];
    }

//    /**
//     * Before plugin for refund method
//     *
//     * @param CreditmemoManagementInterface $subject
//     * @param CreditmemoInterface $creditmemo
//     * @param bool $offlineRequested
//     * @return array
//     */
//    public function afterRefund(
//        CreditmemoService   $subject,
//        CreditmemoInterface $result,
//        CreditmemoInterface $creditmemo,
//                            $offlineRequested = false
//    ) {
////        try {
////            // todo@gerald debug log
////            $this->logger->info(
////                sprintf('[AfterShip TikTokShop HandleCreditmemoStockBeforeRefund] handle stock after refund, id: %d', $creditmemo->getEntityId()));
////
////            if (!($this->isMSIEnabled())){
////                // todo@gerald debug log
////                $this->logger->info(
////                    sprintf('[AfterShip TikTokShop HandleCreditmemoStockBeforeRefund] MSI NOT Enabled'));
////
////                $order = $creditmemo->getOrder();
////                $this->updateStockItemQty($order);
////            }
////        }catch (\Exception $e) {
////            $this->logger->error(
////                sprintf(
////                    '[AfterShip TikTokShop HandleCreditmemoStockBeforeRefund] Failed to handle stock after refund, msg %s',
////                    $e->getMessage()
////                )
////            );
////        }
//
//        // 返回参数数组
//        return [$creditmemo, $offlineRequested];
//    }

    /**
     * @param OrderInterface $order
     * @return void
     */
    private function updateStockItemQty(OrderInterface $order)
    {
        $itemsById =[];
        foreach ($order->getItems() as $item) {
            if (!isset($itemsById[$item->getProductId()])) {
                $itemsById[$item->getProductId()] = 0;
            }
            $itemsById[$item->getProductId()] += $item->getQtyOrdered();
        }
        $websiteId = $this->stockConfiguration->getDefaultScopeId();

        // todo@gerald debug log
        $this->logger->info(
            sprintf('[AfterShip TikTokShop HandleCreditmemoStockBeforeRefund] get websiteid %d', $websiteId));

        $items = $this->stockManagement->revertProductsSale($itemsById, $websiteId);
        $itemsForReindex = [];
        foreach ($items as $productId => $qty){
            $stockItem = $this->stockRegistryProvider->getStockItem($productId, $websiteId);
            $itemsForReindex [] = $stockItem;
        }
        if (count($itemsForReindex)) {
            $this->itemsForReindex->setItems($itemsForReindex);
        }
    }


    /**
     * @return bool
     */
    private function isMSIEnabled()
    {
        return $this->moduleManager->isEnabled('Magento_Inventory');
    }

}
