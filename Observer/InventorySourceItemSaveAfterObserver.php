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
use Magento\Framework\Api\ExtensionAttributesFactory;
use AfterShip\TikTokShop\Api\Data\ProductChangeType;
use Magento\Framework\ObjectManagerInterface;

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
     * Extension attributes factory
     *
     * @var ExtensionAttributesFactory
     */
    protected $extensionAttributesFactory;

    /**
     * Object manager
     *
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Construct
     *
     * @param LoggerInterface $logger
     * @param ProductRepositoryInterface $productRepository
     * @param WebhookPublisher $publisher
     * @param CommonHelper $commonHelper
     * @param Configurable $configurableProduct
     * @param ExtensionAttributesFactory $extensionAttributesFactory
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        LoggerInterface $logger,
        ProductRepositoryInterface $productRepository,
        WebhookPublisher $publisher,
        CommonHelper $commonHelper,
        Configurable $configurableProduct,
        ExtensionAttributesFactory $extensionAttributesFactory,
        ObjectManagerInterface $objectManager
    ) {
        $this->logger = $logger;
        $this->productRepository = $productRepository;
        $this->publisher = $publisher;
        $this->commonHelper = $commonHelper;
        $this->configurableProduct = $configurableProduct;
        $this->extensionAttributesFactory = $extensionAttributesFactory;
        $this->objectManager = $objectManager;
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
                $this->logger->warning(
                    '[AfterShip TikTokShop] InventorySourceItemSaveAfterObserver do not handle during performance test'
                );
                return;
            }

            $sourceItems = $observer->getData('items') ?? [];

            // process source items from import
            $this->_processSourceItems($sourceItems);
        } catch (\Exception $e) {
            $this->logger->warning(
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
                $productIdsToNotify[] = $product->getId();

            } catch (NoSuchEntityException $e) {
                $this->logger->warning(
                    sprintf(
                        '[AfterShip TikTokShop] Import sku not found, sku: %s, error: %s',
                        $sourceItem->getSku(),
                        $e->getMessage()
                    )
                );
            } catch (\Exception $e) {
                $this->logger->warning(
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
                $event = $this->objectManager->create(WebhookEvent::class);
                $event->setId($productId)
                    ->setResource(Constants::WEBHOOK_RESOURCE_PRODUCTS)
                    ->setEvent(Constants::WEBHOOK_EVENT_UPDATE);
                
                $extensionAttributes = null;
                if (method_exists($event, 'getExtensionAttributes')) {
                    $extensionAttributes = $event->getExtensionAttributes();
                }
                if (!$extensionAttributes) {
                    $extensionAttributes = $this->extensionAttributesFactory->create(
                        WebhookEvent::class
                    );
                }
                $extensionAttributes->setProductChangeType(ProductChangeType::STOCK);
                if (method_exists($event, 'setExtensionAttributes')) {
                    $event->setExtensionAttributes($extensionAttributes);
                }
                
                $this->publisher->execute($event);
            } catch (\Exception $e) {
                $this->logger->warning(
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
