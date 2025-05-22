<?php
/**
 * TikTokShop InventorySourceItemSaveAfterObserver
 *
 * @author    AfterShip <support@aftership.com>
 * @copyright 2023 AfterShip
 * @license   MIT http://opensource.org/licenses/MIT
 * @link      https://aftership.com
 */
namespace AfterShip\TikTokShop\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Psr\Log\LoggerInterface;
use AfterShip\TikTokShop\Constants;
use AfterShip\TikTokShop\Model\Api\WebhookEvent;
use AfterShip\TikTokShop\Model\Queue\WebhookPublisher;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use AfterShip\TikTokShop\Helper\CommonHelper;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

/**
 * Observer for inventory source item save after
 */
class InventorySourceItemSaveAfterObserver implements ObserverInterface
{
    /**
     * Logger instance
     * 
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Product repository instance
     *
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * Publisher Instance.
     *
     * @var WebhookPublisher
     */
    protected $publisher;

    /**
     * Common Helper Instance.
     * 
     * @var CommonHelper
     */
    protected $commonHelper;

    /**
     * Configurable product type instance
     *
     * @var Configurable
     */
    protected $configurableProduct;

    /**
     * Construct
     * 
     * @param LoggerInterface $logger
     * @param ProductRepositoryInterface $productRepository
     * @param WebhookPublisher $publisher
     * @param CommonHelper $commonHelper
     * @param Configurable $configurableProduct
     */
    public function __construct(
        LoggerInterface $logger,
        ProductRepositoryInterface $productRepository,
        WebhookPublisher $publisher,
        CommonHelper $commonHelper,
        Configurable $configurableProduct
    ) {
        $this->logger = $logger;
        $this->productRepository = $productRepository;
        $this->publisher = $publisher;
        $this->commonHelper = $commonHelper;
        $this->configurableProduct = $configurableProduct;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * 
     * @return void
     */
    public function execute(Observer $observer)
    {
        $this->logger->info('InventorySourceItemSaveAfterObserver start');
        try {
            if ($this->commonHelper->isRunningUnderPerformanceTest()) {
                $this->logger->error(
                    '[AfterShip TikTokShop] InventorySourceItemSaveAfterObserver do not sync inventory during performance test'
                );
                return;
            }

            $sourceItems = $observer->getData('items');

            $this->logger->info(
                'InventorySourceItemSaveAfterObserver init data',
                [
                    'source_items_count' => count($sourceItems),
                    'items' => $sourceItems
                ]
            );

            // 处理每个 source item
            $this->_processSourceItems($sourceItems);
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf(
                    'InventorySourceItemSaveAfterObserver errors: %s',
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * 处理库存更新并发送 webhook
     *
     * @param array $sourceItems
     * 
     * @return void
     */
    private function _processSourceItems(array $sourceItems)
    {
        // 用于存储需要发送 webhook 的产品 ID
        $productIdsToNotify = [];
        
        // 处理每个 source item
        foreach ($sourceItems as $sourceItem) {
            try {
                $sku = $sourceItem->getSku();
                $product = $this->productRepository->get($sku);
                $productType = $product->getTypeId();
                
                $this->logger->info(
                    'InventorySourceItemSaveAfterObserver - Source Item 详情',
                    [
                        'sku' => $sku,
                        'product_id' => $product->getId(),
                        'source_code' => $sourceItem->getSourceCode(),
                        'quantity' => $sourceItem->getQuantity(),
                        'status' => $sourceItem->getStatus(),
                        'product_name' => $product->getName(),
                        'product_type' => $productType
                    ]
                );

                // 根据产品类型处理
                switch ($productType) {
                case 'simple':
                case 'virtual':
                case 'downloadable':
                    // 检查是否有父产品
                    $parentIds = $this->configurableProduct->getParentIdsByChild($product->getId());
                    if (!empty($parentIds)) {
                        // 如果是可配置产品的子产品，添加到父产品列表
                        $productIdsToNotify = array_merge($productIdsToNotify, $parentIds);
                        $this->logger->info(
                            'InventorySourceItemSaveAfterObserver - 找到父产品',
                            [
                                'child_id' => $product->getId(),
                                'parent_ids' => $parentIds
                            ]
                        );
                    } else {
                        // 独立的简单产品，直接添加
                        $productIdsToNotify[] = $product->getId();
                    }
                    break;
                        
                default:
                    // 其他类型产品
                    $productIdsToNotify[] = $product->getId();
                    break;
                }
            } catch (NoSuchEntityException $e) {
                $this->logger->error(
                    'InventorySourceItemSaveAfterObserver - 未找到对应商品',
                    [
                        'sku' => $sourceItem->getSku(),
                        'error' => $e->getMessage()
                    ]
                );
            } catch (\Exception $e) {
                $this->logger->error(
                    'InventorySourceItemSaveAfterObserver - 处理异常',
                    [
                        'sku' => $sourceItem->getSku(),
                        'error' => $e->getMessage()
                    ]
                );
            }
        }

        // 去重并发送 webhook
        $uniqueProductIds = array_unique($productIdsToNotify);
        $this->logger->info(
            'InventorySourceItemSaveAfterObserver - 准备发送 webhook',
            [
                'total_products' => count($uniqueProductIds),
                'product_ids' => $uniqueProductIds
            ]
        );

        foreach ($uniqueProductIds as $productId) {
            try {
                $event = new WebhookEvent();
                $event->setId($productId)
                    ->setResource(Constants::WEBHOOK_RESOURCE_PRODUCTS)
                    ->setEvent(Constants::WEBHOOK_EVENT_UPDATE);
                $this->publisher->execute($event);

                $this->logger->info(
                    'InventorySourceItemSaveAfterObserver - Webhook 发送成功',
                    ['product_id' => $productId]
                );
            } catch (\Exception $e) {
                $this->logger->error(
                    'InventorySourceItemSaveAfterObserver - Webhook 发送失败',
                    [
                        'product_id' => $productId,
                        'error' => $e->getMessage()
                    ]
                );
            }
        }
    }
} 