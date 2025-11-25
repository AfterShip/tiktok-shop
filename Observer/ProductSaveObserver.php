<?php
/**
 * TikTokShop ProductSaveObserver
 *
 * @author    AfterShip <support@aftership.com>
 * @copyright 2023 AfterShip
 * @license   MIT http://opensource.org/licenses/MIT
 * @link      https://aftership.com
 */

namespace AfterShip\TikTokShop\Observer;

use AfterShip\TikTokShop\Constants;
use AfterShip\TikTokShop\Helper\CommonHelper;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use AfterShip\TikTokShop\Model\Queue\WebhookPublisher;
use Psr\Log\LoggerInterface;
use AfterShip\TikTokShop\Model\Api\WebhookEvent;
use Magento\Framework\Api\ExtensionAttributesFactory;
use AfterShip\TikTokShop\Api\Data\ProductChangeType;
use Magento\Framework\ObjectManagerInterface;

/**
 * Send webhook when get product update event.
 *
 * @author   AfterShip <support@aftership.com>
 * @license  MIT http://opensource.org/licenses/MIT
 * @link     https://aftership.com
 */
class ProductSaveObserver implements ObserverInterface
{
    /**
     * LoggerInterface Instance.
     *
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * Publisher Instance.
     *
     * @var WebhookPublisher
     */
    protected $publisher;

    /**
     * Common Helper Instance.
     * @var CommonHelper
     */
    protected $commonHelper;

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
     * @param WebhookPublisher $publisher
     * @param CommonHelper $commonHelper
     * @param ExtensionAttributesFactory $extensionAttributesFactory
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        LoggerInterface  $logger,
        WebhookPublisher $publisher,
        CommonHelper $commonHelper,
        ExtensionAttributesFactory $extensionAttributesFactory,
        ObjectManagerInterface $objectManager
    ) {
        $this->logger = $logger;
        $this->publisher = $publisher;
        $this->commonHelper = $commonHelper;
        $this->extensionAttributesFactory = $extensionAttributesFactory;
        $this->objectManager = $objectManager;
    }

    /**
     * Execute
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            if ($this->commonHelper->isRunningUnderPerformanceTest()) {
                $this->logger->error(
                    '[AfterShip TikTokShop] ProductSaveObserver do not sync inventory during performance test'
                );
                return;
            }
            /* @var \Magento\Catalog\Model\Product $product */
            $product = $observer->getEvent()->getProduct();
            $productId = $product->getId();
            $event = $this->objectManager->create(WebhookEvent::class);
            $event->setId($productId)
                ->setResource(Constants::WEBHOOK_RESOURCE_PRODUCTS)
                ->setEVent(Constants::WEBHOOK_EVENT_UPDATE);
            
            $extensionAttributes = null;
            if (method_exists($event, 'getExtensionAttributes')) {
                $extensionAttributes = $event->getExtensionAttributes();
            }
            if (!$extensionAttributes) {
                $extensionAttributes = $this->extensionAttributesFactory->create(
                    WebhookEvent::class
                );
            }
            $extensionAttributes->setProductChangeType(ProductChangeType::BASIC);
            if (method_exists($event, 'setExtensionAttributes')) {
                $event->setExtensionAttributes($extensionAttributes);
            }
            
            $this->publisher->execute($event);
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf(
                    '[AfterShip TikTokShop] Faield to update product data on ProductSaveObserver, %s',
                    $e->getMessage()
                )
            );
        }
    }
}
