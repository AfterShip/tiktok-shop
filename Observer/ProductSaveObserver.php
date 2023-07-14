<?php
/**
 * TikTokShop ProductSaveObserver
 * php version 7.1.0
 *
 * @category  AfterShip
 * @package   TikTokShop
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
 * ProductSaveObserver
 *
 * @category AfterShip
 * @package  TikTokShop
 * @author   AfterShip <apps@aftership.com>
 * @license  MIT http://opensource.org/licenses/MIT
 * @link     https://aftership.com
 */
class ProductSaveObserver implements ObserverInterface
{
    /**
     * ProductRepositoryInterface
     *
     * @var ProductRepositoryInterface 
     */
    protected $productRepository;
    /**
     * Configurable
     *
     * @var Configurable 
     */
    protected $configurable;
    /**
     * Grouped
     *
     * @var Grouped 
     */
    protected $grouped;
    /**
     * Bundle
     *
     * @var Bundle 
     */
    protected $bundle;
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
     * @param ProductRepositoryInterface $productRepository '$productRepository'
     * @param Configurable               $configurable      '$configurable'
     * @param Grouped                    $grouped           '$grouped'
     * @param Bundle                     $bundle            '$bundle'
     * @param WebhookHelper              $webhookHelper     '$webhookHelper'
     * @param LoggerInterface            $logger            '$logger'
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
     * @param Observer $observer 'observer'
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
            $topic = (count($parentIds) === 0) ? Constants::WEBHOOK_TOPIC_PRODUCTS_UPDATE : Constants::WEBHOOK_TOPIC_VARIANTS_UPDATE;
            // Send webhook.
            $this->webhookHelper->makeWebhookRequest(
                $topic, [
                "id" => $productId,
                "type_id" => $product->getTypeId(),
                "sku" => $product->getSku(),
                "visibility" => (string)$product->getVisibility(),
                ]
            );
            // Fix updated time for parent product.
            foreach ($parentIds as $parentId) {
                   $parentProduct = $this->productRepository->getById($parentId);
                   $parentProduct->setData('updated_at', date('Y-m-d H:i:s'));
                   $this->productRepository->save($parentProduct);
            }
        } catch (\Exception $e) {
            $this->logger->error(sprintf('[AfterShip TikTokShop] Faield to update product data on ProductSaveObserver, %s', $e->getMessage()));
        }
    }
}