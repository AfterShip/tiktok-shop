<?php
/**
 * TikTokShop InventoryUpdateObserver
 *
 * @author    AfterShip <apps@aftership.com>
 * @copyright 2023 AfterShip
 * @license   MIT http://opensource.org/licenses/MIT
 * @link      https://aftership.com
 */

namespace AfterShip\TikTokShop\Observer;

use AfterShip\TikTokShop\Constants;
use AfterShip\TikTokShop\Helper\WebhookHelper;
use Magento\Authorization\Model\UserContextInterface;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Bundle\Model\Product\Type as Bundle;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Integration\Api\IntegrationServiceInterface;
use Psr\Log\LoggerInterface;

/**
 * Send webhook when get inventory update event.
 *
 * @author   AfterShip <apps@aftership.com>
 * @license  MIT http://opensource.org/licenses/MIT
 * @link     https://aftership.com
 */
class InventoryUpdateObserver implements ObserverInterface
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
     * Construct
     *
     * @param ProductRepositoryInterface  $productRepository
     * @param UserContextInterface        $userContext
     * @param IntegrationServiceInterface $integrationService
     * @param LoggerInterface             $logger
     * @param WebhookHelper               $webhookHelper
     * @param Configurable                $configurable
     * @param Grouped                     $grouped
     * @param Bundle                      $bundle
     */
    public function __construct(
        ProductRepositoryInterface  $productRepository,
        UserContextInterface        $userContext,
        IntegrationServiceInterface $integrationService,
        LoggerInterface             $logger,
        WebhookHelper               $webhookHelper,
        Configurable                $configurable,
        Grouped                     $grouped,
        Bundle                      $bundle
    ) {
        $this->userContext = $userContext;
        $this->integrationService = $integrationService;
        $this->productRepository = $productRepository;
        $this->configurable = $configurable;
        $this->grouped = $grouped;
        $this->bundle = $bundle;
        $this->webhookHelper = $webhookHelper;
        $this->logger = $logger;
    }

    /**
     * GetParentProductIds.
     *
     * @param string $productId
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
     * IsRestfulApiRequest
     *
     * @return bool
     */
    public function isRestfulApiRequest()
    {
        $userType = $this->userContext->getUserType();
        return ($userType === UserContextInterface::USER_TYPE_INTEGRATION);
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
            if ($this->isRestfulApiRequest()) {
                $stockItem = $observer->getItem();
                $productId = $stockItem->getProductId();
                $parentIds = $this->getParentProductIds($productId);
                foreach ($parentIds as $parentId) {
                    $parentProduct = $this->productRepository->getById($parentId);
                    $this->webhookHelper->makeWebhookRequest(
                        Constants::WEBHOOK_TOPIC_PRODUCTS_UPDATE,
                        [
                        "id" => $parentId,
                        "type_id" => $parentProduct->getTypeId(),
                        "sku" => $parentProduct->getSku(),
                        "visibility" => (string)$parentProduct->getVisibility(),
                        ]
                    );
                }
            }
        } catch (\Exception $e) {
            $this->logger->error(sprintf('[AfterShip TikTokShop] Faield to update product data on InventoryUpdateObserver, %s', $e->getMessage()));
        }
    }
}
