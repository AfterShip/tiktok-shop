<?php
/**
 * TikTokShop ProductChangeType
 *
 * @author    AfterShip <support@aftership.com>
 * @copyright 2023 AfterShip
 * @license   MIT http://opensource.org/licenses/MIT
 * @link      https://aftership.com
 */

namespace AfterShip\TikTokShop\Api\Data;

/**
 * Product change type constants.
 *
 * @author   AfterShip <support@aftership.com>
 * @license  MIT http://opensource.org/licenses/MIT
 * @link     https://aftership.com
 */
class ProductChangeType
{
    /**
     * Stock change type - indicates inventory update
     */
    public const STOCK = 'stock';

    /**
     * Basic change type - indicates product basic fields update
     */
    public const BASIC = 'basic';
}

