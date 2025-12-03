<?php
/**
 * TikTokShop ProductDeleteObserver
 *
 * @author    AfterShip <support@aftership.com>
 * @copyright 2023 AfterShip
 * @license   MIT http://opensource.org/licenses/MIT
 * @link      https://aftership.com
 */

namespace AfterShip\TikTokShop\Observer;

use AfterShip\TikTokShop\Constants;
use AfterShip\TikTokShop\Helper\CommonHelper;
use AfterShip\TikTokShop\Model\Api\WebhookEvent;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use AfterShip\TikTokShop\Model\Queue\WebhookPublisher;
use Psr\Log\LoggerInterface;
use Magento\Framework\ObjectManagerInterface;

/**
 * Send webhook when get product delete event.
 *
 * @author   AfterShip <support@aftership.com>
 * @license  MIT http://opensource.org/licenses/MIT
 * @link     https://aftership.com
 */
class ProductDeleteObserver implements ObserverInterface
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
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        LoggerInterface $logger,
        WebhookPublisher $publisher,
        CommonHelper $commonHelper,
        ObjectManagerInterface $objectManager
    ) {
        $this->logger = $logger;
        $this->publisher = $publisher;
        $this->commonHelper = $commonHelper;
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
            $this->logger->debug(
                '[AfterShip TikTokShop] ProductDeleteObserver triggered'
            );
            if ($this->commonHelper->isRunningUnderPerformanceTest()) {
                $this->logger->error(
                    '[AfterShip TikTokShop] ProductDeleteObserver do not sync inventory during performance test'
                );
                return;
            }
            /* @var \Magento\Catalog\Model\Product $product */
            $product = $observer->getEvent()->getProduct();
            $productId = $product->getId();
            $this->logger->debug(
                sprintf(
                    '[AfterShip TikTokShop] ProductDeleteObserver processing product ID: %s',
                    $productId
                )
            );
            $event = $this->objectManager->create(WebhookEvent::class);
            $event->setId($productId)
                ->setResource(Constants::WEBHOOK_RESOURCE_PRODUCTS)
                ->setEVent(Constants::WEBHOOK_EVENT_DELETE);
            $this->publisher->execute($event);
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf(
                    '[AfterShip TikTokShop] Failed to send webhook on ProductDeleteObserver, %s',
                    $e->getMessage()
                )
            );
        }
    }
}
