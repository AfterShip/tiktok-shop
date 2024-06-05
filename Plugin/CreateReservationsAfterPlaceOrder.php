<?php

namespace AfterShip\TikTokShop\Plugin;

use AfterShip\TikTokShop\Constants;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\RequestInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\Data\SalesEventExtensionInterface;
use Magento\InventorySalesApi\Api\Data\SalesEventInterface;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Framework\Module\Manager as ModuleManager;
use Psr\Log\LoggerInterface;

class CreateReservationsAfterPlaceOrder
{
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var WebsiteRepositoryInterface
     */
    protected $websiteRepository;
    /**
     * @var RequestInterface
     */
    protected $request;
    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;
    /**
     * @var StockRegistryInterface
     */
    protected $stockRegistry;
    /**
     * @var ModuleManager
     */
    protected $moduleManager;



    public function __construct(
        LoggerInterface $logger,
        WebsiteRepositoryInterface $websiteRepository,
        RequestInterface $request,
        ProductMetadataInterface $productMetadata,
        StockRegistryInterface $stockRegistry,
        ModuleManager $moduleManager
    ) {
        $this->logger = $logger;
        $this->websiteRepository = $websiteRepository;
        $this->request = $request;
        $this->productMetadata = $productMetadata;
        $this->stockRegistry = $stockRegistry;
        $this->moduleManager = $moduleManager;
    }

    /**
     * @param OrderRepositoryInterface $subject
     * @param OrderInterface $order
     * @return OrderInterface
     */
    public function afterSave(
        OrderRepositoryInterface $subject,
        OrderInterface $order
    ) {
        try {
            $actions = $this->request->getHeader(Constants::HEADER_INVENTORY_BEHAVIOUR, '');
            $actions = explode(',', $actions);
            if (in_array('decrement', $actions)) {
                if ($this->isMSIEnabled()) {
                    $this->sendSalesEvent($order);
                } else {
                    $this->updateStockItemQty($order);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf(
                    '[AfterShip TikTokShop] Failed to create reservation for order %s, %s',
                    $order->getIncrementId(),
                    $e->getMessage()
                )
            );
        }
        return $order;
    }

    /**
     * @param OrderInterface $order
     * @return void
     */
    private function updateStockItemQty(OrderInterface $order)
    {
        foreach ($order->getItems() as $item) {
            $sku = $item->getSku();
            $stockItem = $this->stockRegistry->getStockItemBySku($sku);
            $stockItem->setQty(
                $stockItem->getQty() - $item->getQtyOrdered()
            );
            $this->stockRegistry->updateStockItemBySku($sku, $stockItem);
        }
    }

    /**
     * @param OrderInterface $order
     * @return void
     */
    private function sendSalesEvent(OrderInterface $order)
    {
        // those interfaces are not available in Magento 2.3, so we need to use ObjectManager
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $placeReservationsForSalesEvent = $objectManager->get(\Magento\InventorySalesApi\Api\PlaceReservationsForSalesEventInterface::class);
        $salesChannelFactory = $objectManager->get(\Magento\InventorySalesApi\Api\Data\SalesChannelInterfaceFactory::class);
        $salesEventFactory = $objectManager->get(\Magento\InventorySalesApi\Api\Data\SalesEventInterfaceFactory::class);
        $itemsToSellFactory = $objectManager->get(\Magento\InventorySalesApi\Api\Data\ItemToSellInterfaceFactory::class);
        $getProductTypesBySkus = $objectManager->get(\Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface::class);
        $isSourceItemManagementAllowedForProductType = $objectManager->get(\Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface::class);
        $salesEventExtensionFactory = $objectManager->get(\Magento\InventorySalesApi\Api\Data\SalesEventExtensionFactory::class);

        $productSkus = [];
        $itemsOrdered = [];
        $itemsToSell = [];
        foreach ($order->getItems() as $item) {
            $productSkus[$item->getProductId()] = $item->getSku();
            $itemsOrdered[$item->getProductId()] = $item->getQtyOrdered();
        }
        $productTypes = $getProductTypesBySkus->execute($productSkus);
        foreach ($productSkus as $productId => $sku) {
            if (false === $isSourceItemManagementAllowedForProductType->execute($productTypes[$sku])) {
                continue;
            }

            $itemsToSell[] = $itemsToSellFactory->create([
                'sku' => $sku,
                'qty' => -(float)$itemsOrdered[$productId],
            ]);
        }

        $websiteId = (int)$order->getStore()->getWebsiteId();
        $websiteCode = $this->websiteRepository->getById($websiteId)->getCode();

        /** @var SalesEventExtensionInterface */
        $salesEventExtension = $salesEventExtensionFactory->create([
            'data' => ['objectIncrementId' => (string)$order->getIncrementId()]
        ]);

        /** @var SalesEventInterface $salesEvent */
        $salesEvent = $salesEventFactory->create([
            'type' => SalesEventInterface::EVENT_ORDER_PLACED,
            'objectType' => SalesEventInterface::OBJECT_TYPE_ORDER,
            'objectId' => (string)$order->getEntityId()
        ]);
        $salesEvent->setExtensionAttributes($salesEventExtension);
        $salesChannel = $salesChannelFactory->create([
            'data' => [
                'type' => SalesChannelInterface::TYPE_WEBSITE,
                'code' => $websiteCode
            ]
        ]);
        $placeReservationsForSalesEvent->execute($itemsToSell, $salesChannel, $salesEvent);
    }

    /**
     * @return bool
     */
    private function isMSIEnabled()
    {
        return $this->moduleManager->isEnabled('Magento_Inventory');
    }
}
