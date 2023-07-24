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
     * Construct
     *
     * @param LoggerInterface $logger
     * @param WebhookPublisher $publisher
     * @param CommonHelper $commonHelper
     */
    public function __construct(
        LoggerInterface  $logger,
        WebhookPublisher $publisher,
        CommonHelper $commonHelper
    ) {
        $this->logger = $logger;
        $this->publisher = $publisher;
        $this->commonHelper = $commonHelper;
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
            $event = new WebhookEvent();
            $event->setId($productId)
                ->setResource(Constants::WEBHOOK_RESOURCE_PRODUCTS)
                ->setEVent(Constants::WEBHOOK_EVENT_UPDATE);
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
