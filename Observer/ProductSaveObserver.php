<?php
/**
 * TikTokShop ProductSaveObserver
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
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Bundle\Model\Product\Type as Bundle;
use AfterShip\TikTokShop\Helper\WebhookHelper;
use Psr\Log\LoggerInterface;

/**
 * Send webhook when get product update event.
 *
 * @author   AfterShip <apps@aftership.com>
 * @license  MIT http://opensource.org/licenses/MIT
 * @link     https://aftership.com
 */
class ProductSaveObserver implements ObserverInterface
{
    /**
     * ProductRepositoryInterface Instance.
     *
     * @var ProductRepositoryInterface
     */
    protected $productRepository;
    /**
     * Configurable Instance.
     *
     * @var Configurable
     */
    protected $configurable;
    /**
     * Grouped Instance.
     *
     * @var Grouped
     */
    protected $grouped;
    /**
     * Bundle Instance.
     *
     * @var Bundle
     */
    protected $bundle;
    /**
     * WebhookHelper Instance.
     *
     * @var WebhookHelper
     */
    protected $webhookHelper;
    /**
     * LoggerInterface Instance.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Construct
     *
     * @param ProductRepositoryInterface $productRepository
     * @param Configurable               $configurable
     * @param Grouped                    $grouped
     * @param Bundle                     $bundle
     * @param WebhookHelper              $webhookHelper
     * @param LoggerInterface            $logger
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        Configurable               $configurable,
        Grouped                    $grouped,
        Bundle                     $bundle,
        WebhookHelper              $webhookHelper,
        LoggerInterface            $logger
    ) {
        $this->productRepository = $productRepository;
        $this->configurable = $configurable;
        $this->grouped = $grouped;
        $this->bundle = $bundle;
        $this->logger = $logger;
        $this->webhookHelper = $webhookHelper;
    }

    /**
     * GetParentProductIds
     *
     * @param string $productId 'productId'
     *
     * @return array
     */
    public function getParentProductIds($productId)
    {
        $configurableParentIds = $this->configurable->getParentIdsByChild($productId);
        $groupedParentIds = $this->grouped->getParentIdsByChild($productId);
        $bundleParentIds = $this->bundle->getParentIdsByChild($productId);
        return array_merge($configurableParentIds, $groupedParentIds, $bundleParentIds);
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
            $parentIds = $this->getParentProductIds($productId);
            $topic = (count($parentIds) === 0) ?
                Constants::WEBHOOK_TOPIC_PRODUCTS_UPDATE :
                Constants::WEBHOOK_TOPIC_VARIANTS_UPDATE;
            // Send webhook.
            $this->webhookHelper->makeWebhookRequest(
                $topic,
                [
                "id" => $productId,
                "type_id" => $product->getTypeId(),
                "sku" => $product->getSku(),
                "visibility" => (string)$product->getVisibility(),
                ]
            );
            // Fix updated time for parent product.
            foreach ($parentIds as $parentId) {
                   $parentProduct = $this->productRepository->getById($parentId);
                    $this->webhookHelper->makeWebhookRequest(
                        Constants::WEBHOOK_TOPIC_PRODUCTS_UPDATE,
                        [
                            "id" => $parentProduct->getId(),
                            "type_id" => $parentProduct->getTypeId(),
                            "sku" => $parentProduct->getSku(),
                            "visibility" => (string)$parentProduct->getVisibility(),
                        ]
                    );
            }
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
