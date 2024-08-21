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

class HandleCreditmemoStockAfterRefund
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
     * Sets status and state for order
     *
     * @param CreditmemoService   $subject
     * @param CreditmemoInterface $result
     * @return CreditmemoInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterRefund(CreditmemoService $subject, CreditmemoInterface $creditmemo) {
        try {
            // todo@gerald debug log
            $this->logger->info(
                sprintf('[AfterShip TikTokShop HandleCreditmemoStockBeforeRefund] handle stock after refund, id: %d', $creditmemo->getEntityId()));

            $actions = $this->request->getHeader(Constants::HEADER_INVENTORY_BEHAVIOUR, '');
            $actions = explode(',', $actions);
            if (in_array(Constants::HEADER_INVENTORY_BEHAVIOUR_VALUE_INCREMENT, $actions)&&(!($this->isMSIEnabled()))){
                // todo@gerald debug log
                $this->logger->info(
                    sprintf('[AfterShip TikTokShop HandleCreditmemoStockBeforeRefund] version 2.3 MSI NOT Enabled'));
                $this->updateStockItemQty($creditmemo);
            }
        }catch (\Exception $e) {
            $this->logger->error(
                sprintf(
                    '[AfterShip TikTokShop HandleCreditmemoStockBeforeRefund] Failed to handle stock after refund, msg %s',
                    $e->getMessage()
                )
            );
        }

        // 返回参数数组
        return $creditmemo;
    }

    /**
     * @param CreditmemoInterface $creditmemo
     * @return void
     */
    private function updateStockItemQty(CreditmemoInterface $creditmemo)
    {
        $itemsById =[];
        foreach ($creditmemo->getItems() as $item) {
            if (!isset($itemsById[$item->getProductId()])) {
                $itemsById[$item->getProductId()] = 0;
            }
            $itemsById[$item->getProductId()] += $item->getQty();
        }

        $this->logger->info(
            sprintf('[AfterShip TikTokShop HandleCreditmemoStockBeforeRefund] complete qty calculation %s', var_export($itemsById, true)));

        $websiteId = $this->stockConfiguration->getDefaultScopeId();

        // todo@gerald debug log
        $this->logger->info(
            sprintf('[AfterShip TikTokShop HandleCreditmemoStockBeforeRefund] get websiteid %d', $websiteId));

        $items = [];
        $itemsForReindex = [];
        try{
            $items = $this->stockManagement->revertProductsSale($itemsById, $websiteId);
        }catch (\Exception $e) {
            $this->logger->error(
                sprintf(
                    '[AfterShip TikTokShop HandleCreditmemoStockBeforeRefund] Failed to revertProductsSale msg %s',
                    $e->getMessage()
                )
            );
            return;
        }

        // todo@gerald debug log
        $this->logger->info(
            sprintf('[AfterShip TikTokShop HandleCreditmemoStockBeforeRefund] revertProductsSale success'));

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
