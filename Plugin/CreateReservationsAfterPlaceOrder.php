<?php

namespace AfterShip\TikTokShop\Plugin;

use AfterShip\TikTokShop\Constants;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\RequestInterface;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\Data\SalesEventExtensionInterface;
use Magento\InventorySalesApi\Api\Data\SalesEventInterface;
use Magento\CatalogInventory\Model\StockManagement;
use Magento\CatalogInventory\Observer\ItemsForReindex;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Framework\Module\Manager as ModuleManager;
use Psr\Log\LoggerInterface;
use Magento\Framework\Webapi\ResponseFactory;

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

    /**
     * @var StockManagement
     */
    protected $stockManagement;

    /**
     * @var ItemsForReindex
     */
    protected $itemsForReindex;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    public function __construct(
        LoggerInterface $logger,
        WebsiteRepositoryInterface $websiteRepository,
        RequestInterface $request,
        ProductMetadataInterface $productMetadata,
        StockRegistryInterface $stockRegistry,
        ModuleManager $moduleManager,
        StockManagement $stockManagement,
        ItemsForReindex $itemsForReindex,
        ResponseFactory $responseFactory
    ) {
        $this->logger = $logger;
        $this->websiteRepository = $websiteRepository;
        $this->request = $request;
        $this->productMetadata = $productMetadata;
        $this->stockRegistry = $stockRegistry;
        $this->moduleManager = $moduleManager;
        $this->stockManagement = $stockManagement;
        $this->itemsForReindex = $itemsForReindex;
        $this->responseFactory = $responseFactory;
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
        $result = [
            'order_id' => $order->getIncrementId(),
            'status' => 'not_executed', // 默认状态：未执行
            'method' => '',
            'message' => ''
        ];

        try {
            $actions = $this->request->getHeader(Constants::HEADER_INVENTORY_BEHAVIOUR, '');
            $actions = explode(',', $actions);
            if (in_array('decrement', $actions)) {
                if ($this->isMSIEnabled()) {
                    $this->sendSalesEvent($order);

                    $result['status'] = 'success';
                    $result['method'] = 'sendSalesEvent';
                } else {
                    $this->updateStockItemQty($order);

                    $result['status'] = 'success';
                    $result['method'] = 'updateStockItemQty';
                }
            }
        } catch (\Exception $e) {
            $result['status'] = 'failed';
            $result['message'] = $e->getMessage();
            $this->logger->error(
                sprintf(
                    '[AfterShip TikTokShop] Failed to create reservation for order %s, %s',
                    $order->getIncrementId(),
                    $e->getMessage()
                )
            );
        }

        // 设置 response header
        $this->setInventoryReservationHeader($result);

        return $order;
    }

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
        $websiteId = (int)$order->getStore()->getWebsiteId();
        $itemsForReindex = $this->stockManagement->registerProductsSale($itemsById, $websiteId);
        if (count($itemsForReindex)) {
            $this->itemsForReindex->setItems($itemsForReindex);
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
        $getSkusByProductIds = $objectManager->get(\Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface::class);
        $stockByWebsiteIdResolver = $objectManager->get(\Magento\InventorySalesApi\Model\StockByWebsiteIdResolverInterface::class);
        $checkItemsQuantity = $objectManager->get(\Magento\InventorySales\Model\CheckItemsQuantity::class);

        $itemsById = $itemsBySku = $itemsToSell = [];
        foreach ($order->getItems() as $item) {
            if (!isset($itemsById[$item->getProductId()])) {
                $itemsById[$item->getProductId()] = 0;
            }
            $itemsById[$item->getProductId()] += $item->getQtyOrdered();
        }
        $productSkus = $getSkusByProductIds->execute(array_keys($itemsById));
        $productTypes = $getProductTypesBySkus->execute($productSkus);

        foreach ($productSkus as $productId => $sku) {
            if (false === $isSourceItemManagementAllowedForProductType->execute($productTypes[$sku])) {
                continue;
            }

            $itemsBySku[$sku] = (float)$itemsById[$productId];
            $itemsToSell[] = $itemsToSellFactory->create([
                'sku' => $sku,
                'qty' => -(float)$itemsById[$productId]
            ]);
        }

        $websiteId = (int)$order->getStore()->getWebsiteId();
        $websiteCode = $this->websiteRepository->getById($websiteId)->getCode();
        $stockId = (int)$stockByWebsiteIdResolver->execute((int)$websiteId)->getStockId();

        $checkItemsQuantity->execute($itemsBySku, $stockId);

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
        
        $this->logger->info(
            sprintf(
                '[AfterShip TikTokShop] Processing to create reservation for order %s',
                $order->getIncrementId()
            )
        );
        $placeReservationsForSalesEvent->execute($itemsToSell, $salesChannel, $salesEvent);
    }

    /**
     * @return bool
     */
    private function isMSIEnabled()
    {
        return $this->moduleManager->isEnabled('Magento_Inventory');
    }

    /**
     * 设置库存扣减结果的 header
     *
     * @param array $result
     * @return void
     */
    private function setInventoryReservationHeader(array $result)
    {
        try {
            $response = $this->responseFactory->create();
            $response->setHeader(
                'X-AS-Inventory-Reservation-Result',
                json_encode($result),
                true
            );
            $this->logger->info(
                sprintf(
                    '[AfterShip TikTokShop] response header X-AS-Inventory-Reservation-Result: %s',
                    json_encode($result)
                )
            );
            // 确保响应被发送
            $response->sendResponse();
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf(
                    '[AfterShip TikTokShop] Failed to set inventory reservation header: %s',
                    $e->getMessage()
                )
            );
        }
    }
}
