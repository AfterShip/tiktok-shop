<?php
/**
 * TikTokShop StockStatusBatch
 *
 * @author    AfterShip <support@aftership.com>
 * @copyright 2023 AfterShip
 * @license   MIT http://opensource.org/licenses/MIT
 * @link      https://aftership.com
 */

namespace AfterShip\TikTokShop\Model\Api;

use AfterShip\TikTokShop\Api\StockStatusBatchInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Psr\Log\LoggerInterface;

/**
 * StockStatusBatch implementation.
 *
 * @author   AfterShip <support@aftership.com>
 * @license  MIT http://opensource.org/licenses/MIT
 * @link     https://aftership.com
 */
class StockStatusBatch implements StockStatusBatchInterface
{
    /**
     * @var StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Constructor
     *
     * @param StockRegistryInterface $stockRegistry
     * @param LoggerInterface $logger
     */
    public function __construct(
        StockRegistryInterface $stockRegistry,
        LoggerInterface $logger
    ) {
        $this->stockRegistry = $stockRegistry;
        $this->logger = $logger;
    }

    /**
     * Get stock statuses for multiple SKUs.
     *
     * @param int $scopeId
     * @param string[] $skus Array of SKUs
     *
     * @return array
     *
     * @throws LocalizedException
     */
    public function getStockStatuses($scopeId, array $skus)
    {
        if (empty($skus)) {
            throw new LocalizedException(
                new Phrase('SKUs parameter is required.'),
                null,
                400
            );
        }

        // Build result using StockRegistry API
        // Magento will automatically handle Default Stock vs MSI via plugin
        $result = [];
        foreach ($skus as $sku) {
            $sku = trim($sku);
            try {
                // Use StockRegistry API - it will automatically adapt MSI via plugin
                // See: Magento\InventoryCatalog\Plugin\CatalogInventory\Api\StockRegistry\AdaptGetStockStatusBySkuPlugin
                $stockStatus = $this->stockRegistry->getStockStatusBySku($sku, $scopeId);

                if ($stockStatus) {
                    $result[] = $stockStatus;
                }
            }catch (\Exception $e) {
                $this->logger->error(
                    \sprintf(
                        '[AfterShip TikTokShop] Error processing SKU %s: %s',
                        $sku,
                        $e->getMessage()
                    )
                );
            }
        }
        return $result;
    }
}
