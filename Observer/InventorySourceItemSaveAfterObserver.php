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
     * @param Observer $observer observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            if ($this->commonHelper->isRunningUnderPerformanceTest()) {
                $this->logger->error(
                    '[AfterShip TikTokShop] InventorySourceItemSaveAfterObserver do not handle during performance test'
                );
                return;
            }

            $sourceItems = $observer->getData('items') ?? [];

            // process source items from import
            $this->_processSourceItems($sourceItems);
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf(
                    '[AfterShip TikTokShop] InventorySourceItemSaveAfterObserver execute errors: %s',
                    $e->getMessage()
                )
            );
        }
    }

    /**
     * Find product via sku and send product update webhook
     *
     * @param array $sourceItems source items
     *
     * @return void
     */
    private function _processSourceItems(array $sourceItems)
    {
        // save product ids to notify
        $productIdsToNotify = [];
        
        // handle each source item
        foreach ($sourceItems as $sourceItem) {
            try {
                $sku = $sourceItem->getSku();
                $product = $this->productRepository->get($sku);
                $productType = $product->getTypeId();

                // get product id by product type
                switch ($productType) {
                    case 'simple':
                    case 'virtual':
                    case 'downloadable':
                        // check if the product is configurable
                        $parentIds = $this->configurableProduct->getParentIdsByChild($product->getId());
                        if (!empty($parentIds)) {
                            foreach ($parentIds as $parentId) {
                                $productIdsToNotify[] = $parentId;
                            }
                        } else {
                            $productIdsToNotify[] = $product->getId();
                        }
                        break;
                    default:
                        $productIdsToNotify[] = $product->getId();
                        break;
                }
            } catch (NoSuchEntityException $e) {
                $this->logger->error(
                    sprintf(
                        '[AfterShip TikTokShop] Import sku not found, sku: %s, error: %s',
                        $sourceItem->getSku(),
                        $e->getMessage()
                    )
                );
            } catch (\Exception $e) {
                $this->logger->error(
                    sprintf(
                        '[AfterShip TikTokShop] Import sku process error, sku: %s, error: %s',
                        $sourceItem->getSku(),
                        $e->getMessage()
                    )
                );
            }
        }

        // keep unique and send webhook
        $uniqueProductIds = array_unique($productIdsToNotify);

        foreach ($uniqueProductIds as $productId) {
            try {
                $event = new WebhookEvent();
                $event->setId($productId)
                    ->setResource(Constants::WEBHOOK_RESOURCE_PRODUCTS)
                    ->setEvent(Constants::WEBHOOK_EVENT_UPDATE);
                $this->publisher->execute($event);
            } catch (\Exception $e) {
                $this->logger->error(
                    sprintf(
                        '[AfterShip TikTokShop] Send webhook failed, product_id: %s, error: %s',
                        $productId,
                        $e->getMessage()
                    )
                );
            }
        }
    }
}
