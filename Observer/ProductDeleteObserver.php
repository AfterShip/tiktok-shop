<?php
/**
 * TikTokShop ProductDeleteObserver
 *
 * @author    AfterShip <apps@aftership.com>
 * @copyright 2023 AfterShip
 * @license   MIT http://opensource.org/licenses/MIT
 * @link      https://aftership.com
 */

namespace AfterShip\TikTokShop\Observer;

use AfterShip\TikTokShop\Constants;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use AfterShip\TikTokShop\Helper\WebhookHelper;
use Psr\Log\LoggerInterface;

/**
 * Send webhook when get product delete event.
 *
 * @author   AfterShip <apps@aftership.com>
 * @license  MIT http://opensource.org/licenses/MIT
 * @link     https://aftership.com
 */
class ProductDeleteObserver implements ObserverInterface
{
    /**
     * ProductRepositoryInterface
     *
     * @var ProductRepositoryInterface
     */
    protected $productRepository;
    /**
     * WebhookHelper
     *
     * @var WebhookHelper
     */
    protected $webhookHelper;
    /**
     * LoggerInterface
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Construct
     *
     * @param ProductRepositoryInterface $productRepository
     * @param WebhookHelper              $webhookHelper
     * @param LoggerInterface            $logger
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        WebhookHelper              $webhookHelper,
        LoggerInterface            $logger
    ) {
        $this->productRepository = $productRepository;
        $this->logger = $logger;
        $this->webhookHelper = $webhookHelper;
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
            /* @var \Magento\Catalog\Model\Product $product */
            $product = $observer->getEvent()->getProduct();
            $productId = $product->getId();
            // Send webhook.
            $this->webhookHelper->makeWebhookRequest(
                Constants::WEBHOOK_TOPIC_PRODUCTS_DELETE,
                [
                "id" => $productId,
                "type_id" => $product->getTypeId(),
                "sku" => $product->getSku(),
                "visibility" => (string)$product->getVisibility(),
                ]
            );
        } catch (\Exception $e) {
            $this->logger->error(sprintf('[AfterShip TikTokShop] Faield to send webhook on ProductDeleteObserver, %s', $e->getMessage()));
        }
    }
}
