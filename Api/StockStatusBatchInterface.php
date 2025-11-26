<?php
/**
 * TikTokShop StockStatusBatchInterface
 *
 * @author    AfterShip <support@aftership.com>
 * @copyright 2023 AfterShip
 * @license   MIT http://opensource.org/licenses/MIT
 * @link      https://aftership.com
 */

namespace AfterShip\TikTokShop\Api;

/**
 * Interface for batch stock status operations.
 *
 * @author   AfterShip <support@aftership.com>
 * @license  MIT http://opensource.org/licenses/MIT
 * @link     https://aftership.com
 */
interface StockStatusBatchInterface
{
    /**
     * Get stock statuses for multiple SKUs.
     *
     * @param int $scopeId
     * @param string[] $skus Array of SKUs
     *
     * @return \Magento\CatalogInventory\Api\Data\StockStatusInterface[]
     */
    public function getStockStatuses($scopeId, array $skus);
}
